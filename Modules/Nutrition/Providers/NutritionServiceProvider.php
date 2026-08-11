<?php

namespace Modules\Nutrition\Providers;

use App\Services\MobileAppRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the whole Nutrition module: its own routes, migrations, config and
 * views. Registered once from bootstrap/providers.php — deleting the
 * Modules/Nutrition folder plus that one registration line removes every
 * trace of the module from the running app.
 */
class NutritionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nutrition.php', 'nutrition');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        Route::middleware('web')
            ->prefix('admin23432150732412134')
            ->group(__DIR__.'/../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nutrition');

        MobileAppRegistrar::register(config('nutrition.app_package'), '🍽️', 'Nutrition Journal');
    }
}
