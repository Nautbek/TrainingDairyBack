<?php

use Illuminate\Support\Facades\Route;
use Modules\Nutrition\Http\Controllers\Admin\NutritionDishController;
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

Route::get('/dishes', [NutritionDishController::class, 'index'])->name('admin.dishes.index');
Route::post('/dishes/{dish}/update', [NutritionDishController::class, 'update'])->name('admin.dishes.update');
Route::post('/dishes/{dish}/approve', [NutritionDishController::class, 'approve'])->name('admin.dishes.approve');
Route::post('/dishes/{dish}/decline', [NutritionDishController::class, 'decline'])->name('admin.dishes.decline');
Route::post('/dishes/{dish}/delete', [NutritionDishController::class, 'destroy'])->name('admin.dishes.destroy');
