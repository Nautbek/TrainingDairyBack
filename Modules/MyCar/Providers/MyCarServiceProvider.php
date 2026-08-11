<?php

namespace Modules\MyCar\Providers;

use App\Services\MobileAppRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\MyCar\Services\MyCarPaymentService;

/**
 * Boots the whole MyCar module: its own routes, migrations, config and
 * Telegram label, and registers its payment service as the handler for the
 * "mycar" DonationPayment app key. Registered once from
 * bootstrap/providers.php — deleting the Modules/MyCar folder plus that one
 * registration line removes every trace of the module from the running app.
 */
class MyCarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mycar.php', 'mycar');

        $this->app->tag(MyCarPaymentService::class, 'payment.app.handlers');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        MobileAppRegistrar::register(config('mycar.app_package'), '🚗', 'My Car');
    }
}
