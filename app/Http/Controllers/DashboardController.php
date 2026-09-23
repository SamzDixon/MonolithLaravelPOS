<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * The landing page. Content depends on role: an admin sees branch
     * performance across the business, a branch manager sees the stores
     * in their branch, and a store manager sees their own operations.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $storeIds = $this->visibleStoreIds($user);

        if (empty($storeIds)) {
            return view('dashboard', [
                'user' => $user,
                'storeIds' => [],
                'summary' => $this->emptySummary(),
            ]);
        }

        // ---- Universal summary tiles ----
        $salesToday = Sale::whereIn('store_id', $storeIds)
            ->whereDate('created_at', today())
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total, COUNT(*) as sales_count')
            ->first();

        $transferCounts = StockTransfer::where(function ($q) use ($storeIds) {
                $q->whereIn('from_store_id', $storeIds)
                  ->orWhereIn('to_store_id', $storeIds);
            })
            ->whereIn('status', ['pending', 'dispatched'])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $summary = [
            'stock_value' => $this->stockValue($storeIds),
            'units_on_hand' => (int) StockLevel::whereIn('store_id', $storeIds)->sum('quantity'),
            'sales_today' => (float) $salesToday->total,
            'sales_today_count' => (int) $salesToday->sales_count,
            'low_stock_count' => $this->lowStockQuery($storeIds)->count(),
            'pending_transfers' => (int) ($transferCounts['pending'] ?? 0),
            'dispatched_transfers' => (int) ($transferCounts['dispatched'] ?? 0),
        ];

        $viewData = [
            'user' => $user,
            'storeIds' => $storeIds,
            'summary' => $summary,
        ];

        if ($user->isAdmin()) {
            $viewData = array_merge($viewData, $this->adminData($storeIds));
        } elseif ($user->isBranchManager()) {
            $viewData = array_merge($viewData, $this->branchManagerData($user, $storeIds));
        } else {
            $viewData = array_merge($viewData, $this->storeManagerData($user, $storeIds));
        }

        return view('dashboard', $viewData);
    }

    // ---------- Admin: branch-level overview ----------
    private function adminData(array $storeIds): array
    {
        // One query for stock value per branch.
        $branches = DB::table('branches')
            ->leftJoin('stores', function ($join) {
                $join->on('stores.branch_id', '=', 'branches.id')
                     ->whereNull('stores.deleted_at');
            })
            ->leftJoin('stock_levels', 'stock_levels.store_id', '=', 'stores.id')
            ->leftJoin('products', 'products.id', '=', 'stock_levels.product_id')
            ->whereNull('branches.deleted_at')
            ->groupBy('branches.id', 'branches.name', 'branches.location')
            ->select('branches.id', 'branches.name', 'branches.location')
            ->selectRaw('COUNT(DISTINCT stores.id) as store_count')
            ->selectRaw('COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as stock_value')
            ->selectRaw('COALESCE(SUM(stock_levels.quantity), 0) as units')
            ->orderBy('branches.name')
            ->get();

        // Sales today per branch, one query.
        $branchSalesToday = DB::table('branches')
            ->join('stores', 'stores.branch_id', '=', 'branches.id')
            ->join('sales', 'sales.store_id', '=', 'stores.id')
            ->whereDate('sales.created_at', today())
            ->whereNull('sales.deleted_at')
            ->groupBy('branches.id')
            ->select('branches.id')
            ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as sales_today')
            ->pluck('sales_today', 'id');

        $branches = $branches->map(fn ($b) => [
            'id' => $b->id,
            'name' => $b->name,
            'location' => $b->location,
            'store_count' => (int) $b->store_count,
            'units' => (int) $b->units,
            'stock_value' => (float) $b->stock_value,
            'sales_today' => (float) ($branchSalesToday[$b->id] ?? 0),
        ]);

        // Low stock across the whole business.
        $lowStockItems = $this->lowStockQuery($storeIds)
            ->with(['store:id,name', 'product:id,name,sku,unit,reorder_level'])
            ->orderBy('stock_levels.quantity')
            ->limit(8)
            ->get();

        // Active transfers across the business.
        $activeTransfers = StockTransfer::whereIn('status', ['pending', 'dispatched'])
            ->where(function ($q) use ($storeIds) {
                $q->whereIn('from_store_id', $storeIds)
                  ->orWhereIn('to_store_id', $storeIds);
            })
            ->with(['fromStore:id,name', 'toStore:id,name', 'requestedBy:id,name'])
            ->latest('id')
            ->limit(6)
            ->get();

        // Recent activity.
        $recentSales = Sale::whereIn('store_id', $storeIds)
            ->with(['store:id,name', 'user:id,name'])
            ->withCount('items')
            ->latest('id')
            ->limit(6)
            ->get();

        $recentMovements = StockMovement::whereIn('store_id', $storeIds)
            ->with(['store:id,name', 'product:id,name,sku', 'user:id,name'])
            ->latest('id')
            ->limit(10)
            ->get();

        return compact('branches', 'lowStockItems', 'activeTransfers', 'recentSales', 'recentMovements');
    }

        // ---------- Branch manager: store-level within their branch ----------
        private function branchManagerData($user, array $storeIds): array
        {
            $stores = DB::table('stores')
                ->leftJoin('stock_levels', 'stock_levels.store_id', '=', 'stores.id')
                ->leftJoin('products', 'products.id', '=', 'stock_levels.product_id')
                ->where('stores.branch_id', $user->branch_id)
                ->whereNull('stores.deleted_at')
                ->groupBy('stores.id', 'stores.name')
                ->select('stores.id', 'stores.name')
                ->selectRaw('COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as stock_value')
                ->selectRaw('COALESCE(SUM(stock_levels.quantity), 0) as units')
                ->orderBy('stores.name')
                ->get();

            $storeSalesToday = DB::table('stores')
                ->leftJoin('sales', function ($join) {
                    $join->on('sales.store_id', '=', 'stores.id')
                        ->whereDate('sales.created_at', today())
                        ->whereNull('sales.deleted_at');
                })
                ->where('stores.branch_id', $user->branch_id)
                ->whereNull('stores.deleted_at')
                ->groupBy('stores.id')
                ->select('stores.id')
                ->selectRaw('COALESCE(SUM(sales.total_amount), 0) as sales_today')
                ->pluck('sales_today', 'id');

            $stores = $stores->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'units' => (int) $s->units,
                'stock_value' => (float) $s->stock_value,
                'sales_today' => (float) ($storeSalesToday[$s->id] ?? 0),
            ]);

            $lowStockItems = $this->lowStockQuery($storeIds)
                ->with(['store:id,name', 'product:id,name,sku,unit,reorder_level'])
                ->orderBy('stock_levels.quantity')
                ->limit(8)
                ->get();

            $activeTransfers = StockTransfer::whereIn('status', ['pending', 'dispatched'])
                ->where(function ($q) use ($storeIds) {
                    $q->whereIn('from_store_id', $storeIds)
                    ->orWhereIn('to_store_id', $storeIds);
                })
                ->with(['fromStore:id,name', 'toStore:id,name', 'requestedBy:id,name'])
                ->latest('id')
                ->limit(6)
                ->get();

            $recentSales = Sale::whereIn('store_id', $storeIds)
                ->with(['store:id,name', 'user:id,name'])
                ->withCount('items')
                ->latest('id')
                ->limit(6)
                ->get();

            $recentMovements = StockMovement::whereIn('store_id', $storeIds)
                ->with(['store:id,name', 'product:id,name,sku', 'user:id,name'])
                ->latest('id')
                ->limit(10)
                ->get();

            return compact('stores', 'lowStockItems', 'activeTransfers', 'recentSales', 'recentMovements');
        }

        // ---------- Store manager: their own store ----------
        private function storeManagerData($user, array $storeIds): array
        {
            $storeId = $user->store_id;

            $salesWeek = (float) Sale::where('store_id', $storeId)
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->sum('total_amount');

            $salesMonth = (float) Sale::where('store_id', $storeId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            $recentSales = Sale::where('store_id', $storeId)
                ->with('user:id,name')
                ->withCount('items')
                ->latest('id')
                ->limit(6)
                ->get();

            $lowStockItems = $this->lowStockQuery([$storeId])
                ->with(['product:id,name,sku,unit,reorder_level'])
                ->orderBy('stock_levels.quantity')
                ->limit(8)
                ->get();

            $recentMovements = StockMovement::where('store_id', $storeId)
                ->with(['product:id,name,sku', 'user:id,name'])
                ->latest('id')
                ->limit(10)
                ->get();

            $incomingTransfers = StockTransfer::where('to_store_id', $storeId)
                ->whereIn('status', ['dispatched', 'pending'])
                ->with(['fromStore:id,name', 'requestedBy:id,name'])
                ->latest('id')
                ->limit(5)
                ->get();

            $outgoingTransfers = StockTransfer::where('from_store_id', $storeId)
                ->whereIn('status', ['pending', 'dispatched'])
                ->with(['toStore:id,name', 'requestedBy:id,name'])
                ->latest('id')
                ->limit(5)
                ->get();

            return compact(
                'salesWeek', 'salesMonth', 'recentSales',
                'lowStockItems', 'recentMovements',
                'incomingTransfers', 'outgoingTransfers'
            );
        }

        // ---------- Helpers ----------
        private function stockValue(array $storeIds): float
        {
            return (float) (StockLevel::whereIn('stock_levels.store_id', $storeIds)
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->selectRaw('COALESCE(SUM(stock_levels.quantity * products.cost_price), 0) as total')
                ->value('total') ?? 0);
        }

        private function lowStockQuery(array $storeIds)
        {
            return StockLevel::query()
                ->whereIn('stock_levels.store_id', $storeIds)
                ->join('products', 'products.id', '=', 'stock_levels.product_id')
                ->whereColumn('stock_levels.quantity', '<=', 'products.reorder_level')
                ->where('products.reorder_level', '>', 0)
                ->select('stock_levels.*');
        }

        private function emptySummary(): array
        {
            return [
                'stock_value' => 0,
                'units_on_hand' => 0,
                'sales_today' => 0,
                'sales_today_count' => 0,
                'low_stock_count' => 0,
                'pending_transfers' => 0,
                'dispatched_transfers' => 0,
            ];
        }

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