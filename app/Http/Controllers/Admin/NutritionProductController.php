<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Nutrition\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Nutrition\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NutritionProductController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $status = (int) $request->query('status', ProductStatus::Draft->value);
        $search = trim((string) $request->query('name', ''));

        $query = Product::query()->where('status', $status);

        if ($search !== '') {
            $query->searchByName($search);
        }

        $products = $query
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = Product::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->mapWithKeys(fn (int $total, int|string $status): array => [(int) $status => $total]);

        return view('admin.nutrition.products.index', [
            'products' => $products,
            'currentStatus' => $status,
            'counts' => $counts,
            'search' => $search,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back();
    }

    public function approve(Product $product): RedirectResponse
    {
        if ($product->status !== ProductStatus::Draft) {
            return back();
        }

        $product->update(['status' => ProductStatus::Active]);

        return back();
    }

    public function decline(Product $product): RedirectResponse
    {
        if ($product->status !== ProductStatus::Draft) {
            return back();
        }

        $product->update(['status' => ProductStatus::Decline]);

        return back();
    }
}
