<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
use App\Models\WisudawanTamuTambahan;
use App\Services\SiakadIntegrationService;
use App\Services\SimantaIntegrationService;
use App\Services\SikeuIntegrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PresensiWisudawanController extends Controller
{
    protected SiakadIntegrationService $siakadService;
    protected SimantaIntegrationService $simantaService;
    protected SikeuIntegrationService $sikeuService;

    public function __construct(
        SiakadIntegrationService $siakadService,
        SimantaIntegrationService $simantaService,
        SikeuIntegrationService $sikeuService
    ) {
        $this->siakadService = $siakadService;
        $this->simantaService = $simantaService;
        $this->sikeuService = $sikeuService;
    }

    /**
     * Halaman 1: Presensi Gate (Scanner Barcode / Kamera)
     */
    public function index()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $query = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('periode_wisuda_id', $activePeriode?->id)
            ->where('status_verifikasi', 'verified');

        $stats = [
            'total_verified' => (clone $query)->count(),
            'hadir' => (clone $query)->where('is_hadir', true)->count(),
            'belum_hadir' => (clone $query)->where('is_hadir', false)->count(),
            'in_auditorium' => (clone $query)->where('is_in_auditorium', true)->count(),
        ];

        $recentAttendance = (clone $query)->where('is_hadir', true)
            ->orderBy('waktu_presensi', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Panitia/PresensiScan', [
            'activePeriode' => $activePeriode,
            'stats' => $stats,
            'recentAttendance' => $recentAttendance,
        ]);
    }

    /**
     * Mobile Security Scanner View
     */
    public function mobileSecurityScan()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $stats = [
            'total_security_scanned' => Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', true)->count(),
        ];

        return Inertia::render('Scan/MobileSecurityScanner', [
            'activePeriode' => $activePeriode,
            'stats' => $stats,
        ]);
    }

    /**
     * Mobile Receptionist Scanner View
     */
    public function mobileReceptionistScan()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $stats = [
            'total_reception_scanned' => Wisudawan::where('periode_wisuda_id', $activePeriode?->id)->where('is_hadir', true)->count(),
            'total_snack_issued' => WisudawanTamuTambahan::where('snack_diambil', true)->count(),
        ];

        return Inertia::render('Scan/MobileReceptionistScanner', [
            'activePeriode' => $activePeriode,
            'stats' => $stats,
        ]);
    }

    /**
     * Halaman 2: Presensi Wisudawan (Daftar & Status Kehadiran / Auditorium)
     */
    public function listWisudawan(Request $request)
    {
        $periodes = PeriodeWisuda::orderBy('id', 'desc')->get();
        $activePeriode = PeriodeWisuda::getActive() ?? $periodes->first();
        $selectedPeriodeId = $request->periode_id ?? $activePeriode?->id;
        $programStudis = ProgramStudi::all();

        $query = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('periode_wisuda_id', $selectedPeriodeId)
            ->where('status_verifikasi', 'verified');

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'belum_hadir') {
                $query->where('is_hadir', false);
            } elseif ($request->status === 'hadir') {
                $query->where('is_hadir', true)->where('is_in_auditorium', false);
            } elseif ($request->status === 'in_auditorium') {
                $query->where('is_in_auditorium', true);
            }
        }

        $wisudawans = $query->orderBy('program_studi_id')->orderBy('nama_lengkap')->get();

        $baseQuery = Wisudawan::where('periode_wisuda_id', $selectedPeriodeId)->where('status_verifikasi', 'verified');
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'belum_hadir' => (clone $baseQuery)->where('is_hadir', false)->count(),
            'hadir' => (clone $baseQuery)->where('is_hadir', true)->where('is_in_auditorium', false)->count(),
            'in_auditorium' => (clone $baseQuery)->where('is_in_auditorium', true)->count(),
        ];

        return Inertia::render('Panitia/PresensiList', [
            'periodes' => $periodes,
            'selectedPeriodeId' => (int) $selectedPeriodeId,
            'programStudis' => $programStudis,
            'wisudawans' => $wisudawans,
            'counts' => $counts,
            'filters' => $request->only(['periode_id', 'program_studi_id', 'status', 'search']),
        ]);
    }

    /**
     * Action Scan QR Code Token (Web & Mobile API scan)
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_code_token' => 'required|string',
        ]);

        $token = trim($request->qr_code_token);

        $wisudawan = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('qr_code_token', $token)
            ->orWhere('nim', $token)
            ->first();

        if (!$wisudawan) {
            return redirect()->back()->with('error', "QR Code Invalid ($token). Data wisudawan tidak ditemukan.");
        }

        if ($wisudawan->status_verifikasi !== 'verified') {
            return redirect()->back()->with('error', "AKSES DITOLAK: Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) belum lolos verifikasi!");
        }

        // Retrieve extra guest allowance from SIKEU & graduation status from SIMANTA
        $simantaInfo = $this->simantaService->getGraduationStatus($wisudawan->nim);
        $sikeuQuota = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);
        $siakadInfo = $this->siakadService->getStudentByNim($wisudawan->nim);

        $wisudawan->update([
            'is_hadir' => true,
            'is_in_auditorium' => true,
            'waktu_presensi' => $wisudawan->waktu_presensi ?? now(),
            'status_kelulusan_simanta' => $simantaInfo['status_lulus'],
            'jumlah_tamu_tambahan' => $sikeuQuota['total_allowed_guests'],
        ]);

        $message = "PRESENSI BERHASIL! Selamat Datang, {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}). Kuota Tamu & Snack: {$sikeuQuota['snack_quota']} Porsi.";

        $scannedData = [
            'nama_lengkap' => $wisudawan->nama_lengkap,
            'nim' => $wisudawan->nim,
            'prodi' => $wisudawan->programStudi?->nama_prodi,
            'pas_foto' => $wisudawan->pas_foto ? "/storage/{$wisudawan->pas_foto}" : null,
            'nama_ayah' => $siakadInfo['nama_ayah'] ?? $wisudawan->nama_ayah ?? 'Data SIAKAD',
            'nama_ibu' => $siakadInfo['nama_ibu'] ?? $wisudawan->nama_ibu ?? 'Data SIAKAD',
            'status_simanta' => $simantaInfo['status_lulus'],
            'tamu_kuota' => $sikeuQuota['total_allowed_guests'],
            'snack_porsi' => $sikeuQuota['snack_quota'],
            'waktu_presensi' => $wisudawan->waktu_presensi ? (is_string($wisudawan->waktu_presensi) ? $wisudawan->waktu_presensi : $wisudawan->waktu_presensi->format('H:i:s WIB')) : now()->format('H:i:s WIB'),
            'tamu_tambahan_list' => $wisudawan->tamuTambahan ? $wisudawan->tamuTambahan->map(function($t) {
                return [
                    'id' => $t->id,
                    'nama_tamu' => $t->nama_tamu,
                    'hubungan' => $t->hubungan,
                    'is_hadir' => $t->is_hadir,
                    'snack_diambil' => $t->snack_diambil,
                ];
            }) : [],
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'wisudawan' => $wisudawan,
                'scanned_data' => $scannedData,
                'siakad' => $siakadInfo,
                'simanta' => $simantaInfo,
                'sikeu' => $sikeuQuota,
            ]);
        }

        return redirect()->back()
            ->with('success', $message)
            ->with('scannedWisudawan', $scannedData);
    }

    /**
     * Process Guest Attendance & Snack Issuance
     */
    public function processGuestAttendance(Request $request, $id)
    {
        $guest = WisudawanTamuTambahan::findOrFail($id);

        $guest->update([
            'is_hadir' => $request->has('is_hadir') ? $request->boolean('is_hadir') : !$guest->is_hadir,
            'snack_diambil' => $request->has('snack_diambil') ? $request->boolean('snack_diambil') : !$guest->snack_diambil,
            'waktu_presensi' => now(),
        ]);

        return redirect()->back()->with('success', "Data kehadiran tamu {$guest->nama_tamu} berhasil diperbarui.");
    }

    /**
     * Action Toggle Status Kehadiran / Auditorium Manual
     */
    public function toggleStatus(Request $request, $id)
    {
        $wisudawan = Wisudawan::findOrFail($id);
        $field = $request->input('field', 'is_hadir');

        if ($field === 'is_hadir') {
            $newHadir = !$wisudawan->is_hadir;
            $wisudawan->update([
                'is_hadir' => $newHadir,
                'is_in_auditorium' => $newHadir ? true : false,
                'waktu_presensi' => $newHadir ? now() : null,
            ]);
        } elseif ($field === 'is_in_auditorium') {
            $newAuditorium = !$wisudawan->is_in_auditorium;
            $wisudawan->update([
                'is_in_auditorium' => $newAuditorium,
                'is_hadir' => $newAuditorium ? true : $wisudawan->is_hadir,
                'waktu_presensi' => ($newAuditorium && !$wisudawan->waktu_presensi) ? now() : $wisudawan->waktu_presensi,
            ]);
        }

        return redirect()->back()->with('success', "Status presensi {$wisudawan->nama_lengkap} berhasil diperbarui.");
    }
}
