<?php

namespace Modules\Nutrition\Tests\Feature;

use Modules\Nutrition\Enums\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionDishStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_dish_creates_draft_with_recomputed_macros_for_existing_user(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        // 400g of beef (26/15/0 per 100g) + 300g of potato (2/0.4/16.3 per 100g) + 200g water.
        $response = $this->postJson('/api/nutrition/dishes', [
            'uuid' => $uuid,
            'name' => 'Борщ',
            'water_grams' => 200,
            'ingredients' => [
                ['name' => 'Говядина', 'proteins' => 26, 'fats' => 15, 'carbs' => 0, 'grams' => 400],
                ['name' => 'Картофель', 'proteins' => 2, 'fats' => 0.4, 'carbs' => 16.3, 'grams' => 300],
            ],
        ]);

        $totalGrams = 400 + 300 + 200;
        $totalProtein = 26 * 4 + 2 * 3;
        $totalFat = 15 * 4 + 0.4 * 3;
        $totalCarbs = 0 * 4 + 16.3 * 3;
        $expectedProtein = round($totalProtein * 100 / $totalGrams, 2);
        $expectedFat = round($totalFat * 100 / $totalGrams, 2);
        $expectedCarbs = round($totalCarbs * 100 / $totalGrams, 2);
        $expectedCalories = round($expectedProtein * 4 + $expectedFat * 9 + $expectedCarbs * 4, 2);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'uuid', 'status', 'proteins', 'fats', 'carbs', 'calories', 'total_grams'])
            ->assertJson([
                'status' => ProductStatus::Draft->value,
                'proteins' => $expectedProtein,
                'fats' => $expectedFat,
                'carbs' => $expectedCarbs,
                'calories' => $expectedCalories,
                'total_grams' => (float) $totalGrams,
            ]);

        $this->assertDatabaseHas('nutrition_dishes', [
            'name' => 'Борщ',
            'uuid' => $response->json('uuid'),
            'author_uuid' => $uuid,
            'status' => ProductStatus::Draft->value,
        ]);

        $this->assertDatabaseHas('nutrition_dish_ingredients', [
            'name' => 'Говядина',
            'grams' => 400,
        ]);
        $this->assertDatabaseHas('nutrition_dish_ingredients', [
            'name' => 'Картофель',
            'grams' => 300,
        ]);
    }

    public function test_store_dish_ignores_client_submitted_aggregates(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/nutrition/dishes', [
            'uuid' => $uuid,
            'name' => 'Каша',
            'proteins' => 999,
            'fats' => 999,
            'carbs' => 999,
            'calories' => 999,
            'total_grams' => 999,
            'ingredients' => [
                ['name' => 'Овсянка', 'proteins' => 12, 'fats' => 6, 'carbs' => 60, 'grams' => 100],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertNotEquals(999, $response->json('proteins'));
        $this->assertEquals(100.0, $response->json('total_grams'));
    }

    public function test_store_dish_accepts_uuid_from_header(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson(
            '/api/nutrition/dishes',
            [
                'name' => 'Гречка с курицей',
                'ingredients' => [
                    ['name' => 'Гречка', 'proteins' => 12, 'fats' => 3, 'carbs' => 62, 'grams' => 200],
                    ['name' => 'Курица', 'proteins' => 23, 'fats' => 4, 'carbs' => 0, 'grams' => 150],
                ],
            ],
            ['X-User-UUID' => $uuid]
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('nutrition_dishes', [
            'name' => 'Гречка с курицей',
            'author_uuid' => $uuid,
        ]);
    }

    public function test_store_dish_returns_unauthorized_for_unknown_user(): void
    {
        $response = $this->postJson('/api/nutrition/dishes', [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Борщ',
            'ingredients' => [
                ['name' => 'Говядина', 'proteins' => 26, 'fats' => 15, 'carbs' => 0, 'grams' => 400],
            ],
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_store_dish_fails_validation_without_required_fields(): void
    {
        $response = $this->postJson('/api/nutrition/dishes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['uuid', 'name', 'ingredients']);
    }

    public function test_store_dish_fails_validation_for_empty_ingredients(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/nutrition/dishes', [
            'uuid' => $uuid,
            'name' => 'Пустое блюдо',
            'ingredients' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ingredients']);
    }

    public function test_store_dish_fails_validation_for_incomplete_ingredient_row(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson('/api/nutrition/dishes', [
            'uuid' => $uuid,
            'name' => 'Блюдо',
            'ingredients' => [
                ['proteins' => 10, 'fats' => 5, 'carbs' => 20],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ingredients.0.name', 'ingredients.0.grams']);
    }

    public function test_store_dish_does_not_leave_partial_rows_on_failed_transaction(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $this->postJson('/api/nutrition/dishes', [
            'uuid' => $uuid,
            'name' => 'Невалидное',
            'ingredients' => [
                ['name' => 'Что-то', 'proteins' => 10, 'fats' => 5, 'carbs' => 20], // missing grams
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('nutrition_dishes', 0);
        $this->assertDatabaseCount('nutrition_dish_ingredients', 0);
    }
}
