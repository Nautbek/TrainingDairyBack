<?php

use App\Http\Controllers\Api\UserFeedbackController;
use App\Http\Controllers\Api\UserOpenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// API роуты (аналоги Go проекта)
Route::post('/api/user_open', UserOpenController::class);
Route::post('/api/user_feedback', UserFeedbackController::class);
