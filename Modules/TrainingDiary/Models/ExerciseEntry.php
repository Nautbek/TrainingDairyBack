<?php

namespace Modules\TrainingDiary\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One workout-log entry synced from a device: "user did exercise X at time T".
 * Mirrors the client's ExerciseHistory Room entity — [[title]] is denormalized
 * from the device's local exercise catalog (which never leaves the device).
 *
 * Also the source of truth for the cross-device sync feature: a second
 * device pulls these rows back via GET /api/training-diary/exercises and
 * find-or-creates a local Exercise by (title, measurement_type) to attach
 * them to, since the original local exercise id never leaves the device
 * that created it.
 *
 * @property int $id
 * @property string $uuid
 * @property string $user_uuid
 * @property string $title
 * @property string $measurement_type
 * @property Carbon $logged_at
 * @property int|null $client_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ExerciseEntry extends Model
{
    protected $table = 'training_diary_exercises';

    protected $fillable = [
        'uuid',
        'user_uuid',
        'title',
        'measurement_type',
        'logged_at',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    /**
     * @return HasMany<ApproachEntry, $this>
     */
    public function approaches(): HasMany
    {
        return $this->hasMany(ApproachEntry::class, 'exercise_id');
    }
}
