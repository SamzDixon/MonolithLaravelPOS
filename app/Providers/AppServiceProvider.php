<?php

namespace App\Providers;

use App\Models\StockReceipt;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Policies\StockReceiptPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPolicy;
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
        // Explicit registration. Auto-discovery works in theory, but when
        // it silently fails you get default-deny 403s with no error — so we
        // wire every policy up by hand.
        Gate::policy(StockTransfer::class, StockTransferPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(StockReceipt::class, StockReceiptPolicy::class);
    }
}