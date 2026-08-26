<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'uuid',
        'ad_free_until',
        'discount_percent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ad_free_until' => 'datetime',
            'discount_percent' => 'integer',
        ];
    }

    /**
     * RegisterController fills email/password with placeholders ({uuid}@temp.local + a
     * random password) just to satisfy the NOT NULL/UNIQUE columns for anonymous uuid-only
     * accounts — this is the only way to tell "never attached a real email" apart from "has
     * a real login" without a separate boolean column.
     */
    public function hasEmailLogin(): bool
    {
        return $this->email !== $this->uuid.'@temp.local';
    }
}
