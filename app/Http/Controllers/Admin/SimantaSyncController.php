<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\SimantaMahasiswaLulusCache;
use App\Models\User;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $search    = trim($request->input('search', $request->input('q', '')));

        $query = SimantaMahasiswaLulusCache::query()
            ->lulus()
            ->orderBy('tanggal_pendadaran', 'desc')
            ->orderBy('nama');

        // Filter rentang jika diberikan (atau dilewati jika user mencari NIM/Nama tertentu)
        if ($tglDari && $tglSampai && empty($search)) {
            $query->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);
        }

        // Filter pencarian NIM / Nama / Judul TA
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%")
                  ->orWhere('judul_ta', 'like', "%{$search}%");
            });
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
            'filter'     => ['tgl_dari' => $tglDari, 'tgl_sampai' => $tglSampai, 'search' => $search],
            'default_range' => ['dari' => $defaultDari, 'sampai' => $defaultSampai],
        ]);
    }

    /**
     * Trigger sync: pull data mahasiswa lulus dari SIMANTA API.
     *
     * POST params:
     *   tgl_dari    : default = Oktober tahun lalu
     *   tgl_sampai  : default = Oktober tahun ini
     *   status_lulus: default = 1 (hanya yang lulus)
     *   prodi       : kosong = semua
     *   search      : pencarian NIM / nama spesifik
     */
    public function sync(Request $request)
    {
        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();

        $tglDari     = $request->input('tgl_dari',    $defaultDari);
        $tglSampai   = $request->input('tgl_sampai',  $defaultSampai);
        $statusLulus = $request->input('status_lulus', '1');
        $prodi       = $request->input('prodi', '');
        $search      = trim($request->input('search', $request->input('q', '')));

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
                'status_lulus' => $statusLulus, 'prodi' => $prodi, 'search' => $search,
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
            if (!empty($prodi))  $params['prodi']    = $prodi;
            if (!empty($search)) $params['nim_nama'] = $search;

            $response = Http::timeout(90)
                ->withHeaders([
                    'X-API-KEY'  => $apiKey,
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])
                ->get($apiUrl, $params);

            if (!$response->successful()) {
                $bodySnippet = Str::limit(trim(strip_tags($response->body())), 250);
                throw new \Exception("SIMANTA API error: HTTP {$response->status()}" . ($bodySnippet ? " — {$bodySnippet}" : ''));
            }

            $json = $response->json();
            if (!is_array($json)) {
                $rawBody = Str::limit(trim(strip_tags($response->body())), 250);
                throw new \Exception("SIMANTA API error: Respons server bukan JSON valid" . ($rawBody ? " ({$rawBody})" : ''));
            }

            if (($json['status'] ?? '') !== 'success') {
                $errMsg = $json['message'] ?? (is_string($json) ? $json : null);
                if (empty($errMsg)) {
                    $errMsg = Str::limit(trim(strip_tags($response->body())), 250) ?: 'Status bukan success';
                }
                throw new \Exception("SIMANTA API error: {$errMsg}");
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
            // Fallback: jika API SIMANTA bermasalah/error, coba tarik dari database SIAKAD (viewMahasiswaKeluar)
            try {
                $siakadList = DB::connection('siakad')
                    ->table('viewMahasiswaKeluar')
                    ->where('ket_keluar', 'Lulus')
                    ->get();

                if ($siakadList->isNotEmpty()) {
                    $syncedAt = now();
                    $inserted = 0;
                    $updated  = 0;

                    foreach ($siakadList as $item) {
                        $nim = $item->nipd ?? null;
                        if (empty($nim)) continue;

                        $payload = [
                            'nim'                         => $nim,
                            'nama'                        => $item->nm_pd ?? $nim,
                            'judul_ta'                    => $item->judul_skripsi ?? null,
                            'kode_prodi'                  => substr($nim, 1, 1),
                            'nama_prodi'                  => $item->nm_lemb ?? null,
                            'status_persetujuan'          => 'disetujui',
                            'status_lulus'                => 1,
                            'tanggal_pendadaran'          => $this->safeDate($item->tgl_keluar ?? null) ?? now()->toDateString(),
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

                    $logData['records_fetched']  = $siakadList->count();
                    $logData['records_inserted'] = $inserted;
                    $logData['records_updated']  = $updated;
                    $logData['status']           = 'success';
                    $logData['notes']            = "Sync SIMANTA via Fallback SIAKAD: {$inserted} ditambah, {$updated} diperbarui.";
                    DB::table('external_sync_logs')->insert($logData);

                    return back()->with('success', "✅ Sync SIMANTA berhasil (via Fallback Data SIAKAD)! {$inserted} mahasiswa baru, {$updated} diperbarui.");
                }
            } catch (\Exception $ex) {
                Log::warning('SIAKAD Fallback error: ' . $ex->getMessage());
            }

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

    /**
     * Halaman konfirmasi import wisudawan dari cache ke tabel wisudawan.
     * GET /admin/sync-simanta/import
     */
    public function importPreview(Request $request)
    {
        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();

        $tglDari    = $request->input('tgl_dari',   $defaultDari);
        $tglSampai  = $request->input('tgl_sampai', $defaultSampai);
        $ignoreDate = $request->boolean('ignore_date');
        $prodiFilter = $request->input('prodi', '');

        $query = SimantaMahasiswaLulusCache::lulus()->belumTerdaftarWisuda();

        if (!$ignoreDate && !empty($tglDari) && !empty($tglSampai)) {
            $query->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);
        }

        if (!empty($prodiFilter)) {
            $query->where('kode_prodi', $prodiFilter);
        }

        $candidates = $query->orderBy('nama')->get();

        $totalUnimported = SimantaMahasiswaLulusCache::lulus()->belumTerdaftarWisuda()->count();
        $totalInCache    = SimantaMahasiswaLulusCache::count();

        $periodes = PeriodeWisuda::orderByDesc('id')->get();
        $activePeriode = PeriodeWisuda::getActive() ?? $periodes->first();
        $programStudis = ProgramStudi::orderBy('nama_prodi')->get();

        return Inertia::render('Admin/SimantaImport', [
            'candidates'      => $candidates,
            'periodes'        => $periodes,
            'activePeriode'   => $activePeriode,
            'programStudis'   => $programStudis,
            'filter'          => [
                'tgl_dari'    => $tglDari,
                'tgl_sampai'  => $tglSampai,
                'ignore_date' => $ignoreDate,
                'prodi'       => $prodiFilter,
            ],
            'totalUnimported' => $totalUnimported,
            'totalInCache'    => $totalInCache,
        ]);
    }

    /**
     * Jalankan import: buat wisudawan dari cache SIMANTA atau input list NIM manual.
     *
     * POST params:
     *   nim[]                : daftar NIM yang dipilih dari checkbox
     *   nim_list_text        : teks daftar NIM (dipisahkan baris/koma/spasi)
     *   periode_wisuda_id    : ID periode wisuda tujuan (default: aktif)
     *   tgl_dari / tgl_sampai: rentang filter cache
     *   auto_create_user     : '1' = buat akun user (default true)
     */
    public function importWisudawan(Request $request)
    {
        $request->validate([
            'periode_wisuda_id' => 'required|exists:periode_wisuda,id',
        ]);

        [$defaultDari, $defaultSampai] = SimantaMahasiswaLulusCache::tahunAkademiksaat();
        $tglDari   = $request->input('tgl_dari',   $defaultDari);
        $tglSampai = $request->input('tgl_sampai', $defaultSampai);
        $periodeId = (int) $request->input('periode_wisuda_id');
        $autoUser   = $request->input('auto_create_user', '1') === '1';

        // ── 1. Kumpulkan daftar NIM yang akan di-import ─────────────────────────
        $pilihanNim = $request->input('nim', []);
        $nimListText = trim((string) $request->input('nim_list_text', ''));

        if (!empty($nimListText)) {
            // Pisahkan teks berdasarkan newline, spasi, koma, titik koma, tab
            $parsedNims = preg_split('/[\r\n\t,; ]+/', $nimListText, -1, PREG_SPLIT_NO_EMPTY);
            $parsedNims = array_map('strtoupper', array_map('trim', $parsedNims));
            $pilihanNim = array_unique(array_merge($pilihanNim, $parsedNims));
        }

        // Mapping kode prodi SIMANTA (karakter ke-2 dari NIM) ke kode_prodi di tabel program_studi
        $prodiMapping = [
            'A' => 'D3-TekOto',   // Teknologi Otomotif
            'B' => 'D3-SI',        // Sistem Informasi
            'C' => 'D3-KomMas',   // Komunikasi Massa
            'D' => 'D3-Hotel',    // Perhotelan
            'E' => 'D3-FAR',      // Farmasi
            'F' => 'D3-MIK',      // Manajemen Informasi Kesehatan
            'G' => 'D3-TLM',      // Teknologi Laboratorium Medis
            'H' => 'D3-BMR',      // Bisnis Manajemen Ritel
            'I' => 'D3-MI',       // Manajemen Informatika
        ];

        $programStudis = ProgramStudi::all()->keyBy('kode_prodi');
        $ignoreDate = $request->boolean('ignore_date');

        // ── 2. Ambil candidate list ───────────────────────────────────────────
        $candidatesByNim = collect();

        if (!empty($pilihanNim)) {
            // Ambil dari cache yang sesuai NIM
            $cacheRecords = SimantaMahasiswaLulusCache::whereIn('nim', $pilihanNim)->get()->keyBy('nim');

            foreach ($pilihanNim as $targetNim) {
                $targetNim = strtoupper(trim($targetNim));
                if (empty($targetNim)) continue;

                if (isset($cacheRecords[$targetNim])) {
                    $candidatesByNim->put($targetNim, $cacheRecords[$targetNim]);
                } else {
                    // Fallback: Cari di database SIAKAD jika di cache SIMANTA belum ada
                    $siakadMahasiswa = null;
                    try {
                        $siakadMahasiswa = DB::connection('siakad')
                            ->table('viewMahasiswaPt')
                            ->where('nipd', $targetNim)
                            ->first();

                        if (!$siakadMahasiswa) {
                            $siakadMahasiswa = DB::connection('siakad')
                                ->table('viewMahasiswaKeluar')
                                ->where('nipd', $targetNim)
                                ->first();
                        }
                    } catch (\Exception $e) {
                        Log::warning("SIAKAD Lookup Error for NIM {$targetNim}: " . $e->getMessage());
                    }

                    // Buat virtual cache object
                    $candidatesByNim->put($targetNim, (object) [
                        'nim'                => $targetNim,
                        'nama'               => $siakadMahasiswa->nm_pd ?? $targetNim,
                        'judul_ta'           => $siakadMahasiswa->judul_skripsi ?? '',
                        'kode_prodi'         => substr($targetNim, 1, 1),
                        'nama_prodi'         => $siakadMahasiswa->nm_lemb ?? null,
                        'tanggal_pendadaran' => $this->safeDate($siakadMahasiswa->tgl_keluar ?? null) ?? now()->toDateString(),
                    ]);
                }
            }
        } else {
            $query = SimantaMahasiswaLulusCache::lulus()->belumTerdaftarWisuda();
            if (!$ignoreDate && !empty($tglDari) && !empty($tglSampai)) {
                $query->whereBetween('tanggal_pendadaran', [$tglDari, $tglSampai]);
            }
            $candidatesByNim = $query->orderBy('nama')->get()->keyBy('nim');
        }

        if ($candidatesByNim->isEmpty()) {
            return back()->with('info', 'Tidak ada data mahasiswa yang ditemukan untuk di-import.');
        }

        $logData = [
            'source'           => 'simanta',
            'action'           => 'import_wisudawan',
            'records_fetched'  => $candidatesByNim->count(),
            'records_inserted' => 0,
            'records_updated'  => 0,
            'status'           => 'failed',
            'notes'            => null,
            'filter_params'    => json_encode(['tgl_dari' => $tglDari, 'tgl_sampai' => $tglSampai, 'periode_id' => $periodeId]),
            'triggered_by'     => auth()->id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $inserted  = 0;
        $skipped   = 0;
        $errors    = [];

        DB::beginTransaction();
        try {
            foreach ($candidatesByNim as $nim => $item) {
                $nim = strtoupper($item->nim);

                // ── 1. Tentukan program_studi_id ───────────────────────────────
                $kodeProdiSimanta = strtoupper($item->kode_prodi ?? substr($nim, 1, 1));
                $kodeProdiWisuda  = $prodiMapping[$kodeProdiSimanta] ?? null;
                $programStudi     = $kodeProdiWisuda ? ($programStudis[$kodeProdiWisuda] ?? null) : null;

                // Fallback pencarian nama prodi
                if (!$programStudi && !empty($item->nama_prodi)) {
                    $programStudi = ProgramStudi::where('nama_prodi', 'like', '%' . $item->nama_prodi . '%')->first();
                }

                // Fallback default prodi pertama jika tetap tidak ketemu
                if (!$programStudi) {
                    $programStudi = ProgramStudi::first();
                }

                if (!$programStudi) {
                    $errors[] = "NIM {$nim}: Data program studi tidak ditemukan.";
                    $skipped++;
                    continue;
                }

                // Cek apakah wisudawan sudah ada di periode ini
                $existing = Wisudawan::where('nim', $nim)
                    ->where('periode_wisuda_id', $periodeId)
                    ->first();

                if ($existing) {
                    if ($item instanceof SimantaMahasiswaLulusCache) {
                        $item->update(['wisudawan_id' => $existing->id]);
                    }
                    $skipped++;
                    continue;
                }

                // ── 2. Buat User jika diminta ──────────────────────────────────
                $userId = null;
                if ($autoUser) {
                    $email = strtolower($nim) . '@students.poltekindonusa.ac.id';
                    $user  = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name'             => $item->nama ?? $nim,
                            'password'         => Hash::make($nim), // Password default = NIM
                            'role'             => 'wisudawan',
                            'program_studi_id' => $programStudi->id,
                        ]
                    );
                    $userId = $user->id;
                }

                // ── 3. Ambil data biodata lengkap dari SIAKAD ─────────────────
                $siakadStudent = app(\App\Services\SiakadIntegrationService::class)->getStudentByNim($nim);

                // ── 4. Buat Wisudawan ──────────────────────────────────────────
                $qrToken = 'WSD-' . strtoupper($nim) . '-' . strtoupper(Str::random(4));

                $wisudawan = Wisudawan::create([
                    'user_id'                  => $userId,
                    'periode_wisuda_id'        => $periodeId,
                    'program_studi_id'         => $programStudi->id,
                    'nim'                      => $nim,
                    'nama_lengkap'             => $siakadStudent['nama_lengkap'] ?? ($item->nama ?? $nim),
                    'judul_ta'                 => $item->judul_ta ?? '',
                    'dosen_pembimbing_1'       => null,
                    'dosen_pembimbing_2'       => null,
                    'dosen_penguji'            => null,
                    'nama_ayah'                => $siakadStudent['nama_ayah'] ?? null,
                    'nama_ibu'                 => $siakadStudent['nama_ibu'] ?? null,
                    'tempat_lahir'             => $siakadStudent['tempat_lahir'] ?? '',
                    'tanggal_lahir'            => $siakadStudent['tanggal_lahir'] ?? '1990-01-01',
                    'jenis_kelamin'            => $siakadStudent['jenis_kelamin'] ?? 'L',
                    'nik'                      => $siakadStudent['nik'] ?? null,
                    'alamat'                   => $siakadStudent['alamat'] ?? null,
                    'nomor_hp'                 => $siakadStudent['nomor_hp'] ?? '',
                    'tanggal_lulus'            => $item->tanggal_pendadaran ?? now()->toDateString(),
                    'status_kelulusan_simanta' => 'LULUS',
                    'qr_code_token'            => $qrToken,
                    'status_verifikasi'        => 'verified',
                    'email'                    => strtolower($nim) . '@students.poltekindonusa.ac.id',
                    'ipk'                      => 3.50,
                    'predikat_kelulusan'       => 'Memuaskan',
                ]);

                // Auto-generate default 2 tamu pendamping
                \App\Models\WisudawanTamuTambahan::firstOrCreate(
                    [
                        'wisudawan_id'   => $wisudawan->id,
                        'nama_tamu'      => 'Pendamping 1 (Orang Tua / Wali)',
                    ],
                    [
                        'hubungan'       => 'Orang Tua / Wali',
                        'qr_guest_token' => 'GST-1-' . $nim,
                    ]
                );

                \App\Models\WisudawanTamuTambahan::firstOrCreate(
                    [
                        'wisudawan_id'   => $wisudawan->id,
                        'nama_tamu'      => 'Pendamping 2 (Orang Tua / Wali)',
                    ],
                    [
                        'hubungan'       => 'Orang Tua / Wali',
                        'qr_guest_token' => 'GST-2-' . $nim,
                    ]
                );

                // ── 4. Update link di cache jika model Eloquent ────────────────
                if ($item instanceof SimantaMahasiswaLulusCache) {
                    $item->update(['wisudawan_id' => $wisudawan->id]);
                } else {
                    SimantaMahasiswaLulusCache::where('nim', $nim)->update(['wisudawan_id' => $wisudawan->id]);
                }

                $inserted++;
            }

            DB::commit();

            $logData['records_inserted'] = $inserted;
            $logData['records_updated']  = $skipped;
            $logData['status']           = empty($errors) ? 'success' : 'partial';
            $logData['notes']            = "{$inserted} wisudawan dibuat, {$skipped} dilewati." .
                                           (empty($errors) ? '' : ' Errors: ' . implode('; ', array_slice($errors, 0, 5)));

            DB::table('external_sync_logs')->insert($logData);

            $msg = "✅ Import selesai! {$inserted} wisudawan berhasil dimasukkan ke periode wisuda terpilih.";
            if ($skipped > 0) $msg .= " ({$skipped} mahasiswa sudah ada sebelumnya)";
            if (!empty($errors)) $msg .= ". ⚠️ " . count($errors) . " kendala ditemukan.";

            return redirect()->route('admin.sync-simanta.import')
                ->with('success', $msg)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SimantaImport error: ' . $e->getMessage());
            $logData['notes'] = $e->getMessage();
            DB::table('external_sync_logs')->insert($logData);

            return back()->with('error', '❌ Import gagal: ' . $e->getMessage());
        }
    }
}
