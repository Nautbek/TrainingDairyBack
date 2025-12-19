<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserVisit extends Model
{
    protected $table = 'user_visits';
    
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'visit_ip',
        'visit_date',
        'visit_count',
        'app',
    ];
    
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'visit_date' => 'date',
        'visit_count' => 'integer',
    ];
    
    /**
     * Увеличить счетчик посещений или создать новую запись
     * Аналог функции IncrementVisitCount из Go с ON CONFLICT
     * Использует Eloquent методы для ООП подхода
     */
    public static function incrementVisitCount(string $visitIp, string $app): void
    {
        $visitDate = now()->toDateString();
        
        // Используем updateOrCreate для ООП подхода, но для атомарности инкремента
        // все равно используем DB::statement с ON CONFLICT для PostgreSQL
        DB::statement('
            INSERT INTO user_visits (visit_date, visit_count, visit_ip, app, created_at, updated_at)
            VALUES (?, 1, ?, ?, NOW(), NOW())
            ON CONFLICT (visit_date, visit_ip)
            DO UPDATE SET visit_count = user_visits.visit_count + 1, updated_at = NOW()
        ', [$visitDate, $visitIp, $app]);
    }
    
    /**
     * Альтернативный метод через Eloquent (менее эффективный, но чище ООП)
     * Используется только если не требуется атомарность инкремента
     */
    public static function incrementVisitCountEloquent(string $visitIp, string $app): void
    {
        $visitDate = now()->toDateString();
        
        $visit = static::firstOrNew(
            [
                'visit_date' => $visitDate,
                'visit_ip' => $visitIp,
            ],
            [
                'app' => $app,
                'visit_count' => 0,
            ]
        );
        
        if ($visit->exists) {
            $visit->increment('visit_count');
        } else {
            $visit->visit_count = 1;
            $visit->app = $app;
            $visit->save();
        }
    }
}
