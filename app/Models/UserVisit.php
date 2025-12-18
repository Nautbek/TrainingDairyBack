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

    /**
     * @var string[]
     */
    protected $casts = [
        'visit_date' => 'date',
        'visit_count' => 'integer',
    ];
}
