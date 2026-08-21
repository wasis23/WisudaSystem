<?php

namespace App\Services;

use App\Models\SikeuPaymentCache;
use App\Models\Wisudawan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SikeuIntegrationService
{
    /**
     * Get financial details and extra wisuda guest quota from cached data or SIKEU database
     */
    public function getExtraWisudaQuota(string $nim): array
    {
        $cleanNim = strtoupper(trim($nim));

        // 1. Cek dari SikeuPaymentCache terlebih dahulu
        $cached = SikeuPaymentCache::where('nim', $cleanNim)->first();
        if ($cached) {
            $isLunas = $cached->status_bayar === 'lunas';
            $extraGuests = (int)$cached->jumlah_undangan_extra;
            $totalGuests = 2 + $extraGuests;

            return [
                'has_paid_wisuda' => $isLunas,
                'status_bayar' => $cached->status_bayar,
                'total_bayar' => $cached->total_bayar,
                'total_tagihan' => $cached->total_tagihan ?: 2650000,
                'tambahan_wisuda_paid_quota' => $extraGuests,
                'total_allowed_guests' => $totalGuests,
                'snack_quota' => $totalGuests + 1,
                'tanggal_bayar' => $cached->tanggal_bayar ? $cached->tanggal_bayar->format('d/m/Y H:i') : null,
                'no_transaksi' => $cached->no_transaksi,
                'keterangan' => $cached->keterangan,
            ];
        }

        // 2. Jika belum ada di cache, lookup no_pend via SIAKAD lalu query riil ke SIKEU
        try {
            if (config('database.connections.sikeu') && config('database.connections.siakad')) {
                // Ambil no_pend mahasiswa dari SIAKAD
                $mhsPt = DB::connection('siakad')->table('viewMahasiswaPt')
                    ->where('nipd', $cleanNim)
                    ->first(['no_pend', 'nm_pd']);

                $noPend = $mhsPt->no_pend ?? null;

                if ($noPend) {
                    $payments = DB::connection('sikeu')->table('riwayat_bayar')
                        ->where('no_pend', $noPend)
                        ->whereNull('koreksi')
                        ->whereNull('deletedAt')
                        ->where(function ($q) {
                            $q->where('nama_biaya', 'LIKE', '%wisuda%')
                              ->orWhere('keterangan', 'LIKE', '%wisuda%');
                        })
                        ->get();

                    if ($payments->isNotEmpty()) {
                        $totalExtraGuests = 0;
                        $hasPaidWisuda = false;
                        $totalBayar = 0;
                        $totalTagihan = 2650000;
                        $lastPaymentDate = null;
                        $noTransaksi = null;
                        $keteranganList = [];

                        foreach ($payments as $payment) {
                            $keterangan = strtolower($payment->keterangan ?? '');
                            $namaBiaya = strtolower($payment->nama_biaya ?? '');
                            $nominal = (int)($payment->jumlah_bayar ?? 0);
                            $totalBayar += $nominal;
                            $lastPaymentDate = $payment->tanggal ?? $payment->createdAt ?? null;
                            $noTransaksi = $payment->kode ?? ('TX-' . $payment->id);
                            $keteranganList[] = $payment->nama_biaya . ($payment->keterangan ? ' (' . $payment->keterangan . ')' : '');

                            if (str_contains($namaBiaya, 'wisuda ta') || str_contains($namaBiaya, 'wisuda 20') || $nominal >= 2000000) {
                                $hasPaidWisuda = true;
                            }

                            if (str_contains($namaBiaya, 'tambahan') || str_contains($keterangan, 'tambahan') || str_contains($keterangan, 'undangan')) {
                                $totalExtraGuests += max(1, (int)($nominal / 125000));
                            }
                        }

                        if ($totalBayar >= 2000000) {
                            $hasPaidWisuda = true;
                        }

                        $totalQuota = 2 + $totalExtraGuests;

                        $this->saveToCache([
                            'nim' => $cleanNim,
                            'nama' => $mhsPt->nm_pd ?? null,
                            'status_bayar' => $hasPaidWisuda ? 'lunas' : 'belum_lunas',
                            'total_bayar' => $totalBayar,
                            'total_tagihan' => $totalTagihan,
                            'jumlah_undangan_extra' => $totalExtraGuests,
                            'total_kuota_undangan' => $totalQuota,
                            'snack_kuota' => $totalQuota + 1,
                            'tanggal_bayar' => $lastPaymentDate,
                            'no_transaksi' => $noTransaksi,
                            'keterangan' => implode(', ', array_filter($keteranganList)),
                        ]);

                        return [
                            'has_paid_wisuda' => $hasPaidWisuda,
                            'status_bayar' => $hasPaidWisuda ? 'lunas' : 'belum_lunas',
                            'total_bayar' => $totalBayar,
                            'total_tagihan' => $totalTagihan,
                            'tambahan_wisuda_paid_quota' => $totalExtraGuests,
                            'total_allowed_guests' => $totalQuota,
                            'snack_quota' => $totalQuota + 1,
                            'tanggal_bayar' => $lastPaymentDate,
                            'no_transaksi' => $noTransaksi,
                            'keterangan' => implode(', ', array_filter($keteranganList)),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::info('SIKEU direct DB query error: ' . $e->getMessage());
        }

        // 3. Cek langsung ke tabel Wisudawan jika ada status manual
        $wisudawan = Wisudawan::where('nim', $cleanNim)->first();
        if ($wisudawan && $wisudawan->status_pembayaran_sikeu === 'lunas') {
            $extra = (int)$wisudawan->jumlah_undangan_extra_sikeu;
            $total = 2 + $extra;
            return [
                'has_paid_wisuda' => true,
                'status_bayar' => 'lunas',
                'total_bayar' => $wisudawan->nominal_bayar_wisuda ?: 2650000,
                'total_tagihan' => $wisudawan->nominal_tagihan_wisuda ?: 2650000,
                'tambahan_wisuda_paid_quota' => $extra,
                'total_allowed_guests' => $total,
                'snack_quota' => $total + 1,
                'tanggal_bayar' => $wisudawan->tanggal_bayar_sikeu ? $wisudawan->tanggal_bayar_sikeu->format('d/m/Y H:i') : null,
                'no_transaksi' => $wisudawan->nomor_transaksi_sikeu,
                'keterangan' => 'Verifikasi Sistem Wisuda',
            ];
        }

        // Default response: belum lunas, standar 2 undangan
        return [
            'has_paid_wisuda' => false,
            'status_bayar' => 'belum_lunas',
            'total_bayar' => 0,
            'total_tagihan' => 2650000,
            'tambahan_wisuda_paid_quota' => 0,
            'total_allowed_guests' => 2,
            'snack_quota' => 3,
            'tanggal_bayar' => null,
            'no_transaksi' => null,
            'keterangan' => 'Belum ada catatan pembayaran wisuda di SIKEU.',
        ];
    }

    /**
     * Save/upsert record ke sikeu_payments_cache dan perbarui wisudawan jika cocok
     */
    public function saveToCache(array $data): SikeuPaymentCache
    {
        $nim = strtoupper(trim($data['nim']));
        $wisudawan = Wisudawan::where('nim', $nim)->first();

        $payload = [
            'nama' => $data['nama'] ?? $wisudawan?->nama_lengkap ?? null,
            'status_bayar' => $data['status_bayar'] ?? 'belum_lunas',
            'total_bayar' => $data['total_bayar'] ?? 0,
            'total_tagihan' => $data['total_tagihan'] ?? 2650000,
            'jumlah_undangan_extra' => $data['jumlah_undangan_extra'] ?? 0,
            'total_kuota_undangan' => $data['total_kuota_undangan'] ?? (2 + ($data['jumlah_undangan_extra'] ?? 0)),
            'snack_kuota' => $data['snack_kuota'] ?? (3 + ($data['jumlah_undangan_extra'] ?? 0)),
            'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
            'no_transaksi' => $data['no_transaksi'] ?? null,
            'keterangan' => $data['keterangan'] ?? null,
            'metode_bayar' => $data['metode_bayar'] ?? 'Transfer / Loket SIKEU',
            'synced_at' => now(),
            'wisudawan_id' => $wisudawan?->id,
        ];

        $cache = SikeuPaymentCache::updateOrCreate(['nim' => $nim], $payload);

        // Sinkronkan langsung ke tabel wisudawan
        if ($wisudawan) {
            $wisudawan->update([
                'status_pembayaran_sikeu' => $cache->status_bayar,
                'nominal_bayar_wisuda' => $cache->total_bayar,
                'nominal_tagihan_wisuda' => $cache->total_tagihan,
                'jumlah_undangan_extra_sikeu' => $cache->jumlah_undangan_extra,
                'jumlah_tamu_tambahan' => $cache->total_kuota_undangan,
                'tanggal_bayar_sikeu' => $cache->tanggal_bayar,
                'nomor_transaksi_sikeu' => $cache->no_transaksi,
                'sikeu_synced_at' => now(),
            ]);
        }

        return $cache;
    }

    /**
     * Run full sync from SIKEU database (READ-ONLY)
     */
    public function syncAll(): array
    {
        $syncedCount = 0;
        $insertedCount = 0;
        $updatedCount = 0;

        // Ambil seluruh wisudawan terdaftar
        $wisudawans = Wisudawan::all();

        try {
            // 1. Ambil mapping NIM -> no_pend & Nama dari SIAKAD
            $nims = $wisudawans->pluck('nim')->toArray();
            $siakadStudents = DB::connection('siakad')->table('viewMahasiswaPt')
                ->whereIn('nipd', $nims)
                ->whereNotNull('no_pend')
                ->get(['nipd as nim', 'no_pend', 'nm_pd as nama'])
                ->keyBy(fn($item) => strtoupper(trim($item->nim)));

            $noPendToNim = [];
            foreach ($siakadStudents as $s) {
                $noPendToNim[trim($s->no_pend)] = strtoupper(trim($s->nim));
            }

            // 2. Query pembayaran wisuda riil dari SIKEU berdasarkan no_pend (hanya data aktif yang belum/tidak dikoreksi)
            $noPends = array_keys($noPendToNim);
            $paymentsByNoPend = DB::connection('sikeu')->table('riwayat_bayar')
                ->whereIn('no_pend', $noPends)
                ->whereNull('koreksi')
                ->whereNull('deletedAt')
                ->where(function ($q) {
                    $q->where('nama_biaya', 'LIKE', '%wisuda%')
                      ->orWhere('keterangan', 'LIKE', '%wisuda%');
                })
                ->orderBy('tanggal', 'desc')
                ->get()
                ->groupBy('no_pend');

            foreach ($wisudawans as $w) {
                $nim = strtoupper(trim($w->nim));
                $studentInfo = $siakadStudents->get($nim);
                $noPend = $studentInfo->no_pend ?? null;
                $payments = $noPend ? ($paymentsByNoPend->get($noPend) ?? collect()) : collect();

                $totalBayar = 0;
                $totalExtra = 0;
                $totalTagihanPokok = 2650000;
                $lastDate = null;
                $noTx = null;
                $kets = [];

                if ($payments->isNotEmpty()) {
                    foreach ($payments as $p) {
                        $ket = strtolower($p->keterangan ?? '');
                        $namaBiaya = strtolower($p->nama_biaya ?? '');
                        $jml = (int)($p->jumlah_bayar ?? 0);
                        $totalBayar += $jml;
                        $lastDate = $p->tanggal ?? $p->createdAt ?? $lastDate;
                        $noTx = $p->kode ?? $noTx;
                        $kets[] = $p->nama_biaya . ($p->keterangan ? ' (' . $p->keterangan . ')' : '');

                        if (str_contains($namaBiaya, 'tambahan') || str_contains($ket, 'tambahan') || str_contains($ket, 'undangan')) {
                            $totalExtra += max(1, (int)($jml / 125000));
                        } else {
                            if (!empty($p->tagihan) && (int)$p->tagihan > 0) {
                                $totalTagihanPokok = max($totalTagihanPokok, (int)$p->tagihan);
                            }
                        }
                    }
                }

                // Status lunas HANYA jika total pembayaran mencukupi tagihan wisuda penuh (minimal 2.500.000 / sesuai tagihan)
                $isLunas = ($totalBayar >= $totalTagihanPokok || $totalBayar >= 2500000);

                $exists = SikeuPaymentCache::where('nim', $nim)->exists();
                $this->saveToCache([
                    'nim' => $nim,
                    'nama' => $studentInfo->nama ?? $w->nama_lengkap,
                    'status_bayar' => $isLunas ? 'lunas' : 'belum_lunas',
                    'total_bayar' => $totalBayar,
                    'total_tagihan' => $totalTagihanPokok + ($totalExtra * 125000),
                    'jumlah_undangan_extra' => $totalExtra,
                    'total_kuota_undangan' => 2 + $totalExtra,
                    'snack_kuota' => 3 + $totalExtra,
                    'tanggal_bayar' => $lastDate,
                    'no_transaksi' => $noTx,
                    'keterangan' => !empty($kets) ? implode(', ', array_filter($kets)) : 'Belum ada catatan pembayaran wisuda di SIKEU.',
                ]);

                if ($exists) {
                    $updatedCount++;
                } else {
                    $insertedCount++;
                }
                $syncedCount++;
            }
        } catch (\Exception $e) {
            Log::error('Error processing SIKEU payments: ' . $e->getMessage());
        }

        return [
            'records_fetched' => $syncedCount,
            'records_inserted' => $insertedCount,
            'records_updated' => $updatedCount,
        ];
    }

    /**
     * Admin manual toggle or update payment status
     */
    public function setPaymentStatus(string $nim, string $status, int $extraGuests = 0, ?int $nominal = null, ?string $keterangan = null): SikeuPaymentCache
    {
        $cleanNim = trim($nim);
        $isLunas = $status === 'lunas';
        $totalBayar = $nominal !== null ? $nominal : ($isLunas ? 2000000 + ($extraGuests * 150000) : 0);

        return $this->saveToCache([
            'nim' => $cleanNim,
            'status_bayar' => $status,
            'total_bayar' => $totalBayar,
            'total_tagihan' => 2000000 + ($extraGuests * 150000),
            'jumlah_undangan_extra' => $extraGuests,
            'total_kuota_undangan' => 2 + $extraGuests,
            'snack_kuota' => 3 + $extraGuests,
            'tanggal_bayar' => $isLunas ? now() : null,
            'no_transaksi' => $isLunas ? 'TX-MANUAL-' . strtoupper(substr(md5($cleanNim . time()), 0, 8)) : null,
            'keterangan' => $keterangan ?: ($isLunas ? 'Diverifikasi Lunas oleh Admin Keuangan' : 'Status Pembayaran Direset'),
        ]);
    }
}
