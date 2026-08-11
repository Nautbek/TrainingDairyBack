<?php

use App\Http\Controllers\Api\Donation\ConfirmDonationController;
use App\Http\Controllers\Api\Donation\CreateDonationController;
use App\Http\Controllers\Api\Donation\DonationPaymentStatusController;
use App\Http\Controllers\Api\Donation\SubscriptionStatusController;
use App\Http\Controllers\Api\Donation\YooKassaWebhookController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\UserFeedbackController;
use App\Http\Controllers\Api\UserOpenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — core app only
|--------------------------------------------------------------------------
| User accounts, feedback/visit tracking, and the generic ad-free donation
| flow shared by every app. Each per-app module (MyCar, TripSplit,
| Nutrition, ...) registers its own routes from its own service provider —
| see Modules/*\/Providers and bootstrap/providers.php.
*/

Route::post('/register', RegisterController::class);
Route::post('/user_open', UserOpenController::class);
Route::post('/user_feedback', UserFeedbackController::class);

Route::post('/donations/create', CreateDonationController::class);
Route::post('/donations/confirm', ConfirmDonationController::class);
Route::get('/donations/{paymentUuid}/status', DonationPaymentStatusController::class);
Route::get('/user/subscription', SubscriptionStatusController::class);
Route::post('/yookassa/webhook', YooKassaWebhookController::class);
