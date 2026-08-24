<?php

use Illuminate\Support\Facades\Route;
use Modules\Nutrition\Http\Controllers\Api\DishSearchController;
use Modules\Nutrition\Http\Controllers\Api\DishShowController;
use Modules\Nutrition\Http\Controllers\Api\DishStoreController;
use Modules\Nutrition\Http\Controllers\Api\ProductSearchByBarcodeController;
use Modules\Nutrition\Http\Controllers\Api\ProductSearchController;
use Modules\Nutrition\Http\Controllers\Api\ProductStoreController;

/*
|--------------------------------------------------------------------------
| Nutrition module API routes
|--------------------------------------------------------------------------
| Loaded by Modules\Nutrition\Providers\NutritionServiceProvider inside the
| main app's "api" route group (prefix /api, "api" middleware).
*/

Route::get('/nutrition/products/search', ProductSearchController::class);
Route::get('/nutrition/products/search-by-barcode', ProductSearchByBarcodeController::class);
Route::post('/nutrition/products', ProductStoreController::class);

Route::get('/nutrition/dishes/search', DishSearchController::class);
Route::get('/nutrition/dishes/{uuid}', DishShowController::class);
Route::post('/nutrition/dishes', DishStoreController::class);
