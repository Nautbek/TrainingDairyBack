<?php

namespace Modules\Nutrition\Http\Controllers\Admin;

use Modules\Nutrition\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use Modules\Nutrition\Http\Requests\Admin\UpdateDishRequest;
use Modules\Nutrition\Models\Dish;
use Modules\Nutrition\Models\DishIngredient;
use Modules\Nutrition\Support\DishNutritionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NutritionDishController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $status = (int) $request->query('status', ProductStatus::Draft->value);
        $search = trim((string) $request->query('name', ''));

        $query = Dish::query()->with('ingredients')->where('status', $status);

        if ($search !== '') {
            $query->searchByName($search);
        }

        $dishes = $query
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = Dish::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn (int $total, int|string $status): array => [(int) $status => $total]);

        return view('nutrition::admin.dishes.index', [
            'dishes' => $dishes,
            'currentStatus' => $status,
            'counts' => $counts,
            'search' => $search,
        ]);
    }

    public function update(UpdateDishRequest $request, Dish $dish): RedirectResponse
    {
        if ((int) $request->input('_dish_id') !== $dish->id) {
            abort(404);
        }

        $validated = $request->safe()->except(['_dish_id', 'ingredients']);
        $ingredients = $request->validated('ingredients');
        $aggregates = DishNutritionCalculator::computeAggregates($ingredients, (float) $validated['water_grams']);

        DB::transaction(function () use ($dish, $validated, $ingredients, $aggregates): void {
            $dish->update([
                ...$validated,
                'total_grams' => $aggregates['total_grams'],
                'proteins' => $aggregates['proteins'],
                'fats' => $aggregates['fats'],
                'carbs' => $aggregates['carbs'],
                'calories' => $aggregates['calories'],
            ]);

            $dish->ingredients()->delete();

            $rows = [];
            foreach (array_values($ingredients) as $index => $ingredient) {
                $rows[] = [
                    'dish_id' => $dish->id,
                    'product_uuid' => $ingredient['product_uuid'] ?? null,
                    'name' => $ingredient['name'],
                    'proteins' => $ingredient['proteins'],
                    'fats' => $ingredient['fats'],
                    'carbs' => $ingredient['carbs'],
                    'grams' => $ingredient['grams'],
                    'position' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DishIngredient::query()->insert($rows);
        });

        return back()->with('saved_dish_id', $dish->id);
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        $dish->delete();

        return back();
    }

    public function approve(Dish $dish): RedirectResponse
    {
        if ($dish->status !== ProductStatus::Draft) {
            return back();
        }

        $dish->update(['status' => ProductStatus::Active]);

        return back();
    }

    public function decline(Dish $dish): RedirectResponse
    {
        if ($dish->status !== ProductStatus::Draft) {
            return back();
        }

        $dish->update(['status' => ProductStatus::Decline]);

        return back();
    }
}
