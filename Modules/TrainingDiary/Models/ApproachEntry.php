<?php

namespace Modules\TrainingDiary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One set ("подход") synced from a device, belonging to an [[ExerciseEntry]].
 *
 * @property int $id
 * @property string $uuid
 * @property int $exercise_id
 * @property float|null $weight
 * @property int|null $repeat_count
 * @property string|null $comment
 * @property int|null $client_id
 * @property int|null $duration_seconds
 * @property float|null $distance_meters
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApproachEntry extends Model
{
    protected $table = 'training_diary_approaches';

    protected $fillable = [
        'uuid',
        'exercise_id',
        'weight',
        'repeat_count',
        'comment',
        'client_id',
        'duration_seconds',
        'distance_meters',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'repeat_count' => 'integer',
            'duration_seconds' => 'integer',
            'distance_meters' => 'float',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(ExerciseEntry::class, 'exercise_id');
    }
}
