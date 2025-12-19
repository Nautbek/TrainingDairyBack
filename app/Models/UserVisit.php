<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @method static firstOrNew(array $array, array $array1)
 */
class UserVisit extends Model
{
    protected $table = 'user_visits';

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
        'visit_count',
        'app',
        'user_id',
    ];
    
    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * @var array<string, string>
     */
    protected $casts = [
        'visit_date' => 'date',
        'visit_count' => 'integer',
    ];
    
    /**
     * Увеличить счетчик посещений или создать новую запись
     * Использует прямой SQL для работы с таблицей без первичного ключа
     *
     * @param string $visitIp
     * @param string $app
     * @param int|null $userId ID пользователя (необязательно)
     * @return void
     */
    public static function incrementVisitCount(string $visitIp, string $app, ?int $userId = null): void
    {
        $visitDate = now()->toDateString();
        
        // Используем PostgreSQL ON CONFLICT для атомарного инкремента
        // Это работает даже без первичного ключа, используя уникальный индекс
        if ($userId !== null) {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app, visit_count, user_id)
                VALUES (?, ?, ?, 1, ?)
                ON CONFLICT (visit_date, visit_ip)
                DO UPDATE SET visit_count = user_visits.visit_count + 1
            ', [$visitDate, $visitIp, $app, $userId]);
        } else {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app, visit_count)
                VALUES (?, ?, ?, 1)
                ON CONFLICT (visit_date, visit_ip)
                DO UPDATE SET visit_count = user_visits.visit_count + 1
            ', [$visitDate, $visitIp, $app]);
        }
    }
}
