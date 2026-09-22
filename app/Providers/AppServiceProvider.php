<?php

namespace App\Providers;

use App\Models\StockTransfer;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\StockReceiptPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(StockTransfer::class, StockTransferPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(StockReceipt::class, StockReceiptPolicy::class);
    }
}
