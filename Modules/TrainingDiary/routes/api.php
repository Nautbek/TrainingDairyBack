<?php

use Illuminate\Support\Facades\Route;
use Modules\TrainingDiary\Http\Controllers\Api\ExerciseEntryStoreController;

/*
|--------------------------------------------------------------------------
| TrainingDiary module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\TrainingDiary\Providers\TrainingDiaryServiceProvider
| inside the main app's "api" route group (prefix /api, "api" middleware).
| Sync endpoint for exercise log entries (with their approaches) sent from
| the Android app's sync/ package — only the hourly catch-up worker calls
| this, one not-yet-synced exercise (+ its approaches) per hour.
*/

Route::post('/training-diary/exercises', ExerciseEntryStoreController::class);
