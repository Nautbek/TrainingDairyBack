<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use Modules\Nutrition\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use Modules\Nutrition\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DishShowController extends Controller
{
    public function __invoke(string $uuid): JsonResponse
    {
        try {
            $dish = Dish::query()
                ->where('uuid', $uuid)
                ->where('status', ProductStatus::Active)
                ->with('ingredients')
                ->first();

            if ($dish === null) {
                return response()->json([
                    'error' => 'Not Found',
                ], 404);
            }

            return response()->json($dish);
        } catch (\Exception $e) {
            Log::error('Error fetching dish: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
