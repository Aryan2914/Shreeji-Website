<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\DeliveryEstimateService;
use App\Services\PricingService;
use App\Services\RazorpayService;
use App\Services\StockService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons for consistent state within a request
        $this->app->singleton(StockService::class);
        $this->app->singleton(PricingService::class);
        $this->app->singleton(CartService::class);
        $this->app->singleton(DeliveryEstimateService::class);
        $this->app->singleton(RazorpayService::class);

        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(CheckoutService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
