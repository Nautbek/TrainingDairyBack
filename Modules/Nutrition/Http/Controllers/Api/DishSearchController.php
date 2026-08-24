<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use Modules\Nutrition\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use Modules\Nutrition\Http\Requests\SearchDishRequest;
use Modules\Nutrition\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DishSearchController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(SearchDishRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $dishes = Dish::query()
                ->select(['id', 'uuid', 'name', 'water_grams', 'total_grams', 'proteins', 'fats', 'carbs', 'calories', 'status'])
                ->searchByName($validated['name'])
                ->where('status', ProductStatus::Active)
                ->orderBy('name')
                ->paginate(self::PER_PAGE);

            return response()->json($dishes);
        } catch (\Exception $e) {
            Log::error('Error searching dishes: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
