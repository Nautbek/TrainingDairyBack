<?php

namespace App\Providers;

use App\Services\Payment\PaymentHandlerRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PaymentHandlerRegistry::class,
            fn ($app) => new PaymentHandlerRegistry($app->tagged('payment.app.handlers')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
