<?php

use Illuminate\Support\Facades\Route;
use Modules\Nutrition\Http\Controllers\Admin\NutritionProductController;

/*
|--------------------------------------------------------------------------
| Nutrition module admin (web) routes
|--------------------------------------------------------------------------
| Loaded by Modules\Nutrition\Providers\NutritionServiceProvider under the
| main app's obfuscated admin prefix, see routes/web.php.
*/

Route::get('/', [NutritionProductController::class, 'index'])->name('admin.products.index');
Route::post('/products/{product}/update', [NutritionProductController::class, 'update'])->name('admin.products.update');
Route::post('/products/{product}/approve', [NutritionProductController::class, 'approve'])->name('admin.products.approve');
Route::post('/products/{product}/decline', [NutritionProductController::class, 'decline'])->name('admin.products.decline');
Route::post('/products/{product}/delete', [NutritionProductController::class, 'destroy'])->name('admin.products.destroy');
