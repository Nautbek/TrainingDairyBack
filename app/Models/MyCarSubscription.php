<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon|null $premium_until
 */
class MyCarSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'premium_until',
    ];

    protected function casts(): array
    {
        return [
            'premium_until' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
