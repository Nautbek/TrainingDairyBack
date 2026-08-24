<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use Modules\Nutrition\Enums\ProductStatus;
use Modules\Nutrition\Http\Requests\StoreDishRequest;
use Modules\Nutrition\Models\Dish;
use Modules\Nutrition\Models\DishIngredient;
use Modules\Nutrition\Support\DishNutritionCalculator;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DishStoreController extends Controller
{
    public function __invoke(StoreDishRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }

            $waterGrams = (float) ($validated['water_grams'] ?? 0);
            $aggregates = DishNutritionCalculator::computeAggregates($validated['ingredients'], $waterGrams);

            if ($aggregates['total_grams'] <= 0.0) {
                return response()->json([
                    'error' => 'The dish must have a positive total weight',
                ], 422);
            }

            $dish = DB::transaction(function () use ($validated, $waterGrams, $aggregates): Dish {
                do {
                    $dishUuid = (string) Str::uuid();
                } while (Dish::query()->where('uuid', $dishUuid)->exists());

                $dish = Dish::query()->create([
                    'uuid' => $dishUuid,
                    'name' => $validated['name'],
                    'water_grams' => $waterGrams,
                    'total_grams' => $aggregates['total_grams'],
                    'proteins' => $aggregates['proteins'],
                    'fats' => $aggregates['fats'],
                    'carbs' => $aggregates['carbs'],
                    'calories' => $aggregates['calories'],
                    'author_uuid' => $validated['uuid'],
                    'status' => ProductStatus::Draft,
                ]);

                $rows = [];
                foreach (array_values($validated['ingredients']) as $index => $ingredient) {
                    $rows[] = [
                        'dish_id' => $dish->id,
                        'product_uuid' => $ingredient['product_uuid'] ?? null,
                        'name' => $ingredient['name'],
                        'proteins' => $ingredient['proteins'],
                        'fats' => $ingredient['fats'],
                        'carbs' => $ingredient['carbs'],
                        'grams' => $ingredient['grams'],
                        'position' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DishIngredient::query()->insert($rows);

                return $dish;
            });

            return response()->json([
                'id' => $dish->id,
                'uuid' => $dish->uuid,
                'status' => $dish->status->value,
                'proteins' => $dish->proteins,
                'fats' => $dish->fats,
                'carbs' => $dish->carbs,
                'calories' => $dish->calories,
                'total_grams' => $dish->total_grams,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error saving dish: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
