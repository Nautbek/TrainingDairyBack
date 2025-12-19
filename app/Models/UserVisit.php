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
    ];

    /**
     * Сохранить посещение
     * Использует прямой SQL для работы с таблицей без первичного ключа
     * Если запись с такой датой и IP уже существует, ничего не делает (ON CONFLICT DO NOTHING)
     *
     * @param string $visitIp
     * @param string $app
     * @param int|null $userId ID пользователя (необязательно)
     * @return void
     */
    public static function incrementVisitCount(string $visitIp, string $app, ?int $userId = null): void
    {
        $visitDate = now()->toDateString();
        
        // Используем PostgreSQL ON CONFLICT для атомарной вставки
        // Если запись уже существует, ничего не делаем (DO NOTHING)
        if ($userId !== null) {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app, user_id)
                VALUES (?, ?, ?, ?)
                ON CONFLICT (visit_date, visit_ip)
                DO NOTHING
            ', [$visitDate, $visitIp, $app, $userId]);
        } else {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app)
                VALUES (?, ?, ?)
                ON CONFLICT (visit_date, visit_ip)
                DO NOTHING
            ', [$visitDate, $visitIp, $app]);
        }
    }
}
