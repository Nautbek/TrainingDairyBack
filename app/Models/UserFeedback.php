<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        // Используем insertGetId() напрямую, чтобы не использовать timestamps
        // даже если $timestamps = false, create() может пытаться их вставить
        $id = static::insertGetId([
            'visit_ip' => $visitIp,
            'visit_date' => now()->toDateString(),
            'app' => $app,
            'text' => $text,
        ]);
        
        return static::find($id);
    }
}
