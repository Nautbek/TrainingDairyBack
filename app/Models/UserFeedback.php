<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
    ];
    
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
     * @return void
     */
    public static function saveFeedback(string $visitIp, string $app, string $text): void
    {
        // Используем прямой SQL запрос для вставки данных
        $visitDate = now()->toDateString();
        
        DB::insert(
            "INSERT INTO user_feedback (visit_ip, visit_date, app, text) 
             VALUES (?, ?, ?, ?)",
            [$visitIp, $visitDate, $app, $text]
        );
    }
}
