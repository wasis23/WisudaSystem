<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SimpegEmployeeCache;
use App\Models\ExternalSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SimpegSyncController extends Controller
{
    /**
     * Halaman status sync SIMPEG.
     */
    public function index(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $lastSync = SimpegEmployeeCache::max('synced_at');
        $total    = SimpegEmployeeCache::count();
        $dosen    = SimpegEmployeeCache::dosen()->count();
        $tendik   = SimpegEmployeeCache::tendik()->count();

        $recentLogs = DB::table('external_sync_logs')
            ->where('source', 'simpeg')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $query = SimpegEmployeeCache::query();

        if (!empty($q)) {
            $query->search($q);
        }

        if ($status === 'dosen') {
            $query->dosen();
        } elseif (in_array($status, ['tendik', 'pegawai'])) {
            $query->tendik();
        }

        return Inertia::render('Admin/SimpegSync', [
            'stats' => [
                'last_sync'    => $lastSync,
                'total'        => $total,
                'dosen'        => $dosen,
                'tendik'       => $tendik,
            ],
            'recentLogs' => $recentLogs,
            'employees'  => $query->orderBy('nama')->paginate(50)->withQueryString(),
            'filters'    => [
                'q'      => $q,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Jalankan sync: pull semua pegawai dari SIMPEG dan simpan ke cache.
     * Endpoint SIMPEG: GET /api/employees/all
     */
    public function sync(Request $request)
    {
        $apiUrl = rtrim(env('SIMPEG_EMPLOYEES_ALL_URL', env('SIMPEG_API_URL', '')), '/');
        // Jika masih pakai URL lama (verify-login), ganti ke /employees/all
        if (str_contains($apiUrl, 'verify-login')) {
            $apiUrl = str_replace('verify-login', 'employees/all', $apiUrl);
        }

        $apiKey   = env('SIMPEG_API_KEY', '');
        $statusFil = $request->input('status', ''); // kosong = semua

        $logData = [
            'source'       => 'simpeg',
            'action'       => 'sync_all',
            'records_fetched'  => 0,
            'records_inserted' => 0,
            'records_updated'  => 0,
            'status'       => 'failed',
            'notes'        => null,
            'filter_params'=> json_encode(['status' => $statusFil]),
            'triggered_by' => auth()->id(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        try {
            $params = [];
            if (!empty($statusFil)) $params['status'] = $statusFil;

            $response = Http::timeout(60)
                ->withHeaders([
                    'X-API-KEY'  => $apiKey,
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($apiUrl, $params);

            if (!$response->successful()) {
                $bodySnippet = Str::limit(trim(strip_tags($response->body())), 250);
                throw new \Exception("SIMPEG API error: HTTP {$response->status()}" . ($bodySnippet ? " — {$bodySnippet}" : ''));
            }

            $json = $response->json();
            if (!is_array($json) || ($json['status'] ?? '') !== 'success') {
                $errMsg = $json['message'] ?? Str::limit(trim(strip_tags($response->body())), 250);
                throw new \Exception("SIMPEG API error: " . ($errMsg ?: 'Unknown'));
            }

            $employees = $json['data'] ?? [];
            $logData['records_fetched'] = count($employees);
            $syncedAt = now();

            $inserted = 0;
            $updated  = 0;

            foreach ($employees as $emp) {
                $username = $emp['username'] ?? null;
                if (empty($username)) continue;

                $payload = [
                    'id_sdm'    => $emp['id_sdm']   ?? null,
                    'nidn'      => $emp['nidn']      ?? null,
                    'nip'       => $emp['nip']       ?? null,
                    'username'  => $username,
                    'nama'      => $emp['nama']      ?? 'Pegawai',
                    'status'    => $emp['status']    ?? 'Tendik',
                    'jenis'     => $emp['jenis']     ?? 'tendik',
                    'email'     => $emp['email']     ?? null,
                    'synced_at' => $syncedAt,
                    'updated_at'=> $syncedAt,
                ];

                $existing = SimpegEmployeeCache::where('username', $username)->first();
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $payload['created_at'] = $syncedAt;
                    SimpegEmployeeCache::create($payload);
                    $inserted++;
                }
            }

            $logData['records_inserted'] = $inserted;
            $logData['records_updated']  = $updated;
            $logData['status']           = 'success';
            $logData['notes']            = "Berhasil sync {$logData['records_fetched']} pegawai dari SIMPEG.";

            DB::table('external_sync_logs')->insert($logData);

            return back()->with('success', "✅ Sync SIMPEG berhasil! {$inserted} ditambah, {$updated} diperbarui, total {$logData['records_fetched']} pegawai.");

        } catch (\Exception $e) {
            Log::error('SimpegSync error: ' . $e->getMessage());
            $logData['notes'] = $e->getMessage();
            DB::table('external_sync_logs')->insert($logData);

            return back()->with('error', '❌ Sync SIMPEG gagal: ' . $e->getMessage());
        }
    }

    /**
     * API: Cari pegawai dari cache (untuk dropdown assignment tugas scan wisuda).
     * GET /admin/sync-simpeg/search?q=nama&status=dosen
     */
    public function search(Request $request)
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $query = SimpegEmployeeCache::query();

        if (!empty($q)) {
            $query->search($q);
        }

        if ($status === 'dosen') {
            $query->dosen();
        } elseif (in_array($status, ['tendik', 'pegawai'])) {
            $query->tendik();
        }

        $results = $query->orderBy('nama')->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data'   => $results,
        ]);
    }
}
