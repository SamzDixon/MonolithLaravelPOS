<?php

namespace App\Providers;

use App\Models\StockTransfer;
use App\Policies\StockTransferPolicy;
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
    }
}
