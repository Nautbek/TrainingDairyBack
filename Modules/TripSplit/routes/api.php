<?php

use Illuminate\Support\Facades\Route;
use Modules\TripSplit\Http\Controllers\Api\ConfirmTripSplitPaymentController;
use Modules\TripSplit\Http\Controllers\Api\CreateTripSplitPaymentController;
use Modules\TripSplit\Http\Controllers\Api\SettleTripController;
use Modules\TripSplit\Http\Controllers\Api\TripSplitCreditsController;
use Modules\TripSplit\Http\Controllers\Api\TripSplitPaymentStatusController;
use Modules\TripSplit\Http\Controllers\Api\TripSplitSettlementPdfController;

/*
|--------------------------------------------------------------------------
| TripSplit module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\TripSplit\Providers\TripSplitServiceProvider inside the
| main app's "api" route group (prefix /api, "api" middleware).
*/

Route::post('/tripsplit/payments/create', CreateTripSplitPaymentController::class);
Route::post('/tripsplit/payments/confirm', ConfirmTripSplitPaymentController::class);
Route::get('/tripsplit/payments/{paymentUuid}/status', TripSplitPaymentStatusController::class);
Route::get('/tripsplit/credits', TripSplitCreditsController::class);
Route::post('/tripsplit/settle', SettleTripController::class);
Route::get('/tripsplit/settlements/{settlementUuid}/pdf', TripSplitSettlementPdfController::class);
