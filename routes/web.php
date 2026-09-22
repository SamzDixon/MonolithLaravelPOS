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

/*
|--------------------------------------------------------------------------
| Authenticated application routes
|--------------------------------------------------------------------------
|
| Everything behind this group requires a logged-in user whose account
| is still active. The active.user check sits here rather than on the
| login route because a deactivated user should be able to reach the
| login page to see the message, but not go any further.
|
*/

Route::middleware(['auth', 'active.user'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Admin-only: master data that affects the whole business.
    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class);
        Route::resource('users', UserController::class);
    });

    // Admin and branch manager: catalogue and read-only stock views.
    Route::middleware('role:admin,branch_manager')->group(function () {
        Route::resource('stores', StoreController::class);
        
        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
        Route::resource('products', ProductController::class);

        Route::get('stock-levels/search', [StockLevelController::class, 'search'])->name('stock-levels.search');
        Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');

        Route::get('stock-movements/search', [StockMovementController::class, 'search'])->name('stock-movements.search');
        Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
    });

    // Sales and transfers are available to all authenticated active users,
    // but policies on the controllers decide which records they can touch.
    Route::resource('sales', SaleController::class);
    Route::resource('transfers', StockTransferController::class)->except(['edit', 'update']);

    Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatch'])
        ->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])
        ->name('transfers.receive');
});

require __DIR__.'/auth.php';