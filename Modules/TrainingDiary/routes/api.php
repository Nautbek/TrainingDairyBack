<?php

use Illuminate\Support\Facades\Route;
use Modules\TrainingDiary\Http\Controllers\Api\ExerciseEntryStoreController;

/*
|--------------------------------------------------------------------------
| TrainingDiary module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\TrainingDiary\Providers\TrainingDiaryServiceProvider
| inside the main app's "api" route group (prefix /api, "api" middleware).
| Only the upload side is live: the Android app's hourly catch-up worker
| sends one not-yet-synced exercise (+ its approaches) per hour.
|
| GET (the pull side for cross-device sync — see ExerciseEntryIndexController,
| still present but unrouted) is deliberately NOT registered: it read a
| user's whole history by bare uuid with no proof of ownership — uuid isn't
| a secret, it's shown in the app and could leak via a screenshot. Re-enable
| only once there's real per-device auth (see the "Аккаунт по email" plan —
| email+password, device tokens) to gate it.
*/

Route::post('/training-diary/exercises', ExerciseEntryStoreController::class);
