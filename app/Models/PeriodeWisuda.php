<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeWisuda extends Model
{
    use HasFactory;

    protected $table = 'periode_wisuda';

    protected $fillable = [
        'nama_periode',
        'nomor_periode',
        'tahun_akademik',
        'tanggal_pelaksanaan',
        'kuota_peserta',
        'tanggal_buka_pendaftaran',
        'tanggal_tutup_pendaftaran',
        'is_active',
    ];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_buka_pendaftaran' => 'datetime',
        'tanggal_tutup_pendaftaran' => 'datetime',
        'is_active' => 'boolean',
        'nomor_periode' => 'integer',
        'kuota_peserta' => 'integer',
    ];

    public function wisudawan(): HasMany
    {
        return $this->hasMany(Wisudawan::class, 'periode_wisuda_id');
    }

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
