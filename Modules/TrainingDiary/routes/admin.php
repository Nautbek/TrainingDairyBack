<?php

use Illuminate\Support\Facades\Route;
use Modules\TrainingDiary\Http\Controllers\Admin\FeedbackController;

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
