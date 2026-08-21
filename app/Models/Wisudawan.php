<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wisudawan extends Model
{
    use HasFactory;

    protected $table = 'wisudawan';

    protected $fillable = [
        'user_id',
        'periode_wisuda_id',
        'urutan_tampil',
        'program_studi_id',
        'nim',
        'nama_lengkap',
        'gelar',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'email',
        'nomor_hp',
        'alamat',
        'ipk',
        'predikat_kelulusan',
        'judul_ta',
        'dosen_pembimbing_1',
        'dosen_pembimbing_2',
        'dosen_penguji',
        'tanggal_lulus',
        'nama_ayah',
        'nama_ibu',
        'pas_foto',
        'qr_code_token',
        'status_verifikasi',
        'is_hadir',
        'is_in_auditorium',
        'waktu_presensi',
        'waktu_presensi_venue',
        'is_tracer_study_filled',
        'is_biodata_filled',
        'tracer_status_pekerjaan',
        'tracer_nama_instansi',
        'tracer_jabatan',
        'tracer_pendapatan',
        'tracer_kesesuaian_prodi',
        'tracer_study_data',
        'jumlah_tamu_tambahan',
        'tamu_tambahan_scanned',
        'status_kelulusan_simanta',
        'status_pembayaran_sikeu',
        'nominal_bayar_wisuda',
        'nominal_tagihan_wisuda',
        'jumlah_undangan_extra_sikeu',
        'tanggal_bayar_sikeu',
        'nomor_transaksi_sikeu',
        'sikeu_synced_at',
    ];

    protected $casts = [
        'is_tracer_study_filled' => 'boolean',
        'is_biodata_filled' => 'boolean',
        'tracer_study_data' => 'array',
        'is_hadir' => 'boolean',
        'is_in_auditorium' => 'boolean',
        'waktu_presensi' => 'datetime',
        'waktu_presensi_venue' => 'datetime',
        'tanggal_bayar_sikeu' => 'datetime',
        'sikeu_synced_at' => 'datetime',
        'jumlah_tamu_tambahan' => 'integer',
        'tamu_tambahan_scanned' => 'integer',
        'nominal_bayar_wisuda' => 'integer',
        'nominal_tagihan_wisuda' => 'integer',
        'jumlah_undangan_extra_sikeu' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function periodeWisuda()
    {
        return $this->belongsTo(PeriodeWisuda::class, 'periode_wisuda_id');
    }

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id');
    }

    public function tamuTambahan()
    {
        return $this->hasMany(WisudawanTamuTambahan::class, 'wisudawan_id');
    }

    public function tracerStudy()
    {
        return $this->hasOne(TracerStudy::class, 'wisudawan_id');
    }

    public function sikeuPayment()
    {
        return $this->hasOne(SikeuPaymentCache::class, 'nim', 'nim');
    }

    public function isLunas(): bool
    {
        return $this->status_pembayaran_sikeu === 'lunas';
    }

    public function scopeLunasSikeu($query)
    {
        return $query->where('status_pembayaran_sikeu', 'lunas');
    }

    public function scopeBelumLunasSikeu($query)
    {
        return $query->where(function ($q) {
            $q->where('status_pembayaran_sikeu', 'belum_lunas')
              ->orWhereNull('status_pembayaran_sikeu');
        });
    }
}
