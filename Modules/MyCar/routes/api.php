<?php

use Illuminate\Support\Facades\Route;
use Modules\MyCar\Http\Controllers\Api\ConfirmMyCarPaymentController;
use Modules\MyCar\Http\Controllers\Api\CreateMyCarPaymentController;
use Modules\MyCar\Http\Controllers\Api\MyCarPaymentStatusController;
use Modules\MyCar\Http\Controllers\Api\MyCarSubscriptionStatusController;

/*
|--------------------------------------------------------------------------
| MyCar module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\MyCar\Providers\MyCarServiceProvider inside the main
| app's "api" route group (prefix /api, "api" middleware).
*/

Route::post('/mycar/payments/create', CreateMyCarPaymentController::class);
Route::post('/mycar/payments/confirm', ConfirmMyCarPaymentController::class);
Route::get('/mycar/payments/{paymentUuid}/status', MyCarPaymentStatusController::class);
Route::get('/mycar/subscription', MyCarSubscriptionStatusController::class);
