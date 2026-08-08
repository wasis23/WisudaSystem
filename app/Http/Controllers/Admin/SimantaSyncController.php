<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimantaMahasiswaLulusCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SimantaSyncController extends Controller
{
    /**
     * Halaman status sync SIMANTA + daftar mahasiswa lulus yang sudah di-cache.
     */
    public function index(Request $request)
    {
        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();

        $tglDari   = $request->input('tgl_dari',   $defaultDari);
        $tglSampai = $request->input('tgl_sampai', $defaultSampai);

        $query = SimantaMahasiswaLulusCache::query()
            ->lulus()
            ->orderBy('tanggal_pendadaran', 'desc')
            ->orderBy('nama');

        // Filter rentang jika diberikan
        if ($tglDari && $tglSampai) {
            $query->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);
        }

        $stats = [
            'total_cache'         => SimantaMahasiswaLulusCache::count(),
            'total_lulus'         => SimantaMahasiswaLulusCache::lulus()->count(),
            'total_periode_ini'   => $query->count(),
            'belum_daftar_wisuda' => SimantaMahasiswaLulusCache::lulus()->belumTerdaftarWisuda()->count(),
            'last_sync'           => SimantaMahasiswaLulusCache::max('synced_at'),
        ];

        $recentLogs = DB::table('external_sync_logs')
            ->where('source', 'simanta')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/SimantaSync', [
            'stats'      => $stats,
            'recentLogs' => $recentLogs,
            'mahasiswa'  => $query->paginate(50)->withQueryString(),
            'filter'     => ['tgl_dari' => $tglDari, 'tgl_sampai' => $tglSampai],
            'default_range' => ['dari' => $defaultDari, 'sampai' => $defaultSampai],
        ]);
    }

    /**
     * Trigger sync: pull data mahasiswa lulus dari SIMANTA API.
     *
     * POST params:
     *   tgl_dari   : default = Oktober tahun lalu
     *   tgl_sampai : default = Oktober tahun ini
     *   status_lulus: default = 1 (hanya yang lulus)
     *   prodi       : kosong = semua
     */
    public function sync(Request $request)
    {
        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();

        $tglDari   = $request->input('tgl_dari',    $defaultDari);
        $tglSampai = $request->input('tgl_sampai',  $defaultSampai);
        $statusLulus = $request->input('status_lulus', '1');
        $prodi     = $request->input('prodi', '');

        $apiUrl = rtrim(env('SIMANTA_API_URL', ''), '/');
        $apiKey = env('SIMANTA_API_KEY', '');

        $logData = [
            'source'           => 'simanta',
            'action'           => 'sync_lulus',
            'records_fetched'  => 0,
            'records_inserted' => 0,
            'records_updated'  => 0,
            'status'           => 'failed',
            'notes'            => null,
            'filter_params'    => json_encode([
                'tgl_dari' => $tglDari, 'tgl_sampai' => $tglSampai,
                'status_lulus' => $statusLulus, 'prodi' => $prodi,
            ]),
            'triggered_by' => auth()->id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        try {
            if (empty($apiUrl)) {
                throw new \Exception('SIMANTA_API_URL belum dikonfigurasi di .env');
            }

            $params = [
                'action'       => 'mahasiswa_lulus',
                'tgl_dari'     => $tglDari,
                'tgl_sampai'   => $tglSampai,
                'status_lulus' => $statusLulus,
            ];
            if (!empty($prodi)) $params['prodi'] = $prodi;

            $response = Http::timeout(90)
                ->withHeaders(['X-API-KEY' => $apiKey])
                ->get($apiUrl, $params);

            if (!$response->successful()) {
                throw new \Exception("SIMANTA API error: HTTP {$response->status()} — {$response->body()}");
            }

            $json = $response->json();
            if (($json['status'] ?? '') !== 'success') {
                throw new \Exception("SIMANTA API error: " . ($json['message'] ?? 'Unknown'));
            }

            $dataList = $json['data'] ?? [];
            $logData['records_fetched'] = count($dataList);
            $syncedAt = now();
            $inserted = 0;
            $updated  = 0;

            foreach ($dataList as $item) {
                $nim = $item['nim'] ?? null;
                if (empty($nim)) continue;

                $payload = [
                    'nim'                         => $nim,
                    'nama'                        => $item['nama'] ?? null,
                    'judul_ta'                    => $item['judul_ta'] ?? null,
                    'kode_prodi'                  => $item['kode_prodi'] ?? null,
                    'nama_prodi'                  => $item['nama_prodi'] ?? null,
                    'status_persetujuan'          => $item['status_persetujuan'] ?? null,
                    'status_lulus'                => (int) ($item['status_lulus'] ?? 0),
                    'tanggal_pengajuan'           => $this->safeDate($item['tanggal_pengajuan'] ?? null),
                    'tanggal_pendaftaran_ta'      => $this->safeDate($item['tanggal_pendaftaran_ta'] ?? null),
                    'tanggal_seminar_proposal'    => $this->safeDate($item['tanggal_seminar_proposal'] ?? null),
                    'tanggal_pendadaran'          => $this->safeDate($item['tanggal_pendadaran'] ?? null),
                    'tanggal_pengumpulan_laporan' => $this->safeDate($item['tanggal_pengumpulan_laporan'] ?? null),
                    'sync_tgl_dari'               => $tglDari,
                    'sync_tgl_sampai'             => $tglSampai,
                    'synced_at'                   => $syncedAt,
                    'updated_at'                  => $syncedAt,
                ];

                $existing = SimantaMahasiswaLulusCache::where('nim', $nim)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $payload['created_at'] = $syncedAt;
                    SimantaMahasiswaLulusCache::create($payload);
                    $inserted++;
                }
            }

            $logData['records_inserted'] = $inserted;
            $logData['records_updated']  = $updated;
            $logData['status']           = 'success';
            $logData['notes']            = "Sync SIMANTA: {$tglDari} s/d {$tglSampai}. {$inserted} ditambah, {$updated} diperbarui.";

            DB::table('external_sync_logs')->insert($logData);

            return back()->with('success',
                "✅ Sync SIMANTA berhasil! Rentang: {$tglDari} — {$tglSampai}. " .
                "{$inserted} mahasiswa baru, {$updated} diperbarui."
            );

        } catch (\Exception $e) {
            Log::error('SimantaSync error: ' . $e->getMessage());
            $logData['notes'] = $e->getMessage();
            DB::table('external_sync_logs')->insert($logData);

            return back()->with('error', '❌ Sync SIMANTA gagal: ' . $e->getMessage());
        }
    }

    /**
     * API: ambil daftar mahasiswa lulus dari cache (untuk wisuda).
     * GET /admin/sync-simanta/data?tgl_dari=&tgl_sampai=&prodi=&belum_daftar=1
     */
    public function data(Request $request)
    {
        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();

        $tglDari   = $request->input('tgl_dari',   $defaultDari);
        $tglSampai = $request->input('tgl_sampai', $defaultSampai);
        $prodi     = $request->input('prodi', '');
        $belumDaftar = $request->boolean('belum_daftar');

        $query = SimantaMahasiswaLulusCache::lulus()
            ->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);

        if (!empty($prodi)) $query->prodi($prodi);
        if ($belumDaftar)   $query->belumTerdaftarWisuda();

        $data = $query->orderBy('nama')->get();

        return response()->json([
            'status'    => 'success',
            'filter'    => ['tgl_dari' => $tglDari, 'tgl_sampai' => $tglSampai, 'prodi' => $prodi],
            'total'     => $data->count(),
            'data'      => $data,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function safeDate(?string $value): ?string
    {
        if (empty($value) || $value === '0000-00-00') return null;
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
}
