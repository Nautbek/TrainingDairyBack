<?php

use App\Http\Controllers\Admin\NutritionProductController;
use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');

Route::prefix('admin23432150732412134')->group(function () {
    Route::get('/', [NutritionProductController::class, 'index'])->name('admin.products.index');
    Route::post('/products/{product}/approve', [NutritionProductController::class, 'approve'])->name('admin.products.approve');
    Route::post('/products/{product}/decline', [NutritionProductController::class, 'decline'])->name('admin.products.decline');
    Route::post('/products/{product}/delete', [NutritionProductController::class, 'destroy'])->name('admin.products.destroy');
});
