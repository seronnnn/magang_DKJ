<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /* ── Role helpers ── */

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isCollector(): bool
    {
        return $this->role === 'collector';
    }

    /* ── Relationships ── */

    public function collector()
    {
        return $this->hasOne(Collector::class, 'user_id');
    }
}