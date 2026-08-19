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
 * @property float $weight
 * @property int $repeat_count
 * @property string|null $comment
 * @property int|null $client_id
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
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'repeat_count' => 'integer',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(ExerciseEntry::class, 'exercise_id');
    }
}
