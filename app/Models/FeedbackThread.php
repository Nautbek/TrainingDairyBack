<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Тред обращения пользователя (фидбек-чат).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $app
 * @property string $status "open" | "closed"
 * @property string|null $visit_ip
 * @property string|null $device_info
 */
class FeedbackThread extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    /** Юзер удалил обращение сам, пока оно было открыто (см. FeedbackThreadController::destroy). */
    public const STATUS_DELETED_BY_USER = 'deleted_by_user';

    protected $fillable = [
        'user_id',
        'app',
        'status',
        'visit_ip',
        'device_info',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(FeedbackMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * @param  Builder<FeedbackThread>  $query
     * @return Builder<FeedbackThread>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }
}
