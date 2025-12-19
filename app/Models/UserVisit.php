<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static firstOrNew(array $array, array $array1)
 */
class UserVisit extends Model
{
    protected $table = 'user_visits';

    public $timestamps = false;

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
     * ООП-реализация через Eloquent без сырого SQL.
     */
    public static function incrementVisitCount(string $visitIp, string $app): void
    {
        $visitDate = now()->toDateString();
        
        // Ищем запись по дате и IP, если нет — создаём
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
        
        // Инкрементируем счётчик и сохраняем модель
        $visit->visit_count = ($visit->visit_count ?? 0) + 1;
        $visit->save();
    }
}
