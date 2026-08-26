<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterWithEmailController;
use App\Http\Controllers\Api\Donation\ConfirmDonationController;
use App\Http\Controllers\Api\Donation\CreateDonationController;
use App\Http\Controllers\Api\Donation\DonationPaymentStatusController;
use App\Http\Controllers\Api\Donation\DonationTiersController;
use App\Http\Controllers\Api\Donation\SubscriptionStatusController;
use App\Http\Controllers\Api\Donation\YooKassaWebhookController;
use App\Http\Controllers\Api\Feedback\FeedbackMessageController;
use App\Http\Controllers\Api\Feedback\FeedbackThreadController;
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

// Email+пароль — см. план "Аккаунт по email". Уживается с анонимным /register выше:
// клиент либо создаёт новый uuid прямо с реальными email/password (мандатор для новых
// установок), либо подсовывает уже существующий локальный uuid, чтобы привязать email к
// нему (необязательный баннер для уже установленных). Throttle на /auth/login — единственная
// по-настоящему новая угроза, которую вводит пароль (подбор).
Route::post('/auth/register', RegisterWithEmailController::class);
Route::post('/auth/login', LoginController::class)->middleware('throttle:5,1');

// Фидбек-чат: треды обращений + сообщения. Отдельно от /user_feedback выше,
// которая остаётся каналом для activity-пингов (см. Modules/*/… и Helper).
Route::get('/feedback/threads', [FeedbackThreadController::class, 'index']);
Route::post('/feedback/threads', [FeedbackThreadController::class, 'store']);
Route::get('/feedback/threads/{id}', [FeedbackThreadController::class, 'show']);
Route::delete('/feedback/threads/{id}', [FeedbackThreadController::class, 'destroy']);
Route::post('/feedback/threads/{id}/messages', [FeedbackMessageController::class, 'store']);

Route::get('/donations/tiers', DonationTiersController::class);
Route::post('/donations/create', CreateDonationController::class);
Route::post('/donations/confirm', ConfirmDonationController::class);
Route::get('/donations/{paymentUuid}/status', DonationPaymentStatusController::class);
Route::get('/user/subscription', SubscriptionStatusController::class);
Route::post('/yookassa/webhook', YooKassaWebhookController::class);
