<?php

use App\Http\Controllers\Admin\BukuKenanganController;
use App\Http\Controllers\Admin\PeriodeWisudaController;
use App\Http\Controllers\Admin\StageLayoutConfigController;
use App\Http\Controllers\Panitia\PresensiWisudawanController;
use App\Http\Controllers\Panitia\StageDisplayController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
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
    }

    $activePeriode = \App\Models\PeriodeWisuda::getActive() ?? \App\Models\PeriodeWisuda::latest()->first();

    $stats = [
        'totalWisudawan' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->count(),
        'tracerCompleted' => \App\Models\Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_tracer_study_filled', true)->count(),
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

// 1. Admin Master Routes
Route::middleware(['auth', 'role:admin_utama'])->prefix('admin')->name('admin.')->group(function () {
    // Periode Wisuda Management
    Route::get('/periode', [PeriodeWisudaController::class, 'index'])->name('periode.index');
    Route::post('/periode', [PeriodeWisudaController::class, 'store'])->name('periode.store');
    Route::patch('/periode/{id}/toggle', [PeriodeWisudaController::class, 'toggleActive'])->name('periode.toggle');

    // Precision Stage Layout Configurator
    Route::get('/stage-layout', [StageLayoutConfigController::class, 'edit'])->name('stage-layout.edit');
    Route::post('/stage-layout', [StageLayoutConfigController::class, 'update'])->name('stage-layout.update');

    // Buku Kenangan PDF & Data Wisudawan
    Route::get('/buku-kenangan', [BukuKenanganController::class, 'index'])->name('buku-kenangan.index');
    Route::get('/buku-kenangan/export', [BukuKenanganController::class, 'exportPdf'])->name('buku-kenangan.export');
});

// 2. Panitia Presensi & Stage Routes
Route::middleware(['auth', 'role:panitia_presensi,admin_utama'])->prefix('panitia')->name('panitia.')->group(function () {
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

// 3. Wisudawan Routes
Route::middleware(['auth', 'role:wisudawan,admin_utama'])->prefix('wisudawan')->name('wisudawan.')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $wisudawan = $user->wisudawan ? $user->wisudawan->load('programStudi') : null;
        return Inertia::render('Wisudawan/Dashboard', [
            'wisudawan' => $wisudawan,
            'stageConfig' => \App\Models\StageLayoutConfig::getDefaultConfig(),
        ]);
    })->name('dashboard');

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
