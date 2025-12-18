<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array<string, mixed> $array)
 */
class UserFeedback extends Model
{
    protected $table = 'user_feedback';
    
    protected $fillable = [
        'visit_ip',
        'visit_date',
        'text',
        'app',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'visit_date' => 'date',
    ];
}
