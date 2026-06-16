<?php

namespace Tests\Feature;

use App\Enums\Nutrition\ProductStatus;
use App\Models\Nutrition\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class NutritionProductSearchByBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_products_returns_paginated_results_by_barcode(): void
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
            'barcode' => '4601234567890',
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
            'barcode' => '4609999999999',
            'proteins' => 7,
            'fats' => 0.7,
            'carbs' => 78,
            'calories' => 350,
            'author_uuid' => $uuid,
            'status' => ProductStatus::Active,
        ]);

        $response = $this->getJson('/api/nutrition/products/search-by-barcode?barcode=4601234567890');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 20)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Овсянка геркулес')
            ->assertJsonPath('data.0.barcode', '4601234567890');
    }

    public function test_search_products_fails_validation_without_barcode(): void
    {
        $response = $this->getJson('/api/nutrition/products/search-by-barcode');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['barcode']);
    }
}
