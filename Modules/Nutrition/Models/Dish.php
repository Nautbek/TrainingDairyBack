<?php

namespace Modules\Nutrition\Models;

use Modules\Nutrition\Enums\ProductStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A public "dish" (recipe) — an author's own ingredients combined with water into a single
 * per-100g nutrition-facts card, moderated the same way a Product is. See DishIngredient for
 * why ingredients are stored as self-contained snapshots rather than live product references.
 *
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property float $water_grams
 * @property float $total_grams
 * @property float $proteins
 * @property float $fats
 * @property float $carbs
 * @property float $calories
 * @property string $author_uuid
 * @property ProductStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Dish extends Model
{
    protected $table = 'nutrition_dishes';

    protected $fillable = [
        'uuid',
        'name',
        'water_grams',
        'total_grams',
        'proteins',
        'fats',
        'carbs',
        'calories',
        'author_uuid',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'water_grams' => 'float',
            'total_grams' => 'float',
            'proteins' => 'float',
            'fats' => 'float',
            'carbs' => 'float',
            'calories' => 'float',
            'status' => ProductStatus::class,
        ];
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(DishIngredient::class)->orderBy('position');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_uuid', 'uuid');
    }

    /**
     * @param  Builder<Dish>  $query
     */
    public function scopeSearchByName(Builder $query, string $name): void
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $name);
        $pattern = '%'.$escaped.'%';

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            $query->where('name', 'ilike', $pattern);
        } else {
            $query->whereRaw("LOWER(name) LIKE LOWER(?) ESCAPE '\\'", [$pattern]);
        }
    }
}
