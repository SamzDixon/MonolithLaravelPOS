<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active.user'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Sales — every authenticated user (policies scope the data)
    Route::get('sales/search', [SaleController::class, 'search'])->name('sales.search');
    Route::get('sales/stock-for-store/{store}', [SaleController::class, 'stockForStore'])->name('sales.stock-for-store');
    Route::resource('sales', SaleController::class)->except(['edit', 'update']);

    // Admin only — business-wide master data
    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class);
        Route::resource('users', UserController::class);
    });

    // Admin and branch manager — catalogue and stock views
    Route::middleware('role:admin,branch_manager')->group(function () {
        Route::resource('stores', StoreController::class);

        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
        Route::resource('products', ProductController::class);
    });

    // Suppliers and receipts — no role middleware. Every operational role
    // touches these; policies and FormRequests enforce the boundaries.
    Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    Route::get('receipts', [StockReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/create', [StockReceiptController::class, 'create'])->name('receipts.create');
    Route::post('receipts', [StockReceiptController::class, 'store'])->name('receipts.store');
    Route::get('receipts/{receipt}', [StockReceiptController::class, 'show'])->name('receipts.show');
    Route::delete('receipts/{receipt}', [StockReceiptController::class, 'destroy'])->name('receipts.destroy');

    // Transfers — same reasoning
    Route::get('transfers/stock-for-store/{store}', [StockTransferController::class, 'stockForStore'])
        ->name('transfers.stock-for-store');
    Route::resource('transfers', StockTransferController::class)->except(['edit', 'update']);
    Route::post('transfers/{transfer}/dispatch', [StockTransferController::class, 'dispatch'])
        ->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])
        ->name('transfers.receive');

    // Stock levels - same reasoning    
    Route::get('stock-levels/search', [StockLevelController::class, 'search'])->name('stock-levels.search');
    Route::get('stock-levels', [StockLevelController::class, 'index'])->name('stock-levels.index');

    // Stock Movements -same reasoning
    Route::get('stock-movements/search', [StockMovementController::class, 'search'])->name('stock-movements.search');
    Route::get('stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
});

require __DIR__.'/auth.php';