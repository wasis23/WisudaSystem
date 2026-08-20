<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TracerStudy extends Model
{
    use HasFactory;

    protected $table = 'tracer_studies';

    protected $fillable = [
        'wisudawan_id',
        'user_id',
        'nim',
        'nama_lengkap',
        'email',
        'no_whatsapp',
        'prodi',
        'prodi_lainnya',
        'jenis_kelas',
        'alamat_lengkap',
        'status_saat_ini',
        'status_lainnya',
        'tempat_bekerja',
        'gaji_per_bulan',
        'keselarasan_pekerjaan',
        'kesesuaian_pendidikan',
        'waktu_tunggu',
        'alamat_tempat_kerja',
        'jenis_instansi',
        'jenis_instansi_lainnya',
        'nama_perusahaan',
        'posisi_jabatan',
        'posisi_lainnya',
        'cakupan_tempat_kerja',
        'tingkat_tempat_kerja_lainnya',
        'nama_usaha',
        'gaji_usaha',
        'keselarasan_usaha',
        'studi_lanjut',
        'kampus_studi_lanjut',
        'alamat_kampus_studi_lanjut',
        'sumber_dana',
        'sumber_dana_lainnya',
        'lulus_etika',
        'lulus_keahlian_ilmu',
        'lulus_bahasa_inggris',
        'lulus_teknologi_informasi',
        'lulus_komunikasi',
        'lulus_kerjasama_tim',
        'lulus_pengembangan_diri',
        'kerja_etika',
        'kerja_keahlian_ilmu',
        'kerja_bahasa_inggris',
        'kerja_teknologi_informasi',
        'kerja_komunikasi',
        'kerja_kerjasama_tim',
        'kerja_pengembangan_diri',
        'metode_perkuliahan',
        'metode_demonstrasi',
        'metode_proyek_riset',
        'metode_magang',
        'metode_praktikum',
        'metode_kerja_lapangan',
        'metode_diskusi',
        'kepuasan_layanan',
        'saran_masukan',
    ];

    public function wisudawan()
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
