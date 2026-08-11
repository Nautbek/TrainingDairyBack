<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Nutrition\Http\Requests\SearchProductByBarcodeRequest;
use Modules\Nutrition\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ProductSearchByBarcodeController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(SearchProductByBarcodeRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $products = Product::query()
                ->where('barcode', $validated['barcode'])
                ->orderBy('name')
                ->paginate(self::PER_PAGE);

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error searching products by barcode: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
