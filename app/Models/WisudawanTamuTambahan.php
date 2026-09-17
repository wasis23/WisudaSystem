<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WisudawanTamuTambahan extends Model
{
    use HasFactory;

    protected $table = 'wisudawan_tamu_tambahan';

    protected $fillable = [
        'wisudawan_id',
        'nama_tamu',
        'hubungan',
        'qr_guest_token',
        'foto_keluar_gate',
        'is_hadir',
        'is_hadir_gate',
        'is_hadir_venue',
        'snack_diambil',
        'waktu_presensi',
        'waktu_presensi_gate',
        'waktu_presensi_venue',
        'waktu_keluar_gate',
    ];

    protected $casts = [
        'is_hadir' => 'boolean',
        'is_hadir_gate' => 'boolean',
        'is_hadir_venue' => 'boolean',
        'snack_diambil' => 'boolean',
        'waktu_presensi' => 'datetime',
        'waktu_presensi_gate' => 'datetime',
        'waktu_presensi_venue' => 'datetime',
        'waktu_keluar_gate' => 'datetime',
    ];

    public function wisudawan()
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id')->withoutGlobalScope('excludeDummy');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('excludeDummy', function ($builder) {
            $builder->whereHas('wisudawan', function ($query) {
                $query->where('wisudawan.is_dummy', false);
            });
        });
    }
}
