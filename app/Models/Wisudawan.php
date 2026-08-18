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
        'tracer_status_pekerjaan',
        'tracer_nama_instansi',
        'tracer_jabatan',
        'tracer_pendapatan',
        'tracer_kesesuaian_prodi',
        'jumlah_tamu_tambahan',
        'tamu_tambahan_scanned',
        'status_kelulusan_simanta',
    ];

    protected $casts = [
        'is_tracer_study_filled' => 'boolean',
        'is_hadir' => 'boolean',
        'is_in_auditorium' => 'boolean',
        'waktu_presensi' => 'datetime',
        'waktu_presensi_venue' => 'datetime',
        'jumlah_tamu_tambahan' => 'integer',
        'tamu_tambahan_scanned' => 'integer',
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
}
