<?php

namespace App\Http\Controllers;

use App\Models\PeriodeWisuda;
use App\Models\Wisudawan;
use App\Services\SiakadIntegrationService;
use App\Services\SimantaIntegrationService;
use App\Services\SikeuIntegrationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KioskScanController extends Controller
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

    public function index()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        return Inertia::render('Scan/SelfServiceKiosk', [
            'activePeriode' => $activePeriode,
        ]);
    }

    public function scan(Request $request)
    {
        $token = trim($request->input('qr_code_token', ''));

        if (empty($token)) {
            return response()->json(['status' => 'error', 'message' => 'Token QR tidak boleh kosong.'], 400);
        }

        $wisudawan = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('qr_code_token', $token)
            ->orWhere('nim', $token)
            ->first();

        if (!$wisudawan) {
            return response()->json([
                'status' => 'error',
                'message' => "QR Code/NIM '{$token}' tidak terdaftar di sistem Wisuda.",
            ], 404);
        }

        // Fetch integration details from SIAKAD, SIMANTA, SIKEU
        $siakadInfo = $this->siakadService->getStudentByNim($wisudawan->nim);
        $simantaInfo = $this->simantaService->getGraduationStatus($wisudawan->nim);
        $sikeuQuota = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);

        $wasAlreadyIn = $wisudawan->is_in_auditorium;

        // Check in
        $wisudawan->update([
            'is_hadir' => true,
            'is_in_auditorium' => true,
            'waktu_presensi' => $wisudawan->waktu_presensi ?? now(),
            'status_kelulusan_simanta' => $simantaInfo['status_lulus'],
            'jumlah_tamu_tambahan' => $sikeuQuota['total_allowed_guests'],
        ]);

        return response()->json([
            'status' => 'success',
            'was_already_in' => $wasAlreadyIn,
            'message' => $wasAlreadyIn
                ? "Selamat Datang Kembali, {$wisudawan->nama_lengkap}!"
                : "Presensi Berhasil! Selamat Datang, {$wisudawan->nama_lengkap}!",
            'wisudawan' => [
                'id' => $wisudawan->id,
                'nim' => $wisudawan->nim,
                'nama_lengkap' => $wisudawan->nama_lengkap,
                'prodi' => $wisudawan->programStudi?->nama_prodi ?? 'Politeknik Indonusa',
                'ipk' => $wisudawan->ipk ?? '3.50',
                'pas_foto' => $wisudawan->pas_foto ? asset('storage/' . $wisudawan->pas_foto) : '/img/avatar_placeholder.png',
                'nama_ibu' => $siakadInfo['nama_ibu'] ?? $wisudawan->nama_ibu ?? '-',
                'nama_ayah' => $siakadInfo['nama_ayah'] ?? $wisudawan->nama_ayah ?? '-',
                'status_simanta' => $simantaInfo['status_lulus'],
                'tamu_kuota' => $sikeuQuota['total_allowed_guests'],
                'snack_total' => $sikeuQuota['snack_quota'],
                'waktu_presensi' => now()->format('H:i:s WIB'),
            ]
        ]);
    }
}
