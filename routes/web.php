<?php

use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Core routes only. Each module registers its own web/admin routes from its
| own service provider (see Modules/*\/Providers), so removing a module
| folder doesn't require touching this file.
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');

Route::get('/payment/return', fn () => view('payment.return'))->name('payment.return');
