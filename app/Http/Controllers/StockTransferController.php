<?php

namespace App\Http\Controllers;

use App\Http\Requests\DispatchTransferRequest;
use App\Http\Requests\ReceiveTransferRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Models\StockLevel;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class StockTransferController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', StockTransfer::class);

        $storeIds = $this->visibleStoreIds($request->user());

        $transfers = StockTransfer::query()
            ->where(function ($q) use ($storeIds) {
                $q->whereIn('from_store_id', $storeIds)
                  ->orWhereIn('to_store_id', $storeIds);
            })
            ->with([
                'fromStore:id,name,branch_id',
                'toStore:id,name,branch_id',
                'requestedBy:id,name',
            ])
            ->latest('id')
            ->paginate(25);

        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockTransfer::class);

        $user = $request->user();

        $fromStores = $user->isAdmin()
            ? Store::where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : Store::where('branch_id', $user->branch_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $toStores = Store::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('transfers.create', compact('fromStores', 'toStores'));
    }

    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $transfer = DB::transaction(function () use ($data, $user) {
            $transfer = StockTransfer::create([
                'reference' => $this->nextReference(),
                'from_store_id' => $data['from_store_id'],
                'to_store_id' => $data['to_store_id'],
                'requested_by' => $user->id,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                ]);
            }

            return $transfer;
        });

        return redirect()
            ->route('transfers.show', $transfer)
            ->with('status', "Transfer {$transfer->reference} created.");
    }

    public function show(StockTransfer $transfer): View
    {
        $this->authorize('view', $transfer);

        $transfer->load([
            'fromStore:id,name,branch_id',
            'toStore:id,name,branch_id',
            'requestedBy:id,name',
            'dispatchedBy:id,name',
            'receivedBy:id,name',
            'items.product:id,name,sku',
        ]);

        return view('transfers.show', compact('transfer'));
    }

    public function dispatch(DispatchTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        try {
            $this->stock->dispatchTransfer($transfer, $request->user());

            return redirect()
                ->route('transfers.show', $transfer)
                ->with('status', "Transfer {$transfer->reference} dispatched.");
        } catch (RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function receive(ReceiveTransferRequest $request, StockTransfer $transfer): RedirectResponse
    {
        // If the receiver submitted per-line received quantities, persist them
        // before the service reads them. Anything not submitted defaults to
        // the dispatched quantity inside the service.
        $receivedLines = collect($request->input('items', []))->keyBy('id');

        foreach ($transfer->items as $item) {
            if ($receivedLines->has($item->id)) {
                $item->update([
                    'received_quantity' => $receivedLines[$item->id]['received_quantity'],
                ]);
            }
        }

        try {
            $this->stock->receiveTransfer($transfer, $request->user());

            return redirect()
                ->route('transfers.show', $transfer)
                ->with('status', "Transfer {$transfer->reference} received.");
        } catch (RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()]);
        }
    }

    public function destroy(StockTransfer $transfer): RedirectResponse
    {
        $this->authorize('delete', $transfer);

        if ($transfer->status !== 'pending') {
            return back()->withErrors([
                'transfer' => 'Only pending transfers can be cancelled.',
            ]);
        }

        $transfer->update(['status' => 'cancelled']);

        return redirect()
            ->route('transfers.index')
            ->with('status', "Transfer {$transfer->reference} cancelled.");
    }

    /**
     * JSON: products with positive stock at a source store, for the
     * create form's product picker.
     */
    public function stockForStore(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();

        $allowed = $user->isAdmin()
            || ($user->isBranchManager() && $store->branch_id === $user->branch_id);

        abort_unless($allowed, 403);

        $products = StockLevel::query()
            ->where('store_id', $store->id)
            ->where('quantity', '>', 0)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.unit',
                'stock_levels.quantity as available_quantity'
            )
            ->orderBy('products.name')
            ->get();

        return response()->json([
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit' => $p->unit,
                'available' => (int) $p->available_quantity,
            ]),
        ]);
    }

    private function nextReference(): string
    {
        $prefix = 'TRF-'.now()->format('Ymd').'-';
        $last = StockTransfer::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->value('reference');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function visibleStoreIds($user): array
    {
        if ($user->isAdmin()) {
            return Store::pluck('id')->all();
        }
        if ($user->isBranchManager()) {
            return Store::where('branch_id', $user->branch_id)->pluck('id')->all();
        }

        return $user->store_id ? [$user->store_id] : [];
    }
}