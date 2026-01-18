<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
