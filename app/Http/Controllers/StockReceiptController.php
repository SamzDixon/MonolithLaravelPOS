<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class StockReceiptController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockReceipt::class);

        $storeIds = $this->visibleStoreIds($request->user());

        $receipts = StockReceipt::query()
            ->whereIn('store_id', $storeIds)
            ->with(['supplier:id,name', 'store:id,name', 'user:id,name'])
            ->withCount('items')
            ->latest('id')
            ->paginate(25);

        return view('receipts.index', compact('receipts'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockReceipt::class);

        $user = $request->user();

        $stores = $this->visibleStores($user);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $products = Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'cost_price']);

        return view('receipts.create', compact('stores', 'suppliers', 'products'));
    }

    public function store(StoreStockReceiptRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        try {
            $receipt = DB::transaction(function () use ($data, $user) {
                $total = 0;
                $receipt = StockReceipt::create([
                    'reference' => $this->nextReference(),
                    'supplier_id' => $data['supplier_id'],
                    'store_id' => $data['store_id'],
                    'user_id' => $user->id,
                    'total_cost' => 0,
                    'status' => 'received',
                    'notes' => $data['notes'] ?? null,
                    'received_at' => now(),
                ]);

                foreach ($data['items'] as $line) {
                    $lineTotal = $line['quantity'] * $line['unit_cost'];
                    $total += $lineTotal;

                    StockReceiptItem::create([
                        'stock_receipt_id' => $receipt->id,
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'unit_cost' => $line['unit_cost'],
                        'line_total' => $lineTotal,
                    ]);
                }

                $receipt->update(['total_cost' => $total]);
                $receipt->load(['items', 'supplier', 'store']);

                $this->stock->recordReceipt($receipt, $user);

                return $receipt;
            });

            return redirect()
                ->route('receipts.show', $receipt)
                ->with('status', "Receipt {$receipt->reference} recorded.");
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function show(StockReceipt $receipt): View
    {
        $this->authorize('view', $receipt);

        $receipt->load([
            'supplier:id,name,contact_person,phone,email',
            'store:id,name',
            'user:id,name',
            'items.product:id,name,sku,unit',
        ]);

        return view('receipts.show', compact('receipt'));
    }

    public function destroy(StockReceipt $receipt): RedirectResponse
    {
        $this->authorize('delete', $receipt);

        if ($receipt->status === 'reversed') {
            return back()->withErrors(['receipt' => 'This receipt has already been reversed.']);
        }

        $user = request()->user();

        DB::transaction(function () use ($receipt, $user) {
            foreach ($receipt->items as $item) {
                $level = \App\Models\StockLevel::where('store_id', $receipt->store_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($level) {
                    if ($level->quantity < $item->quantity) {
                        throw new RuntimeException(
                            "Cannot reverse: {$item->product->name} has only {$level->quantity} on hand, "
                            . "but the receipt recorded {$item->quantity}."
                        );
                    }
                    $level->quantity -= $item->quantity;
                    $level->save();
                }

                \App\Models\StockMovement::create([
                    'store_id' => $receipt->store_id,
                    'product_id' => $item->product_id,
                    'quantity_delta' => -$item->quantity,
                    'type' => 'adjustment',
                    'reference_type' => StockReceipt::class,
                    'reference_id' => $receipt->id,
                    'user_id' => $user->id,
                    'notes' => "Reversal of {$receipt->reference}",
                ]);
            }

            $receipt->update(['status' => 'reversed']);
        });

        return redirect()
            ->route('receipts.index')
            ->with('status', "Receipt {$receipt->reference} reversed.");
    }

    private function visibleStores($user)
    {
        if ($user->isAdmin()) {
            return Store::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        if ($user->isBranchManager()) {
            return Store::where('branch_id', $user->branch_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        }
        return Store::where('id', $user->store_id)->get(['id', 'name']);
    }

    private function visibleStoreIds($user): array
    {
        if ($user->isAdmin()) return Store::pluck('id')->all();
        if ($user->isBranchManager()) return Store::where('branch_id', $user->branch_id)->pluck('id')->all();
        return $user->store_id ? [$user->store_id] : [];
    }

    private function nextReference(): string
    {
        $prefix = 'GRN-'.now()->format('Ymd').'-';
        $last = StockReceipt::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}