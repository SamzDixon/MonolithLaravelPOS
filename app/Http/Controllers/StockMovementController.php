<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    private const TYPES = ['opening', 'receipt', 'sale', 'transfer_in', 'transfer_out', 'adjustment'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        $query = StockMovement::query()
            ->whereIn('stock_movements.store_id', $storeIds)
            ->with([
                'store:id,name',
                'product:id,name,sku',
                'user:id,name',
            ]);

        // Filter by store
        if ($storeId = $request->input('store_id')) {
            if (in_array((int) $storeId, $storeIds, true)) {
                $query->where('store_id', $storeId);
            }
        }

        // Filter by type
        $type = $request->input('type');
        if ($type && in_array($type, self::TYPES, true)) {
            $query->where('type', $type);
        }

        // Search by product name or SKU
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Date range
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $movements = $query
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $stores = Store::whereIn('id', $storeIds)->orderBy('name')->get(['id', 'name']);

        return view('stock-movements.index', [
            'movements' => $movements,
            'stores' => $stores,
            'types' => self::TYPES,
            'filters' => [
                'store_id' => $request->input('store_id'),
                'type' => $type,
                'search' => $search,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        $query = StockMovement::query()
            ->whereIn('stock_movements.store_id', $storeIds)
            ->with(['store:id,name', 'product:id,name,sku', 'user:id,name']);

        if ($storeId = $request->input('store_id')) {
            if (in_array((int) $storeId, $storeIds, true)) {
                $query->where('store_id', $storeId);
            }
        }

        $type = $request->input('type');
        if ($type && in_array($type, self::TYPES, true)) {
            $query->where('type', $type);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $movements = $query->latest('id')->paginate(30);

        return response()->json([
            'count' => $movements->total(),
            'rows' => $movements->map(fn ($m) => [
                'id' => $m->id,
                'created_at' => $m->created_at->format('d M Y, H:i'),
                'store_name' => $m->store->name,
                'product_name' => $m->product->name,
                'sku' => $m->product->sku,
                'type' => $m->type,
                'quantity_delta' => (int) $m->quantity_delta,
                'user_name' => $m->user->name,
                'notes' => $m->notes,
            ]),
        ]);
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