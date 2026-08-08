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
        'is_hadir',
        'snack_diambil',
        'waktu_presensi',
    ];

    protected $casts = [
        'is_hadir' => 'boolean',
        'snack_diambil' => 'boolean',
        'waktu_presensi' => 'datetime',
    ];

    public function wisudawan()
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }
}
