<?php

use App\Http\Controllers\Api\Donation\ConfirmDonationController;
use App\Http\Controllers\Api\Donation\CreateDonationController;
use App\Http\Controllers\Api\Donation\DonationPaymentStatusController;
use App\Http\Controllers\Api\Donation\SubscriptionStatusController;
use App\Http\Controllers\Api\Donation\YooKassaWebhookController;
use App\Http\Controllers\Api\MyCar\ConfirmMyCarPaymentController;
use App\Http\Controllers\Api\MyCar\CreateMyCarPaymentController;
use App\Http\Controllers\Api\MyCar\MyCarPaymentStatusController;
use App\Http\Controllers\Api\MyCar\MyCarSubscriptionStatusController;
use App\Http\Controllers\Api\Nutrition\ProductSearchByBarcodeController;
use App\Http\Controllers\Api\Nutrition\ProductSearchController;
use App\Http\Controllers\Api\Nutrition\ProductStoreController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\TripSplit\ConfirmTripSplitPaymentController;
use App\Http\Controllers\Api\TripSplit\CreateTripSplitPaymentController;
use App\Http\Controllers\Api\TripSplit\SettleTripController;
use App\Http\Controllers\Api\TripSplit\TripSplitCreditsController;
use App\Http\Controllers\Api\TripSplit\TripSplitPaymentStatusController;
use App\Http\Controllers\Api\TripSplit\TripSplitSettlementPdfController;
use App\Http\Controllers\Api\UserFeedbackController;
use App\Http\Controllers\Api\UserOpenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', RegisterController::class);
Route::post('/user_open', UserOpenController::class);
Route::post('/user_feedback', UserFeedbackController::class);
Route::get('/nutrition/products/search', ProductSearchController::class);
Route::get('/nutrition/products/search-by-barcode', ProductSearchByBarcodeController::class);
Route::post('/nutrition/products', ProductStoreController::class);

Route::post('/donations/create', CreateDonationController::class);
Route::post('/donations/confirm', ConfirmDonationController::class);
Route::get('/donations/{paymentUuid}/status', DonationPaymentStatusController::class);
Route::get('/user/subscription', SubscriptionStatusController::class);
Route::post('/yookassa/webhook', YooKassaWebhookController::class);

Route::post('/tripsplit/payments/create', CreateTripSplitPaymentController::class);
Route::post('/tripsplit/payments/confirm', ConfirmTripSplitPaymentController::class);
Route::get('/tripsplit/payments/{paymentUuid}/status', TripSplitPaymentStatusController::class);
Route::get('/tripsplit/credits', TripSplitCreditsController::class);
Route::post('/tripsplit/settle', SettleTripController::class);
Route::get('/tripsplit/settlements/{settlementUuid}/pdf', TripSplitSettlementPdfController::class);

Route::post('/mycar/payments/create', CreateMyCarPaymentController::class);
Route::post('/mycar/payments/confirm', ConfirmMyCarPaymentController::class);
Route::get('/mycar/payments/{paymentUuid}/status', MyCarPaymentStatusController::class);
Route::get('/mycar/subscription', MyCarSubscriptionStatusController::class);
