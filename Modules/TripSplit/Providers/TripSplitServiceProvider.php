<?php

namespace Modules\TripSplit\Providers;

use App\Services\MobileAppRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\TripSplit\Services\TripSplitPaymentService;

/**
 * Boots the whole TripSplit module: its own routes, migrations, config and
 * views, and registers its payment service as the handler for the
 * "tripsplit" DonationPayment app key. Registered once from
 * bootstrap/providers.php — deleting the Modules/TripSplit folder plus that
 * one registration line removes every trace of the module from the running
 * app.
 */
class TripSplitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tripsplit.php', 'tripsplit');

        $this->app->tag(TripSplitPaymentService::class, 'payment.app.handlers');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tripsplit');

        // Note: the Telegram label was historically keyed by 'ru.nautbek.custom',
        // which does not match config('tripsplit.app_package') ('com.example.tripsplit').
        // Kept as-is here to preserve existing notification behavior; that
        // mismatch predates this module split.
        MobileAppRegistrar::register('ru.nautbek.custom', '✈️', 'TripSplit');
    }
}
