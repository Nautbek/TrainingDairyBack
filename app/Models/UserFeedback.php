<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property string|null                     $visit_ip
 * @property string                          $visit_date
 * @property string|null                     $text
 * @property string|null                     $app
 * @method static create(array $array)
 */
class UserFeedback extends Model
{
    protected $table = 'user_feedback';

    public $timestamps = false;
    
    // Указываем, что в таблице нет первичного ключа id
    public $incrementing = false;
    protected $primaryKey = null;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'visit_ip',
        'visit_date',
        'text',
        'app',
        'user_id',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'visit_date' => 'date',
    ];

    /**
     * Сохранить отзыв пользователя
     * Аналог функции SaveUserFeedback из Go
     * Использует прямой SQL для избежания проблем с timestamps и отсутствующими колонками
     *
     * @param string $visitIp
     * @param string $app
     * @param string $text
     * @param int|null $uuid
     * @return void
     */
    public static function saveFeedback(string $visitIp, string $app, string $text, ?int $uuid = null): void
    {
        if ($uuid) {
            $user = DB::table('users')->where('uuid', $uuid)->first();
            $userId = $user?->id;
        }

        // Используем прямой SQL запрос для вставки данных
        $visitDate = now()->toDateString();
        
        if ($userId !== null) {
            DB::insert(
                "INSERT INTO user_feedback (visit_ip, visit_date, app, text, user_id) 
                 VALUES (?, ?, ?, ?, ?)",
                [$visitIp, $visitDate, $app, $text, $userId]
            );
        } else {
            DB::insert(
                "INSERT INTO user_feedback (visit_ip, visit_date, app, text) 
                 VALUES (?, ?, ?, ?)",
                [$visitIp, $visitDate, $app, $text]
            );
        }
    }
}
