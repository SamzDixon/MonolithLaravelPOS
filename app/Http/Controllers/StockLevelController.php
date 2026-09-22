<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockLevelController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        // Base query joins products and stores so we can sort and filter
        // by their columns without N+1 or subqueries.
        $query = StockLevel::query()
            ->whereIn('stock_levels.store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('stores', 'stores.id', '=', 'stock_levels.store_id')
            ->select('stock_levels.*')
            ->with(['store:id,name', 'product:id,name,sku,unit,reorder_level,cost_price']);

        // Filter by store
        if ($storeId = $request->input('store_id')) {
            if (in_array((int) $storeId, $storeIds, true)) {
                $query->where('stock_levels.store_id', $storeId);
            }
        }

        // Search by product name or SKU
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        // Low stock only — quantity at or below reorder level
        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
                  ->where('products.reorder_level', '>', 0);
        }

        $levels = $query
            ->orderBy('stores.name')
            ->orderBy('products.name')
            ->paginate(30)
            ->withQueryString();

        // Summary tiles — computed over the full set, not the current page
        $totalValue = StockLevel::query()
            ->whereIn('stock_levels.store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->selectRaw('COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as total')
            ->value('total');

        $totalUnits = StockLevel::whereIn('store_id', $storeIds)->sum('quantity');

        $lowStockCount = StockLevel::query()
            ->whereIn('stock_levels.store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
            ->where('products.reorder_level', '>', 0)
            ->count();

        $stores = Store::whereIn('id', $storeIds)->orderBy('name')->get(['id', 'name']);

        return view('stock-levels.index', [
            'levels' => $levels,
            'stores' => $stores,
            'storeIds' => $storeIds,
            'summary' => [
                'total_value' => (float) $totalValue,
                'total_units' => (int) $totalUnits,
                'low_stock_count' => (int) $lowStockCount,
            ],
            'filters' => [
                'store_id' => $request->input('store_id'),
                'search' => $search,
                'low_stock' => $request->boolean('low_stock'),
            ],
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        $query = StockLevel::query()
            ->whereIn('stock_levels.store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->join('stores', 'stores.id', '=', 'stock_levels.store_id')
            ->select('stock_levels.*')
            ->with(['store:id,name', 'product:id,name,sku,unit,reorder_level,cost_price']);

        if ($storeId = $request->input('store_id')) {
            if (in_array((int) $storeId, $storeIds, true)) {
                $query->where('stock_levels.store_id', $storeId);
            }
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                ->orWhere('products.sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
                ->where('products.reorder_level', '>', 0);
        }

        $levels = $query
            ->orderBy('stores.name')
            ->orderBy('products.name')
            ->paginate(30);

        return response()->json([
            'count' => $levels->total(),
            'rows' => $levels->map(fn ($level) => [
                'id' => $level->id,
                'store_name' => $level->store->name,
                'sku' => $level->product->sku,
                'product_name' => $level->product->name,
                'unit' => $level->product->unit,
                'quantity' => (int) $level->quantity,
                'reorder_level' => (int) $level->product->reorder_level,
                'cost_price' => (float) $level->product->cost_price,
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