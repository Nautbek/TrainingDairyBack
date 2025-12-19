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
     * Уникальный индекс на (visit_date, visit_ip) удален - можно создавать несколько записей
     *
     * @param string $visitIp
     * @param string $app
     * @param int|null $userId ID пользователя (необязательно)
     * @return void
     */
    public static function incrementVisitCount(string $visitIp, string $app, ?int $userId = null): void
    {
        $visitDate = now()->toDateString();
        
        // Просто вставляем запись - уникального индекса больше нет
        if ($userId !== null) {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app, user_id)
                VALUES (?, ?, ?, ?)
            ', [$visitDate, $visitIp, $app, $userId]);
        } else {
            DB::statement('
                INSERT INTO user_visits (visit_date, visit_ip, app)
                VALUES (?, ?, ?)
            ', [$visitDate, $visitIp, $app]);
        }
    }
}
