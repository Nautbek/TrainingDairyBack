<?php

namespace App\Http\Controllers\Api\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Nutrition\SearchProductRequest;
use App\Models\Nutrition\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProductSearchController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(SearchProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $products = Product::query()
                ->searchByName($validated['name'])
                ->orderBy('name')
                ->paginate(self::PER_PAGE);

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error searching products: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
