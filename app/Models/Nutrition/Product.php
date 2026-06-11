<?php

namespace App\Models\Nutrition;

use App\Enums\Nutrition\ProductStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property float $proteins
 * @property float $fats
 * @property float $carbs
 * @property float $calories
 * @property string $author_uuid
 * @property ProductStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Product extends Model
{
    protected $table = 'nutrition_products';

    protected $fillable = [
        'name',
        'description',
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
            'proteins' => 'float',
            'fats' => 'float',
            'carbs' => 'float',
            'calories' => 'float',
            'status' => ProductStatus::class,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_uuid', 'uuid');
    }

    /**
     * @param  Builder<Product>  $query
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
