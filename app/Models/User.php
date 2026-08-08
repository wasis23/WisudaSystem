<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'program_studi_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function wisudawan()
    {
        return $this->hasOne(Wisudawan::class, 'user_id');
    }

    public function isAdminUtama(): bool
    {
        return $this->role === 'admin_utama';
    }

    public function isWisudawan(): bool
    {
        return $this->role === 'wisudawan';
    }

    public function isSecurity(): bool
    {
        return $this->role === 'security';
    }

    public function isReceptionist(): bool
    {
        return $this->role === 'receptionist';
    }
}
