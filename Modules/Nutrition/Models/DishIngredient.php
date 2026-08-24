<?php

namespace Modules\Nutrition\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One ingredient row of a Dish, snapshotted at save time (name/proteins/fats/carbs/grams) so the
 * dish stays servable and correct even if the source product later changes or is unavailable to
 * whoever is viewing the dish. $product_uuid is provenance only and is never joined/resolved.
 *
 * @property int $id
 * @property int $dish_id
 * @property string|null $product_uuid
 * @property string $name
 * @property float $proteins
 * @property float $fats
 * @property float $carbs
 * @property float $grams
 * @property int $position
 */
class DishIngredient extends Model
{
    protected $table = 'nutrition_dish_ingredients';

    protected $fillable = [
        'dish_id',
        'product_uuid',
        'name',
        'proteins',
        'fats',
        'carbs',
        'grams',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'proteins' => 'float',
            'fats' => 'float',
            'carbs' => 'float',
            'grams' => 'float',
            'position' => 'integer',
        ];
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
