<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property int                             $id
 * @property string|null                     $visit_ip
 * @property string                          $visit_date
 * @property string|null                     $text
 * @property string|null                     $app
 * @property \Illuminate\Support\Carbon|null $created_at
 * @method static create(array $array)
 */
class UserFeedback extends Model
{
    protected $table = 'user_feedback';

    public $timestamps = false;

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
     * Использует Eloquent для ООП подхода
     *
     * @param string $visitIp
     * @param string $app
     * @param string $text
     * @return self
     */
    public static function saveFeedback(string $visitIp, string $app, string $text): self
    {
        // Используем прямой SQL запрос, чтобы гарантированно избежать timestamps
        $visitDate = now()->toDateString();
        
        $id = DB::selectOne(
            "INSERT INTO user_feedback (visit_ip, visit_date, app, text) 
             VALUES (?, ?, ?, ?) 
             RETURNING id",
            [$visitIp, $visitDate, $app, $text]
        )->id;
        
        return static::find($id);
    }
}
