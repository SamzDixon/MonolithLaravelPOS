<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])->group(function () {

    // Dashboard — every authenticated user
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Sales — every authenticated user (policies scope the data)
    Route::get('sales/search', [SaleController::class, 'search'])->name('sales.search');
    Route::get('sales/stock-for-store/{store}', [SaleController::class, 'stockForStore'])->name('sales.stock-for-store');
    Route::resource('sales', SaleController::class)->except(['edit', 'update']);

    // Admin only — master data
    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class);
        Route::resource('users', UserController::class);
    });

    // Admin and branch manager — catalogue, stock views, transfers
    Route::middleware('role:admin,branch_manager')->group(function () {
        Route::resource('stores', StoreController::class);

        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
        Route::resource('products', ProductController::class);

        Route::get('stock-levels/search', [StockLevelController::class, 'search'])->name('stock-levels.search');
        Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');

        Route::get('stock-movements/search', [StockMovementController::class, 'search'])->name('stock-movements.search');
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');

        Route::get('transfers/stock-for-store/{store}', [StockTransferController::class, 'stockForStore'])
            ->name('transfers.stock-for-store');
        Route::resource('transfers', StockTransferController::class)->except(['edit', 'update']);
        Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatch'])
            ->name('transfers.dispatch');
        Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])
            ->name('transfers.receive');
    });
});

require __DIR__.'/auth.php';