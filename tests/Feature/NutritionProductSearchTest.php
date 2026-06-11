<?php

namespace Tests\Feature;

use App\Enums\Nutrition\ProductStatus;
use App\Models\Nutrition\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NutritionProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_products_returns_paginated_results_by_name(): void
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

        Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Овсянка геркулес',
            'description' => 'Каша',
            'proteins' => 12,
            'fats' => 6,
            'carbs' => 59,
            'calories' => 342,
            'author_uuid' => $uuid,
            'status' => ProductStatus::Active,
        ]);

        Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Рис белый',
            'proteins' => 7,
            'fats' => 0.7,
            'carbs' => 78,
            'calories' => 350,
            'author_uuid' => $uuid,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->getJson('/api/nutrition/products/search?name=овсян');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 20)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Овсянка геркулес')
            ->assertJsonPath('data.0.description', 'Каша')
            ->assertJsonPath('data.0.proteins', 12)
            ->assertJsonPath('data.0.fats', 6)
            ->assertJsonPath('data.0.carbs', 59)
            ->assertJsonPath('data.0.calories', 342)
            ->assertJsonPath('data.0.author_uuid', $uuid)
            ->assertJsonPath('data.0.status', ProductStatus::Active->value)
            ->assertJsonStructure([
                'data' => [
                    ['id', 'uuid', 'name', 'description', 'proteins', 'fats', 'carbs', 'calories', 'author_uuid', 'status', 'created_at', 'updated_at'],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_search_products_fails_validation_without_name(): void
    {
        $response = $this->getJson('/api/nutrition/products/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
