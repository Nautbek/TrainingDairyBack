<?php

namespace App\Models\TripSplit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UsageBalance extends Model
{
    protected $table = 'tripsplit_usage_balances';

    protected $fillable = [
        'user_id',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
