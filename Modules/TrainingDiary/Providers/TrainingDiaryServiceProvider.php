<?php

namespace Modules\TrainingDiary\Providers;

use App\Services\MobileAppRegistrar;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the whole TrainingDiary module: its admin routes, migrations,
 * config and views. Registered once from bootstrap/providers.php —
 * deleting the Modules/TrainingDiary folder plus that one registration
 * line removes every trace of the module from the running app.
 *
 * Besides the admin panel, it carries the /api/training-diary/* sync
 * endpoints (exercise + approach log entries); the app also uses the core
 * feedback-chat endpoints (/api/feedback/threads, .../messages), which
 * live outside this module.
 */
class TrainingDiaryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/trainingdiary.php', 'trainingdiary');
    }

    public function boot(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__.'/../routes/api.php');

        Route::middleware('web')
            ->prefix('training-admin32446234562345345')
            ->group(__DIR__.'/../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'trainingdiary');

        MobileAppRegistrar::register(config('trainingdiary.app_package'), '🏋️', 'Training Diary');
    }
}
