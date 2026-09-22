<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The landing page. What a user sees here depends on their role,
     * because a store manager has no business looking at another branch's
     * numbers and an admin needs the whole picture.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Start from a query scoped to what this user is allowed to see.
        $storeIds = $this->visibleStoreIds($user);

        $stockValue = StockLevel::query()
            ->whereIn('store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->selectRaw('SUM(stock_levels.quantity * products.cost_price) as total')
            ->value('total') ?? 0;

        $salesToday = Sale::query()
            ->whereIn('store_id', $storeIds)
            ->whereDate('created_at', today())
            ->sum('total_amount');

        $lowStockCount = StockLevel::query()
            ->whereIn('store_id', $storeIds)
            ->join('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
            ->where('products.reorder_level', '>', 0)
            ->count();

        $recentMovements = StockMovement::query()
            ->whereIn('store_id', $storeIds)
            ->with(['product:id,name,sku', 'store:id,name', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard', [
            'storeIds' => $storeIds,
            'stockValue' => $stockValue,
            'salesToday' => $salesToday,
            'lowStockCount' => $lowStockCount,
            'recentMovements' => $recentMovements,
        ]);
    }

    /**
     * Which stores can this user see stock and sales for?
     * Admin: everything. Branch manager: their branch. Store manager: their store.
     */
    private function visibleStoreIds($user): array
    {
        if ($user->isAdmin()) {
            return Store::query()->pluck('id')->all();
        }

        if ($user->isBranchManager()) {
            return Store::query()
                ->where('branch_id', $user->branch_id)
                ->pluck('id')
                ->all();
        }

        return $user->store_id ? [$user->store_id] : [];
    }
}