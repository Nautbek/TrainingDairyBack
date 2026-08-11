<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use Modules\Nutrition\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use Modules\Nutrition\Http\Requests\StoreProductRequest;
use Modules\Nutrition\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductStoreController extends Controller
{
    public function __invoke(StoreProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! User::query()->where('uuid', $validated['uuid'])->exists()) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }

            do {
                $productUuid = (string) Str::uuid();
            } while (Product::query()->where('uuid', $productUuid)->exists());

            $product = Product::query()->create([
                'uuid' => $productUuid,
                'name' => $validated['name'],
                'barcode' => $validated['barcode'] ?? null,
                'description' => $validated['description'] ?? null,
                'proteins' => $validated['proteins'],
                'fats' => $validated['fats'],
                'carbs' => $validated['carbs'],
                'calories' => $validated['calories'],
                'author_uuid' => $validated['uuid'],
                'status' => ProductStatus::Draft,
            ]);

            return response()->json([
                'id' => $product->id,
                'uuid' => $product->uuid,
                'status' => $product->status->value,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error saving product: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
