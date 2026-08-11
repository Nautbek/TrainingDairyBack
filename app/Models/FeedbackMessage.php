<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Одно сообщение внутри треда обращения.
 *
 * @property int    $id
 * @property int    $thread_id
 * @property string $sender "user" | "admin"
 * @property string $body
 */
class FeedbackMessage extends Model
{
    public const SENDER_USER = 'user';

    public const SENDER_ADMIN = 'admin';

    public $timestamps = false;

    protected $fillable = [
        'thread_id',
        'sender',
        'body',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(FeedbackThread::class, 'thread_id');
    }
}
