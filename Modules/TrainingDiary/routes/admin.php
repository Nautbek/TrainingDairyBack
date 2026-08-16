<?php

use Illuminate\Support\Facades\Route;
use Modules\TrainingDiary\Http\Controllers\Admin\FeedbackController;
use Modules\TrainingDiary\Http\Controllers\Admin\UserDiscountController;

/*
|--------------------------------------------------------------------------
| TrainingDiary module admin (web) routes
|--------------------------------------------------------------------------
| Loaded by Modules\TrainingDiary\Providers\TrainingDiaryServiceProvider
| under an obfuscated admin prefix (no auth — see the provider), same
| pattern as Modules/Nutrition.
*/

Route::get('/', [FeedbackController::class, 'index'])->name('trainingdiary.admin.feedback.index');
Route::post('/feedback/{thread}/update', [FeedbackController::class, 'update'])->name('trainingdiary.admin.feedback.update');

Route::get('/users', [UserDiscountController::class, 'index'])->name('trainingdiary.admin.users.index');
Route::post('/users/{user}/discount', [UserDiscountController::class, 'update'])->name('trainingdiary.admin.users.discount');
