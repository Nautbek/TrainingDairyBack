<?php

use App\Http\Controllers\Api\Nutrition\ProductSearchController;
use App\Http\Controllers\Api\Nutrition\ProductStoreController;
use App\Http\Controllers\Api\RegisterController;
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
Route::post('/nutrition/products', ProductStoreController::class);
