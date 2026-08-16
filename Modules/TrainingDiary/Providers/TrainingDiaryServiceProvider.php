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
 * Training Diary has no API of its own yet (it only uses the core
 * feedback-chat endpoints: /api/feedback/threads, .../messages), so this
 * module only carries the admin panel for those threads, scoped to
 * app = config('trainingdiary.app_package').
 */
class TrainingDiaryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/trainingdiary.php', 'trainingdiary');
    }

    public function boot(): void
    {
        Route::middleware('web')
            ->prefix('training-admin32446234562345345')
            ->group(__DIR__.'/../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'trainingdiary');

        MobileAppRegistrar::register(config('trainingdiary.app_package'), '🏋️', 'Training Diary');
    }
}
