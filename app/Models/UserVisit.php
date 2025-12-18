<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserVisit extends Model
{
    protected $table = 'user_visits';
    
    protected $fillable = [
        'visit_ip',
        'visit_date',
        'visit_count',
        'app',
    ];
    
    protected $casts = [
        'visit_date' => 'date',
        'visit_count' => 'integer',
    ];
    
    /**
     * Увеличить счетчик посещений или создать новую запись
     * Аналог функции IncrementVisitCount из Go с ON CONFLICT
     */
    public static function incrementVisitCount(string $visitIp, string $app): void
    {
        $visitDate = now()->format('Y-m-d');
        
        // Использование DB facade для точного соответствия оригинальной логике ON CONFLICT
        DB::statement('
            INSERT INTO user_visits (visit_date, visit_count, visit_ip, app, created_at, updated_at)
            VALUES (?, 1, ?, ?, NOW(), NOW())
            ON CONFLICT (visit_date, visit_ip)
            DO UPDATE SET visit_count = user_visits.visit_count + 1, updated_at = NOW()
        ', [$visitDate, $visitIp, $app]);
    }
}

