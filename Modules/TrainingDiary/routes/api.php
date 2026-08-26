<?php

use Illuminate\Support\Facades\Route;
use Modules\TrainingDiary\Http\Controllers\Api\ExerciseEntryIndexController;
use Modules\TrainingDiary\Http\Controllers\Api\ExerciseEntryStoreController;

/*
|--------------------------------------------------------------------------
| TrainingDiary module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\TrainingDiary\Providers\TrainingDiaryServiceProvider
| inside the main app's "api" route group (prefix /api, "api" middleware).
| POST is the upload side: the Android app's hourly catch-up worker sends
| one not-yet-synced exercise (+ its approaches) per hour. GET is the pull
| side used by the cross-device sync feature (sync/ExercisePullManager on
| the client) to restore a user's history onto a second device.
*/

Route::post('/training-diary/exercises', ExerciseEntryStoreController::class);
Route::get('/training-diary/exercises', ExerciseEntryIndexController::class);
