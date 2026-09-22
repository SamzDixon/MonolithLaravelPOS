<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockLevel;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SaleController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * Sales list, scoped to what the logged-in user is allowed to see.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        $filters = [
            'store_id' => $request->input('store_id'),
            'search' => trim((string) $request->input('search', '')),
            'from' => $request->input('from'),
            'to' => $request->input('to'),
        ];

        $query = Sale::query()
            ->withTrashed()
            ->whereIn('store_id', $storeIds)
            ->with(['store:id,name', 'user:id,name'])
            ->withCount('items');

        if ($filters['store_id']) $query->where('store_id', $filters['store_id']);
        if ($filters['search']) $query->where('reference', 'like', "%{$filters['search']}%");
        if ($filters['from']) $query->whereDate('created_at', '>=', $filters['from']);
        if ($filters['to']) $query->whereDate('created_at', '<=', $filters['to']);

        $sales = $query->latest('id')->paginate(25)->withQueryString();

        $stores = \App\Models\Store::whereIn('id', $storeIds)->orderBy('name')->get(['id', 'name']);

        return view('sales.index', compact('sales', 'stores', 'filters'));
    }

    /**
     * The form a cashier uses to record a new sale. Pre-loaded with the
     * products active at their store, so the jQuery autocomplete has
     * something to work with without a second HTTP round trip.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Sale::class);

        $user = $request->user();

        // Stores this user can record a sale against.
        if ($user->isStoreManager()) {
            $stores = \App\Models\Store::where('id', $user->store_id)->get(['id', 'name']);
        } elseif ($user->isBranchManager()) {
            $stores = \App\Models\Store::where('branch_id', $user->branch_id)
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        } else {
            $stores = \App\Models\Store::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }

        $selectedStoreId = old('store_id', $stores->first()?->id);

        // Preload stock for the initially-selected store so the form works
        // even if JS fails — the product dropdown has its options server-side.
        $products = $selectedStoreId
            ? $this->stockedProducts($selectedStoreId)
            : collect();

        return view('sales.create', compact('stores', 'selectedStoreId', 'products'));
    }

    /**
     * Record a sale. Everything happens inside StockService::recordSale,
     * which wraps the whole operation in a DB transaction with row-level
     * locks. If any item fails, the entire sale rolls back — no partial
     * sales, no orphaned ledger entries.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $sale = DB::transaction(function () use ($data, $user) {
                $total = 0;
                $sale = Sale::create([
                    'reference' => $this->nextReference(),
                    'store_id' => $data['store_id'],
                    'user_id' => $user->id,
                    'total_amount' => 0,
                    'status' => 'completed',
                ]);

                foreach ($data['items'] as $line) {
                    $product = Product::findOrFail($line['product_id']);
                    $lineTotal = $product->selling_price * $line['quantity'];
                    $total += $lineTotal;

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $line['quantity'],
                        'unit_price' => $product->selling_price,
                        'line_total' => $lineTotal,
                    ]);
                }

                $sale->update(['total_amount' => $total]);
                $sale->load('items');

                // The service runs its own transaction; nested transactions
                // in Laravel are savepoints, so it joins the outer one.
                $this->stock->recordSale($sale, $user);

                return $sale;
            });

            return redirect()
                ->route('sales.show', $sale)
                ->with('status', "Sale {$sale->reference} recorded.");
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['store:id,name', 'user:id,name', 'items.product:id,name,sku']);

        return view('sales.show', compact('sale'));
    }

    /**
     * Void a sale. We don't delete — we mark voided and write compensating
     * ledger entries so the audit trail is intact.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);

        $userId = request()->user()->id;

        DB::transaction(function () use ($sale, $userId) {
            foreach ($sale->items as $item) {
                $level = StockLevel::where('store_id', $sale->store_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($level) {
                    $level->quantity += $item->quantity;
                    $level->save();
                }

                \App\Models\StockMovement::create([
                    'store_id' => $sale->store_id,
                    'product_id' => $item->product_id,
                    'quantity_delta' => $item->quantity,
                    'type' => 'adjustment',
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'user_id' => $userId,
                    'notes' => "Void of {$sale->reference}",
                ]);
            }

            $sale->update(['status' => 'voided']);
            $sale->delete();
        });

        return redirect()
            ->route('sales.index')
            ->with('status', "Sale {$sale->reference} voided.");
    }

    /**
     * JSON: current stock for a store, for the create form's live product
     * picker and the client-side quantity warning.
     */
    public function stockForStore(Request $request, \App\Models\Store $store): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // Same store scoping as the create form.
        $allowed = $user->isAdmin()
            || ($user->isBranchManager() && $store->branch_id === $user->branch_id)
            || ($user->isStoreManager() && $store->id === $user->store_id);

        abort_unless($allowed, 403);

        return response()->json([
            'products' => $this->stockedProducts($store->id)->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit' => $p->unit,
                'selling_price' => (float) $p->selling_price,
                'available' => (int) $p->available_quantity,
            ]),
        ]);
    }

    /**
     * JSON: sales list for live filter on the index page.
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::query()
            ->whereIn('store_id', $this->visibleStoreIds($request->user()))
            ->with(['store:id,name', 'user:id,name'])
            ->withCount('items');

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where('reference', 'like', "%{$search}%");
        }

        // Clone the builder before summing, so paginate's LIMIT/OFFSET
        // doesn't corrupt the sum.
        $totalAmount = (clone $query)->sum('total_amount');
        $sales = $query->latest('id')->paginate(25);

        return response()->json([
            'count' => $sales->total(),
            'total_amount' => (float) $totalAmount,
            'rows' => $sales->map(fn ($s) => [
                'id' => $s->id,
                'reference' => $s->reference,
                'store_name' => $s->store->name,
                'user_name' => $s->user->name,
                'total_amount' => (float) $s->total_amount,
                'items_count' => $s->items_count,
                'status' => $s->status,
                'created_at' => $s->created_at->format('d M Y, H:i'),
                'show_url' => route('sales.show', $s),
            ]),
        ]);
    }

    /**
     * Shared: products with positive stock at a given store, including
     * the quantity available for the client-side stock check.
     */
    private function stockedProducts(int $storeId): \Illuminate\Support\Collection
    {
        return StockLevel::query()
            ->where('store_id', $storeId)
            ->where('quantity', '>', 0)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.unit',
                'products.selling_price',
                'stock_levels.quantity as available_quantity'
            )
            ->orderBy('products.name')
            ->get();
    }

    /**
     * SALE-YYYYMMDD-NNNN, sequence resets per day. Human-readable because
     * cashiers and auditors quote these on the phone.
     */
    private function nextReference(): string
    {
        $prefix = 'SALE-'.now()->format('Ymd').'-';
        $last = Sale::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function visibleStoreIds($user): array
    {
        if ($user->isAdmin()) {
            return \App\Models\Store::pluck('id')->all();
        }
        if ($user->isBranchManager()) {
            return \App\Models\Store::where('branch_id', $user->branch_id)->pluck('id')->all();
        }

        return $user->store_id ? [$user->store_id] : [];
    }
}