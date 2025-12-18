<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeedback extends Model
{
    protected $table = 'user_feedback';
    
    protected $fillable = [
        'visit_ip',
        'visit_date',
        'text',
        'app',
    ];
    
    protected $casts = [
        'visit_date' => 'date',
    ];
    
    /**
     * Сохранить отзыв пользователя
     * Аналог функции SaveUserFeedback из Go
     */
    public static function saveFeedback(string $visitIp, string $app, string $text): self
    {
        return static::create([
            'visit_ip' => $visitIp,
            'visit_date' => now()->format('Y-m-d'),
            'app' => $app,
            'text' => $text,
        ]);
    }
}

