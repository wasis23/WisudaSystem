<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'simpeg_id_sdm',
        'simpeg_username',
        'simpeg_nip',
        'nama_pegawai',
        'duty_role', // 'security' or 'receptionist'
        'is_active',
        'assigned_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
