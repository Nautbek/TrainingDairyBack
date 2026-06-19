<?php

namespace App\Models\TripSplit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $client_trip_id
 * @property string $trip_name
 * @property array<string, mixed> $summary
 * @property string|null $pdf_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Settlement extends Model
{
    protected $table = 'tripsplit_settlements';

    protected $fillable = [
        'uuid',
        'user_id',
        'client_trip_id',
        'trip_name',
        'summary',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'client_trip_id' => 'integer',
            'summary' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
