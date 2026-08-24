<?php

namespace Modules\Nutrition\Support;

/**
 * Single place that turns a dish's ingredient list + water into the per-100g nutrition-facts
 * card that actually gets persisted on the Dish row. Used by both DishStoreController and
 * NutritionDishController::update — the server never trusts client-submitted aggregates, only
 * the raw ingredients + water, and always recomputes.
 */
class DishNutritionCalculator
{
    /**
     * @param  array<int, array{proteins: float, fats: float, carbs: float, grams: float}>  $ingredients
     * @return array{total_grams: float, proteins: float, fats: float, carbs: float, calories: float}
     */
    public static function computeAggregates(array $ingredients, float $waterGrams): array
    {
        $ingredientsGrams = array_sum(array_map(
            static fn (array $ingredient): float => (float) $ingredient['grams'],
            $ingredients
        ));
        $totalGrams = $ingredientsGrams + $waterGrams;

        if ($totalGrams <= 0.0) {
            return [
                'total_grams' => 0.0,
                'proteins' => 0.0,
                'fats' => 0.0,
                'carbs' => 0.0,
                'calories' => 0.0,
            ];
        }

        $totalProtein = 0.0;
        $totalFat = 0.0;
        $totalCarbs = 0.0;

        foreach ($ingredients as $ingredient) {
            $factor = ((float) $ingredient['grams']) / 100.0;
            $totalProtein += ((float) $ingredient['proteins']) * $factor;
            $totalFat += ((float) $ingredient['fats']) * $factor;
            $totalCarbs += ((float) $ingredient['carbs']) * $factor;
        }

        $per100 = 100.0 / $totalGrams;
        $proteins = round($totalProtein * $per100, 2);
        $fats = round($totalFat * $per100, 2);
        $carbs = round($totalCarbs * $per100, 2);
        $calories = round($proteins * 4 + $fats * 9 + $carbs * 4, 2);

        return [
            'total_grams' => round($totalGrams, 2),
            'proteins' => $proteins,
            'fats' => $fats,
            'carbs' => $carbs,
            'calories' => $calories,
        ];
    }
}
