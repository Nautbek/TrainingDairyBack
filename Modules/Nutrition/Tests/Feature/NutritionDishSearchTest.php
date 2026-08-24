<?php

namespace Modules\Nutrition\Tests\Feature;

use Modules\Nutrition\Enums\ProductStatus;
use Modules\Nutrition\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NutritionDishSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): string
    {
        $uuid = (string) Str::uuid();

        DB::table('users')->insert([
            'uuid' => $uuid,
            'name' => 'test_user',
            'email' => $uuid.'@temp.local',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $uuid;
    }

    private function makeDish(string $authorUuid, string $name, ProductStatus $status): Dish
    {
        return Dish::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'water_grams' => 100,
            'total_grams' => 500,
            'proteins' => 10,
            'fats' => 5,
            'carbs' => 20,
            'calories' => 10 * 4 + 5 * 9 + 20 * 4,
            'author_uuid' => $authorUuid,
            'status' => $status,
        ]);
    }

    public function test_search_dishes_only_returns_active_dishes_matching_name(): void
    {
        $uuid = $this->makeUser();

        // Latin names on purpose: sqlite's LOWER() (used by this test's in-memory DB) doesn't
        // lowercase Cyrillic, unlike production's Postgres ILIKE path — see
        // NutritionProductSearchTest for the same pre-existing environment quirk.
        $this->makeDish($uuid, 'Borscht active', ProductStatus::Active);
        $this->makeDish($uuid, 'Borscht draft', ProductStatus::Draft);
        $this->makeDish($uuid, 'Borscht declined', ProductStatus::Decline);
        $this->makeDish($uuid, 'Buckwheat', ProductStatus::Active);

        $response = $this->getJson('/api/nutrition/dishes/search?name=borscht');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Borscht active')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'uuid', 'name', 'water_grams', 'total_grams', 'proteins', 'fats', 'carbs', 'calories', 'status'],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_search_dishes_fails_validation_without_name(): void
    {
        $response = $this->getJson('/api/nutrition/dishes/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_show_dish_returns_ingredients_for_active_dish(): void
    {
        $uuid = $this->makeUser();
        $dish = $this->makeDish($uuid, 'Борщ', ProductStatus::Active);
        $dish->ingredients()->create([
            'product_uuid' => null,
            'name' => 'Говядина',
            'proteins' => 26,
            'fats' => 15,
            'carbs' => 0,
            'grams' => 400,
            'position' => 0,
        ]);

        $response = $this->getJson("/api/nutrition/dishes/{$dish->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('uuid', $dish->uuid)
            ->assertJsonCount(1, 'ingredients')
            ->assertJsonPath('ingredients.0.name', 'Говядина');
    }

    public function test_show_dish_returns_404_for_non_active_or_missing_dish(): void
    {
        $uuid = $this->makeUser();
        $draft = $this->makeDish($uuid, 'Черновик', ProductStatus::Draft);

        $this->getJson("/api/nutrition/dishes/{$draft->uuid}")->assertStatus(404);
        $this->getJson('/api/nutrition/dishes/550e8400-e29b-41d4-a716-446655440000')->assertStatus(404);
    }
}
