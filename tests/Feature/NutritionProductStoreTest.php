<?php

namespace Tests\Feature;

use App\Enums\Nutrition\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionProductStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_creates_draft_for_existing_user(): void
    {
        $registerResponse = $this->postJson('/api/register');
        $uuid = $registerResponse->json('uuid');

        $response = $this->postJson('/api/nutrition/products', [
            'uuid' => $uuid,
            'name' => 'Овсянка',
            'description' => 'Геркулес',
            'proteins' => 12.3,
            'fats' => 6.1,
            'carbs' => 59.5,
            'calories' => 342,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => ProductStatus::Draft->value,
            ]);

        $this->assertDatabaseHas('nutrition_products', [
            'name' => 'Овсянка',
            'author_uuid' => $uuid,
            'status' => ProductStatus::Draft->value,
        ]);
    }

    public function test_store_product_accepts_uuid_from_header(): void
    {
        $uuid = $this->postJson('/api/register')->json('uuid');

        $response = $this->postJson(
            '/api/nutrition/products',
            [
                'name' => 'Рис',
                'proteins' => 7,
                'fats' => 0.7,
                'carbs' => 78,
                'calories' => 350,
            ],
            ['X-User-UUID' => $uuid]
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('nutrition_products', [
            'name' => 'Рис',
            'author_uuid' => $uuid,
        ]);
    }

    public function test_store_product_returns_unauthorized_for_unknown_user(): void
    {
        $response = $this->postJson('/api/nutrition/products', [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'name' => 'Овсянка',
            'proteins' => 12,
            'fats' => 6,
            'carbs' => 59,
            'calories' => 342,
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_store_product_fails_validation_without_required_fields(): void
    {
        $response = $this->postJson('/api/nutrition/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['uuid', 'name', 'proteins', 'fats', 'carbs', 'calories']);
    }
}
