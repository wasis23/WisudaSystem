<?php

use App\Http\Controllers\Admin\BukuKenanganController;
use App\Http\Controllers\Admin\DutyAssignmentController;
use App\Http\Controllers\Admin\FakultasProdiController;
use App\Http\Controllers\Admin\PeriodeWisudaController;
use App\Http\Controllers\Admin\SimantaSyncController;
use App\Http\Controllers\Admin\SimpegSyncController;
use App\Http\Controllers\Admin\StageLayoutConfigController;
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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
        'hadirCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', true)->count(),
        'belumHadirCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', false)->count(),
        'auditoriumCount' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_in_auditorium', true)->count(),
        'activePeriode' => $activePeriode,
        'totalProdi' => \App\Models\ProgramStudi::count(),
    ];

    $recentWisudawan = \App\Models\Wisudawan::with('programStudi')
        ->where('periode_wisuda_id', $activePeriode?->id)
        ->latest()
        ->take(5)
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
            // Ensure student has a QR token
            if (!$wisudawan->qr_code_token) {
                $wisudawan->update(['qr_code_token' => 'WSD-' . ($wisudawan->nim ?? Str::random(8))]);
            }

            // Auto-generate default 2 guests if wisudawan has filled biodata and has 0 guests
            if ($wisudawan->tamuTambahan()->count() === 0) {
                \App\Models\WisudawanTamuTambahan::create([
                    'wisudawan_id' => $wisudawan->id,
                    'nama_tamu' => 'Pendamping 1 (Orang Tua/Wali)',
                    'hubungan' => 'Orang Tua / Wali',
                    'qr_guest_token' => 'GST-1-' . ($wisudawan->nim ?? Str::random(8)),
                ]);

                \App\Models\WisudawanTamuTambahan::create([
                    'wisudawan_id' => $wisudawan->id,
                    'nama_tamu' => 'Pendamping 2 (Orang Tua/Wali)',
                    'hubungan' => 'Orang Tua / Wali',
                    'qr_guest_token' => 'GST-2-' . ($wisudawan->nim ?? Str::random(8)),
                ]);
            }

            $wisudawan->load(['programStudi', 'tamuTambahan']);
        }

        return Inertia::render('Wisudawan/Dashboard', [
            'wisudawan' => $wisudawan,
            'stageConfig' => \App\Models\StageLayoutConfig::getDefaultConfig(),
        ]);
    })->name('dashboard');

    // Extra Guest Form & Snack Calculation
    Route::get('/tamu-tambahan', [ExtraGuestController::class, 'index'])->name('tamu.form');
    Route::post('/tamu-tambahan', [ExtraGuestController::class, 'store'])->name('tamu.store');

    // Tracer Study Routes
    Route::get('/tracer-study', function () {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;
        return Inertia::render('Wisudawan/TracerStudy', [
            'wisudawan' => $wisudawan,
        ]);
    })->name('tracer.form');

    Route::post('/tracer-study', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        if ($user->wisudawan) {
            $user->wisudawan->update([
                'is_tracer_study_filled' => true,
                'tracer_status_pekerjaan' => $request->tracer_status_pekerjaan,
                'tracer_nama_instansi' => $request->tracer_nama_instansi,
                'tracer_jabatan' => $request->tracer_jabatan,
                'tracer_pendapatan' => $request->tracer_pendapatan,
                'tracer_kesesuaian_prodi' => $request->tracer_kesesuaian_prodi,
            ]);
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
        return Inertia::render('Wisudawan/Register', [
            'wisudawan' => $wisudawan,
            'activePeriode' => $activePeriode,
            'programStudis' => $programStudis,
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

        if ($user->wisudawan) {
            $user->wisudawan->update($data);
        } else {
            $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();
            $data['user_id'] = $user->id;
            $data['periode_wisuda_id'] = $activePeriode?->id;
            $data['qr_code_token'] = 'WIS-' . strtoupper(\Illuminate\Support\Str::random(8));
            \App\Models\Wisudawan::create($data);
        }

        return redirect()->route('wisudawan.dashboard')->with('success', 'Biodata wisudawan berhasil disimpan!');
    })->name('pendaftaran.store');
});

require __DIR__.'/auth.php';
