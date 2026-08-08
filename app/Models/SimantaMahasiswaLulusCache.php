<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimantaMahasiswaLulusCache extends Model
{
    protected $table = 'simanta_mahasiswa_lulus_cache';

    protected $fillable = [
        'nim', 'nama', 'judul_ta', 'kode_prodi', 'nama_prodi',
        'status_persetujuan', 'status_lulus',
        'tanggal_pengajuan', 'tanggal_pendaftaran_ta',
        'tanggal_seminar_proposal', 'tanggal_pendadaran',
        'tanggal_pengumpulan_laporan',
        'sync_tgl_dari', 'sync_tgl_sampai', 'synced_at',
        'wisudawan_id',
    ];

    protected $casts = [
        'status_lulus'               => 'boolean',
        'tanggal_pengajuan'          => 'date',
        'tanggal_pendaftaran_ta'     => 'date',
        'tanggal_seminar_proposal'   => 'date',
        'tanggal_pendadaran'         => 'date',
        'tanggal_pengumpulan_laporan'=> 'date',
        'sync_tgl_dari'              => 'date',
        'sync_tgl_sampai'            => 'date',
        'synced_at'                  => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function wisudawan(): BelongsTo
    {
        return $this->belongsTo(Wisudawan::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Filter: hanya yang status lulus
     */
    public function scopeLulus($query)
    {
        return $query->where('status_lulus', 1);
    }

    /**
     * Filter: sidang dalam rentang Oktober-Oktober
     * Default: Oktober tahun lalu - Oktober tahun ini
     */
    public function scopeRentangOktoberOktober($query, ?int $tahunAkhir = null)
    {
        $tahunAkhir  = $tahunAkhir  ?? (int) date('Y');
        $tahunAwal   = $tahunAkhir - 1;
        $tglDari     = "{$tahunAwal}-10-01";
        $tglSampai   = "{$tahunAkhir}-10-31";

        return $query->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);
    }

    /**
     * Filter: berdasarkan kode prodi
     */
    public function scopeProdi($query, string $kodeProdi)
    {
        return $query->where('kode_prodi', $kodeProdi);
    }

    /**
     * Filter: belum punya akun wisuda (nim tidak ada di tabel wisudawan)
     */
    public function scopeBelumTerdaftarWisuda($query)
    {
        return $query->whereNull('wisudawan_id');
    }

    /**
     * Helper: hitung tahun akademik Oktober-Oktober
     * Misal: sekarang Agustus 2026 → tahun akademik 2025-2026
     *         sekarang November 2026 → tahun akademik 2026-2027
     */
    public static function tahunAkademiksaat(): array
    {
        $bulan = (int) date('n');
        $tahun = (int) date('Y');

        // Oktober-Oktober: jika sekarang >= Oktober, rentang: tahun ini - tahun depan
        if ($bulan >= 10) {
            return ["{$tahun}-10-01", ($tahun + 1) . "-10-31"];
        } else {
            return [($tahun - 1) . "-10-01", "{$tahun}-10-31"];
        }
    }
}
