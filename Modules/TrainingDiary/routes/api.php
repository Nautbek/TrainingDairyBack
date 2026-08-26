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
| The Android app's hourly catch-up worker pushes one not-yet-synced
| exercise (+ its approaches) at a time; GET is the pull side used for
| cross-device sync ("Синхронизировать сейчас" on the Sync screen).
|
| GET was unrouted for a while: it read a user's whole history by bare uuid
| with no proof of ownership — uuid isn't a secret, it's shown in the app and
| could leak via a screenshot. Re-enabled now that the "Аккаунт по email"
| plan's device tokens exist — see ExerciseEntryIndexController and
| IndexExerciseEntryRequest, both gate on `device_token` too, not uuid alone.
*/

Route::post('/training-diary/exercises', ExerciseEntryStoreController::class);
Route::get('/training-diary/exercises', ExerciseEntryIndexController::class);
