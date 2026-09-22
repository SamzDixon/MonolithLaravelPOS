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

        $sales = Sale::query()
            ->whereIn('store_id', $this->visibleStoreIds($request->user()))
            ->with(['store:id,name', 'user:id,name'])
            ->latest()
            ->paginate(25);

        return view('sales.index', compact('sales'));
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
        $storeId = $user->store_id;

        $stockedProducts = StockLevel::query()
            ->where('store_id', $storeId)
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->with('product:id,name,sku,selling_price')
            ->where('quantity', '>', 0)
            ->get()
            ->pluck('product');

        return view('sales.create', [
            'store' => $user->store,
            'products' => $stockedProducts,
        ]);
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