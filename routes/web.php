<?php

use App\Http\Controllers\Admin\BukuKenanganController;
use App\Http\Controllers\Admin\DutyAssignmentController;
use App\Http\Controllers\Admin\FakultasProdiController;
use App\Http\Controllers\Admin\PeriodeWisudaController;
use App\Http\Controllers\Admin\ProgramStudiAdminController;
use App\Http\Controllers\Admin\SimantaSyncController;
use App\Http\Controllers\Admin\SimpegSyncController;
use App\Http\Controllers\Admin\SikeuSyncController;
use App\Http\Controllers\Admin\StageLayoutConfigController;
use App\Http\Controllers\Admin\TracerStudyAdminController;
use App\Http\Controllers\KioskScanController;
use App\Http\Controllers\Panitia\PresensiWisudawanController;
use App\Http\Controllers\Panitia\StageDisplayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Wisudawan\ExtraGuestController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->role === 'wisudawan') {
        return redirect()->route('wisudawan.dashboard');
    } elseif ($user->role === 'security') {
        return redirect()->route('security.scan');
    } elseif ($user->role === 'receptionist') {
        return redirect()->route('receptionist.scan');
    }

    $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();

    $stats = [
        'totalWisudawan' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->count(),
        'tracerCompleted' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_tracer_study_filled', true)->count(),
        'lunasCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('status_pembayaran_sikeu', 'lunas')->count(),
        'belumLunasCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where(function($q) { $q->where('status_pembayaran_sikeu', 'belum_lunas')->orWhereNull('status_pembayaran_sikeu'); })->count(),
        'hadirCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', true)->count(),
        'belumHadirCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', false)->count(),
        'auditoriumCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_in_auditorium', true)->count(),
        'totalExtraGuests' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->sum('jumlah_undangan_extra_sikeu'),
        'activePeriode' => $activePeriode,
        'totalProdi' => \App\Models\ProgramStudi::count(),
    ];

    $recentWisudawan = \App\Models\Wisudawan::with(['programStudi', 'sikeuPayment'])
        ->where('periode_wisuda_id', $activePeriode?->id)
        ->latest()
        ->take(6)
        ->get();

    return Inertia::render('Admin/Dashboard', [
        'stats' => $stats,
        'recentWisudawan' => $recentWisudawan,
        'stageConfig' => \App\Models\StageLayoutConfig::getDefaultConfig(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Self-Service Kiosk Scan (TV Display + Laptop + USB Scanner)
Route::get('/kiosk-scan', [KioskScanController::class, 'index'])->name('kiosk.display');
Route::post('/api/kiosk-scan', [KioskScanController::class, 'scan'])->name('api.kiosk.scan');

// 1. Admin Master Routes
Route::middleware(['auth', 'role:admin_utama'])->prefix('admin')->name('admin.')->group(function () {
    // Periode Wisuda Management
    Route::get('/periode', [PeriodeWisudaController::class, 'index'])->name('periode.index');
    Route::post('/periode', [PeriodeWisudaController::class, 'store'])->name('periode.store');
    Route::patch('/periode/{id}/toggle', [PeriodeWisudaController::class, 'toggleActive'])->name('periode.toggle');
    Route::post('/periode/sync-siakad', [PeriodeWisudaController::class, 'syncSiakad'])->name('periode.sync-siakad');

    // Precision Stage Layout Configurator
    Route::get('/stage-layout', [StageLayoutConfigController::class, 'edit'])->name('stage-layout.edit');
    Route::post('/stage-layout', [StageLayoutConfigController::class, 'update'])->name('stage-layout.update');

    // Buku Kenangan PDF & Data Wisudawan
    Route::get('/buku-kenangan', [BukuKenanganController::class, 'index'])->name('buku-kenangan.index');
    Route::get('/buku-kenangan/export', [BukuKenanganController::class, 'exportPdf'])->name('buku-kenangan.export');

    // SIMPEG Scan Duty Assignment (Security & Receptionist)
    Route::get('/duty-assignments', [DutyAssignmentController::class, 'index'])->name('duty-assignments.index');
    Route::post('/duty-assignments', [DutyAssignmentController::class, 'store'])->name('duty-assignments.store');
    Route::patch('/duty-assignments/{dutyAssignment}/toggle', [DutyAssignmentController::class, 'toggle'])->name('duty-assignments.toggle');
    Route::delete('/duty-assignments/{dutyAssignment}', [DutyAssignmentController::class, 'destroy'])->name('duty-assignments.destroy');

    // Monitoring Presensi & Peserta Belum Hadir
    Route::get('/monitoring-presensi', [PresensiWisudawanController::class, 'listWisudawan'])->name('monitoring-presensi');

    // ── SIMPEG Sync (cache pegawai dari SIMPEG) ──────────────────────────────
    Route::get('/sync-simpeg',          [SimpegSyncController::class, 'index'])->name('sync-simpeg.index');
    Route::post('/sync-simpeg',         [SimpegSyncController::class, 'sync'])->name('sync-simpeg.sync');
    Route::get('/sync-simpeg/search',   [SimpegSyncController::class, 'search'])->name('sync-simpeg.search');

    // ── SIMANTA Sync (cache mahasiswa lulus dari SIMANTA) ───────────────────
    Route::get('/sync-simanta',             [SimantaSyncController::class, 'index'])->name('sync-simanta.index');
    Route::post('/sync-simanta',            [SimantaSyncController::class, 'sync'])->name('sync-simanta.sync');
    Route::get('/sync-simanta/data',        [SimantaSyncController::class, 'data'])->name('sync-simanta.data');
    // Import: dari cache SIMANTA → tabel wisudawan (inilah langkah ke-2)
    Route::get('/sync-simanta/import',      [SimantaSyncController::class, 'importPreview'])->name('sync-simanta.import.preview');
    Route::post('/sync-simanta/import',     [SimantaSyncController::class, 'importWisudawan'])->name('sync-simanta.import');
    Route::post('/sync-simanta/reset-wisudawan', [SimantaSyncController::class, 'resetPeriodeWisudawan'])->name('sync-simanta.reset-wisudawan');

    // ── Tracer Study Monitoring & Report Export ────────────────────────────────
    Route::get('/tracer-study', [TracerStudyAdminController::class, 'index'])->name('tracer-study.index');
    Route::get('/tracer-study/export', [TracerStudyAdminController::class, 'export'])->name('tracer-study.export');
    Route::get('/tracer-study/{id}', [TracerStudyAdminController::class, 'show'])->name('tracer-study.show');

    // ── Pengaturan Program Studi & Gelar Lulusan ───────────────────────────────
    Route::get('/program-studi', [ProgramStudiAdminController::class, 'index'])->name('program-studi.index');
    Route::post('/program-studi', [ProgramStudiAdminController::class, 'store'])->name('program-studi.store');
    Route::put('/program-studi/{id}', [ProgramStudiAdminController::class, 'update'])->name('program-studi.update');
    Route::delete('/program-studi/{id}', [ProgramStudiAdminController::class, 'destroy'])->name('program-studi.destroy');

    // ── SIKEU Sync (pembayaran wisuda & kuota undangan dari SIKEU) ───────────
    Route::get('/sync-sikeu',               [SikeuSyncController::class, 'index'])->name('sync-sikeu.index');
    Route::post('/sync-sikeu',              [SikeuSyncController::class, 'sync'])->name('sync-sikeu.sync');
    Route::post('/sync-sikeu/{id}/toggle',  [SikeuSyncController::class, 'toggle'])->name('sync-sikeu.toggle');
    Route::post('/sync-sikeu/{id}/update',  [SikeuSyncController::class, 'updateDetail'])->name('sync-sikeu.update');
});

// 2. Security Scan Gate Route
Route::middleware(['auth', 'role:security,admin_utama'])->prefix('security')->name('security.')->group(function () {
    Route::get('/scan', [PresensiWisudawanController::class, 'mobileSecurityScan'])->name('scan');
    Route::post('/scan', [PresensiWisudawanController::class, 'scan'])->name('scan.process');
});

// 3. Receptionist Scan Gate Route
Route::middleware(['auth', 'role:receptionist,admin_utama'])->prefix('receptionist')->name('receptionist.')->group(function () {
    Route::get('/scan', [PresensiWisudawanController::class, 'mobileReceptionistScan'])->name('scan');
    Route::post('/scan', [PresensiWisudawanController::class, 'scan'])->name('scan.process');
    Route::post('/guest-presensi/{id}', [PresensiWisudawanController::class, 'processGuestAttendance'])->name('guest.toggle');
});

// 4. Stage Control Routes (Admin Utama)
Route::middleware(['auth', 'role:admin_utama'])->prefix('panitia')->name('panitia.')->group(function () {
    // Presensi Gate (Barcode / Kamera Scan)
    Route::get('/presensi', [PresensiWisudawanController::class, 'index'])->name('presensi');
    Route::get('/presensi/gate', [PresensiWisudawanController::class, 'index'])->name('presensi.gate');
    Route::get('/presensi/scan-gate', [PresensiWisudawanController::class, 'index'])->name('presensi.index');
    Route::post('/presensi', [PresensiWisudawanController::class, 'scan'])->name('presensi.scan');

    // Presensi Wisudawan (Information & Status List)
    Route::get('/presensi/wisudawan', [PresensiWisudawanController::class, 'listWisudawan'])->name('presensi.wisudawan');
    Route::post('/presensi/{id}/toggle', [PresensiWisudawanController::class, 'toggleStatus'])->name('presensi.toggle');

    // Stage Controls
    Route::get('/stage-display', [StageDisplayController::class, 'display'])->name('stage-display');
    Route::get('/stage-display/active-wisudawan', [StageDisplayController::class, 'getActiveWisudawan'])->name('stage-display.get-active');
    Route::get('/stage-control', [StageDisplayController::class, 'control'])->name('stage-control');
    Route::post('/stage-control/active-wisudawan', [StageDisplayController::class, 'setActiveWisudawan'])->name('stage-control.set-active');
    Route::get('/stage-control/download-template', [StageDisplayController::class, 'downloadTemplate'])->name('stage-control.download-template');
    Route::post('/stage-control/upload-template', [StageDisplayController::class, 'uploadTemplate'])->name('stage-control.upload-template');
});

// 5. Wisudawan Routes
Route::middleware(['auth', 'role:wisudawan,admin_utama'])->prefix('wisudawan')->name('wisudawan.')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if ($wisudawan) {
            // Ensure student has a unique QR token
            if (!$wisudawan->qr_code_token || $wisudawan->qr_code_token === 'WSD-' . $wisudawan->nim) {
                $wisudawan->update(['qr_code_token' => 'WSD-' . $wisudawan->nim . '-' . strtoupper(\Illuminate\Support\Str::random(4))]);
            }

            // Auto-generate default 2 guests if wisudawan has 0 guests
            if ($wisudawan->tamuTambahan()->count() === 0) {
                \App\Models\WisudawanTamuTambahan::create([
                    'wisudawan_id' => $wisudawan->id,
                    'nama_tamu' => $wisudawan->nama_ayah ?: 'Pendamping 1 (Orang Tua/Wali)',
                    'hubungan' => 'Orang Tua / Wali',
                    'qr_guest_token' => 'GST1-' . $wisudawan->nim . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                ]);

                \App\Models\WisudawanTamuTambahan::create([
                    'wisudawan_id' => $wisudawan->id,
                    'nama_tamu' => $wisudawan->nama_ibu ?: 'Pendamping 2 (Orang Tua/Wali)',
                    'hubungan' => 'Orang Tua / Wali',
                    'qr_guest_token' => 'GST2-' . $wisudawan->nim . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                ]);
            } else {
                // Ensure existing guests have unique tokens if they were missing or using old pattern
                $guests = $wisudawan->tamuTambahan()->orderBy('id')->get();
                if (isset($guests[0]) && (!$guests[0]->qr_guest_token || str_starts_with($guests[0]->qr_guest_token, 'GST-1-'))) {
                    $guests[0]->update(['qr_guest_token' => 'GST1-' . $wisudawan->nim . '-' . strtoupper(\Illuminate\Support\Str::random(6))]);
                }
                if (isset($guests[1]) && (!$guests[1]->qr_guest_token || str_starts_with($guests[1]->qr_guest_token, 'GST-2-'))) {
                    $guests[1]->update(['qr_guest_token' => 'GST2-' . $wisudawan->nim . '-' . strtoupper(\Illuminate\Support\Str::random(6))]);
                }
            }

            $wisudawan->load(['programStudi', 'tamuTambahan', 'sikeuPayment']);
            $sikeuQuota = app(\App\Services\SikeuIntegrationService::class)->getExtraWisudaQuota($wisudawan->nim);
        }

        return Inertia::render('Wisudawan/Dashboard', [
            'wisudawan' => $wisudawan,
            'sikeuQuota' => $sikeuQuota ?? null,
            'stageConfig' => \App\Models\StageLayoutConfig::getDefaultConfig(),
        ]);
    })->name('dashboard');

    // Extra Guest Form & Snack Calculation
    Route::get('/tamu-tambahan', [ExtraGuestController::class, 'index'])->name('tamu.form');
    Route::post('/tamu-tambahan', [ExtraGuestController::class, 'store'])->name('tamu.store');

    // Tracer Study Routes
    Route::get('/tracer-study', function () {
        $user = auth()->user();
        $wisudawan = $user->wisudawan ? $user->wisudawan->load(['tracerStudy', 'programStudi']) : null;
        return Inertia::render('Wisudawan/TracerStudy', [
            'wisudawan' => $wisudawan,
        ]);
    })->name('tracer.form');

    Route::post('/tracer-study', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        if ($user->wisudawan) {
            $wisudawan = $user->wisudawan;

            $statusStr = is_array($request->status_saat_ini) ? implode(', ', $request->status_saat_ini) : ($request->tracer_status_pekerjaan ?? '');
            $instansiStr = $request->nama_perusahaan ?: ($request->nama_usaha ?: ($request->tempat_bekerja ?: ($request->tracer_nama_instansi ?? '')));
            $jabatanStr = is_array($request->posisi_jabatan) ? implode(', ', $request->posisi_jabatan) : ($request->tracer_jabatan ?? '');
            $gajiStr = is_array($request->gaji_per_bulan) && count($request->gaji_per_bulan) ? implode(', ', $request->gaji_per_bulan) : (is_array($request->gaji_usaha) && count($request->gaji_usaha) ? implode(', ', $request->gaji_usaha) : ($request->tracer_pendapatan ?? ''));
            $kesesuaianStr = is_array($request->keselarasan_pekerjaan) && count($request->keselarasan_pekerjaan) ? implode(', ', $request->keselarasan_pekerjaan) : (is_array($request->keselarasan_usaha) && count($request->keselarasan_usaha) ? implode(', ', $request->keselarasan_usaha) : ($request->tracer_kesesuaian_prodi ?? ''));

            $wisudawan->update([
                'is_tracer_study_filled' => true,
                'tracer_status_pekerjaan' => $statusStr,
                'tracer_nama_instansi' => $instansiStr,
                'tracer_jabatan' => $jabatanStr,
                'tracer_pendapatan' => $gajiStr,
                'tracer_kesesuaian_prodi' => $kesesuaianStr,
                'tracer_study_data' => $request->all(),
            ]);

            // Save to normalized tracer_studies table
            $kompetensiLulus = $request->kompetensi_lulus ?? [];
            $kompetensiKerja = $request->kompetensi_kerja ?? [];
            $metodePembelajaran = $request->metode_pembelajaran ?? [];

            \App\Models\TracerStudy::updateOrCreate(
                ['wisudawan_id' => $wisudawan->id],
                [
                    'user_id' => $user->id,
                    'nim' => $request->nim,
                    'nama_lengkap' => $request->nama_lengkap,
                    'email' => $request->email,
                    'no_whatsapp' => $request->no_whatsapp,
                    'prodi' => is_array($request->prodi) ? implode(', ', $request->prodi) : $request->prodi,
                    'prodi_lainnya' => $request->prodi_lainnya,
                    'jenis_kelas' => $request->jenis_kelas,
                    'alamat_lengkap' => $request->alamat_lengkap,

                    'status_saat_ini' => is_array($request->status_saat_ini) ? implode(', ', $request->status_saat_ini) : $request->status_saat_ini,
                    'status_lainnya' => $request->status_lainnya,
                    'tempat_bekerja' => $request->tempat_bekerja,
                    'gaji_per_bulan' => is_array($request->gaji_per_bulan) ? implode(', ', $request->gaji_per_bulan) : $request->gaji_per_bulan,
                    'keselarasan_pekerjaan' => is_array($request->keselarasan_pekerjaan) ? implode(', ', $request->keselarasan_pekerjaan) : $request->keselarasan_pekerjaan,
                    'kesesuaian_pendidikan' => is_array($request->kesesuaian_pendidikan) ? implode(', ', $request->kesesuaian_pendidikan) : $request->kesesuaian_pendidikan,
                    'waktu_tunggu' => is_array($request->waktu_tunggu) ? implode(', ', $request->waktu_tunggu) : $request->waktu_tunggu,
                    'alamat_tempat_kerja' => $request->alamat_tempat_kerja,
                    'jenis_instansi' => is_array($request->jenis_instansi) ? implode(', ', $request->jenis_instansi) : $request->jenis_instansi,
                    'jenis_instansi_lainnya' => $request->jenis_instansi_lainnya,
                    'nama_perusahaan' => $request->nama_perusahaan,
                    'posisi_jabatan' => is_array($request->posisi_jabatan) ? implode(', ', $request->posisi_jabatan) : $request->posisi_jabatan,
                    'posisi_lainnya' => $request->posisi_lainnya,
                    'cakupan_tempat_kerja' => is_array($request->cakupan_tempat_kerja) ? implode(', ', $request->cakupan_tempat_kerja) : $request->cakupan_tempat_kerja,
                    'tingkat_tempat_kerja_lainnya' => $request->tingkat_tempat_kerja_lainnya,

                    'nama_usaha' => $request->nama_usaha,
                    'gaji_usaha' => is_array($request->gaji_usaha) ? implode(', ', $request->gaji_usaha) : $request->gaji_usaha,
                    'keselarasan_usaha' => is_array($request->keselarasan_usaha) ? implode(', ', $request->keselarasan_usaha) : $request->keselarasan_usaha,
                    'studi_lanjut' => is_array($request->studi_lanjut) ? implode(', ', $request->studi_lanjut) : $request->studi_lanjut,
                    'kampus_studi_lanjut' => $request->kampus_studi_lanjut,
                    'alamat_kampus_studi_lanjut' => $request->alamat_kampus_studi_lanjut,
                    'sumber_dana' => is_array($request->sumber_dana) ? implode(', ', $request->sumber_dana) : $request->sumber_dana,
                    'sumber_dana_lainnya' => $request->sumber_dana_lainnya,

                    'lulus_etika' => intval($kompetensiLulus['Etika'] ?? 0),
                    'lulus_keahlian_ilmu' => intval($kompetensiLulus['Keahlian berdasarkan bidang ilmu'] ?? 0),
                    'lulus_bahasa_inggris' => intval($kompetensiLulus['Bahasa Inggris'] ?? 0),
                    'lulus_teknologi_informasi' => intval($kompetensiLulus['Penggunaan Teknologi Informasi'] ?? 0),
                    'lulus_komunikasi' => intval($kompetensiLulus['Komunikasi'] ?? 0),
                    'lulus_kerjasama_tim' => intval($kompetensiLulus['Kerja sama tim'] ?? 0),
                    'lulus_pengembangan_diri' => intval($kompetensiLulus['Pengembangan Diri'] ?? 0),

                    'kerja_etika' => intval($kompetensiKerja['Etika'] ?? 0),
                    'kerja_keahlian_ilmu' => intval($kompetensiKerja['Keahlian berdasarkan bidang ilmu'] ?? 0),
                    'kerja_bahasa_inggris' => intval($kompetensiKerja['Bahasa Inggris'] ?? 0),
                    'kerja_teknologi_informasi' => intval($kompetensiKerja['Penggunaan Teknologi Informasi'] ?? 0),
                    'kerja_komunikasi' => intval($kompetensiKerja['Komunikasi'] ?? 0),
                    'kerja_kerjasama_tim' => intval($kompetensiKerja['Kerja sama tim'] ?? 0),
                    'kerja_pengembangan_diri' => intval($kompetensiKerja['Pengembangan Diri'] ?? 0),

                    'metode_perkuliahan' => intval($metodePembelajaran['Perkuliahan'] ?? 0),
                    'metode_demonstrasi' => intval($metodePembelajaran['Demonstrasi'] ?? 0),
                    'metode_proyek_riset' => intval($metodePembelajaran['Partisipasi dalam proyek riset'] ?? 0),
                    'metode_magang' => intval($metodePembelajaran['Magang'] ?? 0),
                    'metode_praktikum' => intval($metodePembelajaran['Praktikum'] ?? 0),
                    'metode_kerja_lapangan' => intval($metodePembelajaran['Kerja Lapangan'] ?? 0),
                    'metode_diskusi' => intval($metodePembelajaran['Diskusi'] ?? 0),

                    'kepuasan_layanan' => is_array($request->kepuasan_layanan) ? implode(', ', $request->kepuasan_layanan) : $request->kepuasan_layanan,
                    'saran_masukan' => $request->saran_masukan,
                ]
            );
        }
        return redirect()->route('wisudawan.dashboard')->with('success', 'Data Tracer Study berhasil disimpan!');
    })->name('tracer.store');

    // Pendaftaran / Biodata Form & Stage Preview
    Route::get('/pendaftaran', function () {
        $user = auth()->user();
        $wisudawan = $user->wisudawan ? $user->wisudawan->load('programStudi') : null;

        if ($wisudawan && (empty($wisudawan->nama_ayah) || empty($wisudawan->nama_ibu) || empty($wisudawan->alamat) || empty($wisudawan->tempat_lahir))) {
            $siakadData = app(\App\Services\SiakadIntegrationService::class)->getStudentByNim($wisudawan->nim);
            if ($siakadData) {
                $autoFill = [];
                if (empty($wisudawan->nama_ayah) && !empty($siakadData['nama_ayah'])) $autoFill['nama_ayah'] = $siakadData['nama_ayah'];
                if (empty($wisudawan->nama_ibu) && !empty($siakadData['nama_ibu'])) $autoFill['nama_ibu'] = $siakadData['nama_ibu'];
                if (empty($wisudawan->tempat_lahir) && !empty($siakadData['tempat_lahir'])) $autoFill['tempat_lahir'] = $siakadData['tempat_lahir'];
                if ((empty($wisudawan->tanggal_lahir) || $wisudawan->tanggal_lahir === '1990-01-01' || $wisudawan->tanggal_lahir === '1900-01-01') && !empty($siakadData['tanggal_lahir'])) $autoFill['tanggal_lahir'] = $siakadData['tanggal_lahir'];
                if (empty($wisudawan->nik) && !empty($siakadData['nik'])) $autoFill['nik'] = $siakadData['nik'];
                if (empty($wisudawan->alamat) && !empty($siakadData['alamat'])) $autoFill['alamat'] = $siakadData['alamat'];
                if (empty($wisudawan->nomor_hp) && !empty($siakadData['nomor_hp'])) $autoFill['nomor_hp'] = $siakadData['nomor_hp'];
                if (!empty($autoFill)) {
                    $wisudawan->update($autoFill);
                    $wisudawan->refresh();
                }
            }
        }

        $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();
        $programStudis = \App\Models\ProgramStudi::orderBy('nama_prodi')->get();
        $dosens = \App\Models\SimpegEmployeeCache::dosen()->orderBy('nama')->get(['id', 'nama', 'nidn', 'nip', 'jenis']);

        return Inertia::render('Wisudawan/Register', [
            'wisudawan' => $wisudawan,
            'activePeriode' => $activePeriode,
            'programStudis' => $programStudis,
            'dosens' => $dosens,
            'stageConfig' => \App\Models\StageLayoutConfig::getDefaultConfig(),
        ]);
    })->name('pendaftaran.form');

    Route::post('/pendaftaran', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $data = $request->validate([
            'program_studi_id' => 'required',
            'nim' => 'required',
            'nama_lengkap' => 'required',
            'gelar' => 'nullable',
            'nik' => 'nullable',
            'tempat_lahir' => 'nullable',
            'tanggal_lahir' => 'nullable',
            'jenis_kelamin' => 'nullable',
            'nomor_hp' => 'nullable',
            'alamat' => 'nullable',
            'ipk' => 'nullable',
            'judul_ta' => 'nullable',
            'dosen_pembimbing_1' => 'nullable|string|max:255',
            'dosen_pembimbing_2' => 'nullable|string|max:255',
            'dosen_penguji' => 'nullable|string|max:255',
            'tanggal_lulus' => 'nullable',
            'nama_ayah' => 'nullable',
            'nama_ibu' => 'nullable',
            'pas_foto' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('pas_foto')) {
            $path = $request->file('pas_foto')->store('pas_foto', 'public');
            $data['pas_foto'] = $path;
        }

        $data['is_biodata_filled'] = true;

        if ($user->wisudawan) {
            $user->wisudawan->update($data);
            $wisudawan = $user->wisudawan;
        } else {
            $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();
            $data['user_id'] = $user->id;
            $data['periode_wisuda_id'] = $activePeriode?->id;
            $data['qr_code_token'] = 'WSD-' . ($data['nim'] ?? 'MHS') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
            $wisudawan = \App\Models\Wisudawan::create($data);
        }

        // Update guest names if available
        $guests = $wisudawan->tamuTambahan()->orderBy('id')->get();
        if (isset($guests[0]) && !empty($request->nama_ayah)) {
            $guests[0]->update(['nama_tamu' => $request->nama_ayah]);
        }
        if (isset($guests[1]) && !empty($request->nama_ibu)) {
            $guests[1]->update(['nama_tamu' => $request->nama_ibu]);
        }

        return redirect()->route('wisudawan.dashboard')->with('success', 'Biodata wisudawan berhasil disimpan!');
    })->name('pendaftaran.store');
});

require __DIR__.'/auth.php';
