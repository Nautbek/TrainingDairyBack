<?php

namespace Modules\Nutrition\Http\Controllers\Api;

use Modules\Nutrition\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use Modules\Nutrition\Http\Requests\SearchDishRequest;
use Modules\Nutrition\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DishSearchController extends Controller
{
    private const PER_PAGE = 20;

    public function __invoke(SearchDishRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $dishes = Dish::query()
                ->select(['id', 'uuid', 'name', 'water_grams', 'total_grams', 'proteins', 'fats', 'carbs', 'calories', 'author_uuid', 'status'])
                ->searchByName($validated['name'])
                // Чужие блюда видны только после модерации (Active), но автор должен находить
                // и использовать своё блюдо сразу, пока оно ещё Draft — иначе им нельзя
                // залогировать только что созданный рецепт (см. симметрию с ProductSearchController,
                // который вообще не гейтит поиск по статусу).
                ->where(function ($q) use ($validated) {
                    $q->where('status', ProductStatus::Active);
                    if (! empty($validated['uuid'])) {
                        $q->orWhere('author_uuid', $validated['uuid']);
                    }
                })
                ->orderBy('name')
                ->paginate(self::PER_PAGE);

            return response()->json($dishes);
        } catch (\Exception $e) {
            Log::error('Error searching dishes: '.$e->getMessage());

            return response()->json([
                'error' => 'Internal Server Error',
            ], 500);
        }
    }
}
