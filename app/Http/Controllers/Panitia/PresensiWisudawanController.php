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

        $query = Wisudawan::with('programStudi')
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

        $wisudawans = $query->orderBy('program_studi_id')->orderBy('nama_lengkap')->paginate(50)->withQueryString();

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
            'isAdmin' => $request->routeIs('admin.*') || str_starts_with($request->path(), 'admin'),
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

        // 1. Search as Wisudawan Token
        $wisudawan = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('qr_code_token', $token)
            ->orWhere('nim', $token)
            ->first();

        $isSecurity = $request->routeIs('security.*') || $request->is('security*') || $request->user()?->role === 'security';
        $isReceptionist = $request->routeIs('receptionist.*') || $request->is('receptionist*') || $request->user()?->role === 'receptionist';

        if ($wisudawan) {
            if ($wisudawan->status_pembayaran_sikeu !== 'lunas') {
                $err = "❌ AKSES DITOLAK: Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) BELUM MELAKUKAN PEMBAYARAN WISUDA di SIKEU!";
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            if ($wisudawan->status_verifikasi !== 'verified') {
                $err = "❌ AKSES DITOLAK: Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) belum lolos verifikasi!";
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            $siakadInfo = $this->siakadService->getStudentByNim($wisudawan->nim);
            $simantaInfo = $this->simantaService->getGraduationStatus($wisudawan->nim);
            $sikeuQuota = $this->sikeuService->getExtraWisudaQuota($wisudawan->nim);

            $scanStatus = 'success';

            // 1. SECURITY GATE SCAN
            if ($isSecurity) {
                if ($wisudawan->is_hadir) {
                    $waktu = $wisudawan->waktu_presensi ? (is_string($wisudawan->waktu_presensi) ? $wisudawan->waktu_presensi : $wisudawan->waktu_presensi->format('H:i:s WIB')) : 'sebelumnya';
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) saat ini berada di DALAM (Presensi Masuk: {$waktu}).";
                    $scanStatus = 'already_scanned';
                } else {
                    $isReentry = !empty($wisudawan->foto_keluar_gate);
                    $wisudawan->update([
                        'is_hadir' => true,
                        'waktu_presensi' => now(),
                        'status_kelulusan_simanta' => $simantaInfo['status_lulus'] ?? 'LULUS',
                        'jumlah_tamu_tambahan' => $sikeuQuota['total_allowed_guests'] ?? 2,
                    ]);
                    if ($isReentry) {
                        $waktuKeluar = $wisudawan->waktu_keluar_gate ? (is_string($wisudawan->waktu_keluar_gate) ? $wisudawan->waktu_keluar_gate : $wisudawan->waktu_keluar_gate->format('H:i:s WIB')) : 'sebelumnya';
                        $message = "🟢 SCAN MASUK KEMBALI BERHASIL! Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) tercatat masuk kembali (Keluar pada {$waktuKeluar}). Foto saat keluar ditampilkan untuk verifikasi.";
                    } else {
                        $message = "🟢 SCAN [SECURITY GATE] BERHASIL! Selamat Datang Wisudawan: {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}). Presensi Gate tercatat.";
                    }
                    $scanStatus = 'success';
                }
            }
            // 2. RECEPTIONIST / VENUE SCAN
            elseif ($isReceptionist) {
                if ($wisudawan->is_in_auditorium) {
                    $waktu = $wisudawan->waktu_presensi_venue ? (is_string($wisudawan->waktu_presensi_venue) ? $wisudawan->waktu_presensi_venue : $wisudawan->waktu_presensi_venue->format('H:i:s WIB')) : 'sebelumnya';
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) sudah tercatat masuk Venue / Receptionist pada {$waktu}.";
                    $scanStatus = 'already_scanned';
                } else {
                    $wisudawan->update([
                        'is_in_auditorium' => true,
                        'waktu_presensi_venue' => now(),
                        'is_hadir' => true,
                        'waktu_presensi' => $wisudawan->waktu_presensi ?? now(),
                    ]);
                    $message = "🔵 SCAN [RECEPTIONIST VENUE] BERHASIL! Wisudawan: {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) resmi memasuki venue & verifikasi snack.";
                    $scanStatus = 'success';
                }
            }
            // 3. GENERAL PANITIA GATE
            else {
                if (!$wisudawan->is_hadir) {
                    $wisudawan->update([
                        'is_hadir' => true,
                        'waktu_presensi' => now(),
                        'status_kelulusan_simanta' => $simantaInfo['status_lulus'] ?? 'LULUS',
                        'jumlah_tamu_tambahan' => $sikeuQuota['total_allowed_guests'] ?? 2,
                    ]);
                    $message = "🟢 SCAN GATE BERHASIL! Selamat Datang Wisudawan: {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}).";
                    $scanStatus = 'success';
                } else {
                    $waktu = $wisudawan->waktu_presensi ? (is_string($wisudawan->waktu_presensi) ? $wisudawan->waktu_presensi : $wisudawan->waktu_presensi->format('H:i:s WIB')) : 'sebelumnya';
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) telah presensi pada {$waktu}.";
                    $scanStatus = 'already_scanned';
                }
            }

            $scannedData = [
                'token' => $token,
                'nama_lengkap' => $wisudawan->nama_lengkap,
                'nim' => $wisudawan->nim,
                'prodi' => $wisudawan->programStudi?->nama_prodi,
                'pas_foto' => $wisudawan->pas_foto ? "/storage/{$wisudawan->pas_foto}" : null,
                'foto_keluar_gate' => $wisudawan->foto_keluar_gate ? "/storage/{$wisudawan->foto_keluar_gate}" : null,
                'waktu_keluar_gate' => $wisudawan->waktu_keluar_gate ? (is_string($wisudawan->waktu_keluar_gate) ? $wisudawan->waktu_keluar_gate : $wisudawan->waktu_keluar_gate->format('H:i:s WIB')) : null,
                'is_reentry' => !empty($wisudawan->foto_keluar_gate),
                'nama_ayah' => $siakadInfo['nama_ayah'] ?? $wisudawan->nama_ayah ?? '-',
                'nama_ibu' => $siakadInfo['nama_ibu'] ?? $wisudawan->nama_ibu ?? '-',
                'status_simanta' => $simantaInfo['status_lulus'] ?? 'LULUS',
                'tamu_kuota' => $sikeuQuota['total_allowed_guests'] ?? 2,
                'snack_porsi' => $sikeuQuota['snack_quota'] ?? 2,
                'is_hadir' => $wisudawan->is_hadir,
                'is_in_auditorium' => $wisudawan->is_in_auditorium,
                'waktu_presensi' => $wisudawan->waktu_presensi ? (is_string($wisudawan->waktu_presensi) ? $wisudawan->waktu_presensi : $wisudawan->waktu_presensi->format('H:i:s WIB')) : '-',
                'waktu_presensi_venue' => $wisudawan->waktu_presensi_venue ? (is_string($wisudawan->waktu_presensi_venue) ? $wisudawan->waktu_presensi_venue : $wisudawan->waktu_presensi_venue->format('H:i:s WIB')) : '-',
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
                    'status' => $scanStatus,
                    'message' => $message,
                    'wisudawan' => $wisudawan,
                    'scanned_data' => $scannedData,
                    'siakad' => $siakadInfo,
                    'simanta' => $simantaInfo,
                    'sikeu' => $sikeuQuota,
                ]);
            }

            return redirect()->back()
                ->with($scanStatus === 'success' ? 'success' : 'warning', $message)
                ->with('scannedWisudawan', $scannedData);
        }

        // 2. Search as Guest Token
        $guest = WisudawanTamuTambahan::with('wisudawan.programStudi')
            ->where('qr_guest_token', $token)
            ->first();

        if ($guest) {
            $wisudawanMain = $guest->wisudawan;

            if ($wisudawanMain && $wisudawanMain->status_pembayaran_sikeu !== 'lunas') {
                $err = "❌ AKSES DITOLAK: Wisudawan {$wisudawanMain->nama_lengkap} (NIM: {$wisudawanMain->nim}) BELUM MELAKUKAN PEMBAYARAN WISUDA di SIKEU!";
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'message' => $err], 422);
                }
                return redirect()->back()->with('error', $err);
            }

            $scanStatus = 'success';

            // 1. SECURITY GATE SCAN (GUEST)
            if ($isSecurity) {
                if ($guest->is_hadir_gate || $guest->is_hadir) {
                    $waktu = $guest->waktu_presensi_gate ? (is_string($guest->waktu_presensi_gate) ? $guest->waktu_presensi_gate : $guest->waktu_presensi_gate->format('H:i:s WIB')) : 'sebelumnya';
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) saat ini berada di DALAM (Presensi Masuk: {$waktu}).";
                    $scanStatus = 'already_scanned';
                } else {
                    $isReentry = !empty($guest->foto_keluar_gate);
                    $guest->update([
                        'is_hadir_gate' => true,
                        'is_hadir' => true,
                        'waktu_presensi_gate' => now(),
                        'waktu_presensi' => now(),
                    ]);
                    if ($isReentry) {
                        $waktuKeluar = $guest->waktu_keluar_gate ? (is_string($guest->waktu_keluar_gate) ? $guest->waktu_keluar_gate : $guest->waktu_keluar_gate->format('H:i:s WIB')) : 'sebelumnya';
                        $message = "🟢 SCAN MASUK KEMBALI BERHASIL! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) masuk kembali (Keluar pada {$waktuKeluar}). Foto saat keluar ditampilkan untuk verifikasi.";
                    } else {
                        $message = "🟢 SCAN [SECURITY GATE] BERHASIL! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}). Presensi Masuk Gate.";
                    }
                    $scanStatus = 'success';
                }
            }
            // 2. RECEPTIONIST / VENUE SCAN (GUEST)
            elseif ($isReceptionist) {
                if ($guest->is_hadir_venue) {
                    $waktu = $guest->waktu_presensi_venue ? (is_string($guest->waktu_presensi_venue) ? $guest->waktu_presensi_venue : $guest->waktu_presensi_venue->format('H:i:s WIB')) : 'sebelumnya';
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) sudah presensi Venue & paket snack telah diserahkan pada {$waktu}.";
                    $scanStatus = 'already_scanned';
                } else {
                    $guest->update([
                        'is_hadir_venue' => true,
                        'snack_diambil' => true,
                        'waktu_presensi_venue' => now(),
                        'is_hadir_gate' => true,
                        'is_hadir' => true,
                        'waktu_presensi_gate' => $guest->waktu_presensi_gate ?? now(),
                    ]);
                    $message = "🔵 SCAN [RECEPTIONIST VENUE] BERHASIL! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) & Penyerahan Paket Snack.";
                    $scanStatus = 'success';
                }
            }
            // 3. GENERAL PANITIA GATE (GUEST)
            else {
                if (!$guest->is_hadir_gate && !$guest->is_hadir) {
                    $guest->update([
                        'is_hadir_gate' => true,
                        'is_hadir' => true,
                        'waktu_presensi_gate' => now(),
                    ]);
                    $message = "🟢 SCAN GATE BERHASIL! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}).";
                    $scanStatus = 'success';
                } else {
                    $message = "⚠️ BARCODE SUDAH PERNAH DI-SCAN! Tamu/Pendamping: {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) sudah pernah presensi gate.";
                    $scanStatus = 'already_scanned';
                }
            }

            $guestData = [
                'token' => $token,
                'nama_tamu' => $guest->nama_tamu,
                'hubungan' => $guest->hubungan,
                'wisudawan_nama' => $wisudawanMain?->nama_lengkap,
                'wisudawan_nim' => $wisudawanMain?->nim,
                'wisudawan_prodi' => $wisudawanMain?->programStudi?->nama_prodi,
                'foto_keluar_gate' => $guest->foto_keluar_gate ? "/storage/{$guest->foto_keluar_gate}" : null,
                'waktu_keluar_gate' => $guest->waktu_keluar_gate ? (is_string($guest->waktu_keluar_gate) ? $guest->waktu_keluar_gate : $guest->waktu_keluar_gate->format('H:i:s WIB')) : null,
                'is_reentry' => !empty($guest->foto_keluar_gate),
                'is_hadir' => $guest->is_hadir,
                'is_hadir_gate' => $guest->is_hadir_gate,
                'is_hadir_venue' => $guest->is_hadir_venue,
                'snack_diambil' => $guest->snack_diambil,
                'waktu_presensi_gate' => $guest->waktu_presensi_gate ? (is_string($guest->waktu_presensi_gate) ? $guest->waktu_presensi_gate : $guest->waktu_presensi_gate->format('H:i:s WIB')) : '-',
                'waktu_presensi_venue' => $guest->waktu_presensi_venue ? (is_string($guest->waktu_presensi_venue) ? $guest->waktu_presensi_venue : $guest->waktu_presensi_venue->format('H:i:s WIB')) : '-',
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => $scanStatus,
                    'message' => $message,
                    'guest' => $guest,
                    'guest_data' => $guestData,
                ]);
            }
            return redirect()->back()
                ->with($scanStatus === 'success' ? 'success' : 'warning', $message)
                ->with('scannedGuest', $guestData);
        }

        $notFoundErr = "❌ QR Code / NIM ($token) Tidak Ditemukan! Pastikan barcode / NIM valid.";
        if ($request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $notFoundErr], 404);
        }
        return redirect()->back()->with('error', $notFoundErr);
    }

    /**
     * Action Check-Out (Keluar Sementara & Upload Foto Wajah Keluar)
     */
    public function checkoutGate(Request $request)
    {
        $request->validate([
            'qr_code_token' => 'required|string',
            'foto_wajah' => 'nullable|string',
        ]);

        $token = trim($request->qr_code_token);
        $fotoPath = null;

        // Process Base64 photo if provided
        if ($request->filled('foto_wajah') && str_starts_with($request->foto_wajah, 'data:image')) {
            $imageParts = explode(';base64,', $request->foto_wajah);
            $imageTypeAux = explode('image/', $imageParts[0]);
            $imageType = $imageTypeAux[1] ?? 'jpg';
            $imageBase64 = base64_decode($imageParts[1]);

            $fileName = 'exit_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $imageType;
            \Illuminate\Support\Facades\Storage::disk('public')->put('exit_photos/' . $fileName, $imageBase64);
            $fotoPath = 'exit_photos/' . $fileName;
        } elseif ($request->hasFile('foto_wajah')) {
            $fotoPath = $request->file('foto_wajah')->store('exit_photos', 'public');
        }

        // 1. Search as Wisudawan
        $wisudawan = Wisudawan::with(['programStudi', 'tamuTambahan'])
            ->where('qr_code_token', $token)
            ->orWhere('nim', $token)
            ->first();

        if ($wisudawan) {
            $wisudawan->update([
                'is_hadir' => false,
                'foto_keluar_gate' => $fotoPath ?? $wisudawan->foto_keluar_gate,
                'waktu_keluar_gate' => now(),
            ]);

            $message = "🚪 CHECK-OUT KELUAR BERHASIL: Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) diizinkan keluar sementara. Foto wajah tersimpan.";

            $data = [
                'token' => $token,
                'nama_lengkap' => $wisudawan->nama_lengkap,
                'nim' => $wisudawan->nim,
                'prodi' => $wisudawan->programStudi?->nama_prodi,
                'pas_foto' => $wisudawan->pas_foto ? "/storage/{$wisudawan->pas_foto}" : null,
                'foto_keluar_gate' => $wisudawan->foto_keluar_gate ? "/storage/{$wisudawan->foto_keluar_gate}" : null,
                'waktu_keluar_gate' => now()->format('H:i:s WIB'),
                'is_hadir' => false,
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'checkout_success',
                    'message' => $message,
                    'wisudawan' => $wisudawan,
                    'scanned_data' => $data,
                ]);
            }

            return redirect()->back()->with('success', $message);
        }

        // 2. Search as Guest
        $guest = WisudawanTamuTambahan::with('wisudawan.programStudi')
            ->where('qr_guest_token', $token)
            ->first();

        if ($guest) {
            $wisudawanMain = $guest->wisudawan;
            $guest->update([
                'is_hadir' => false,
                'is_hadir_gate' => false,
                'foto_keluar_gate' => $fotoPath ?? $guest->foto_keluar_gate,
                'waktu_keluar_gate' => now(),
            ]);

            $message = "🚪 CHECK-OUT KELUAR BERHASIL: Tamu/Pendamping {$guest->nama_tamu} (Wisudawan: {$wisudawanMain?->nama_lengkap}) diizinkan keluar sementara. Foto wajah tersimpan.";

            $data = [
                'token' => $token,
                'nama_tamu' => $guest->nama_tamu,
                'hubungan' => $guest->hubungan,
                'wisudawan_nama' => $wisudawanMain?->nama_lengkap,
                'wisudawan_nim' => $wisudawanMain?->nim,
                'wisudawan_prodi' => $wisudawanMain?->programStudi?->nama_prodi,
                'foto_keluar_gate' => $guest->foto_keluar_gate ? "/storage/{$guest->foto_keluar_gate}" : null,
                'waktu_keluar_gate' => now()->format('H:i:s WIB'),
                'is_hadir' => false,
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'checkout_success',
                    'message' => $message,
                    'guest' => $guest,
                    'guest_data' => $data,
                ]);
            }

            return redirect()->back()->with('success', $message);
        }

        return response()->json(['status' => 'error', 'message' => "Data token ($token) tidak ditemukan."], 404);
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

    /**
     * Action Setujui Masuk Kembali (Reset Foto Keluar & Waktu Keluar Gate)
     */
    public function approveReentry(Request $request)
    {
        $request->validate([
            'qr_code_token' => 'required|string',
        ]);

        $token = trim($request->qr_code_token);

        // 1. Search Wisudawan
        $wisudawan = Wisudawan::where('qr_code_token', $token)
            ->orWhere('nim', $token)
            ->first();

        if ($wisudawan) {
            if ($wisudawan->foto_keluar_gate && \Illuminate\Support\Facades\Storage::disk('public')->exists($wisudawan->foto_keluar_gate)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($wisudawan->foto_keluar_gate);
            }
            $wisudawan->update([
                'is_hadir' => true,
                'foto_keluar_gate' => null,
                'waktu_keluar_gate' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Verifikasi masuk kembali disetujui. Foto keluar wisudawan {$wisudawan->nama_lengkap} telah di-reset.",
            ]);
        }

        // 2. Search Guest
        $guest = WisudawanTamuTambahan::where('qr_guest_token', $token)->first();
        if ($guest) {
            if ($guest->foto_keluar_gate && \Illuminate\Support\Facades\Storage::disk('public')->exists($guest->foto_keluar_gate)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($guest->foto_keluar_gate);
            }
            $guest->update([
                'is_hadir' => true,
                'is_hadir_gate' => true,
                'foto_keluar_gate' => null,
                'waktu_keluar_gate' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Verifikasi masuk kembali disetujui. Foto keluar tamu {$guest->nama_tamu} telah di-reset.",
            ]);
        }

        return response()->json(['status' => 'error', 'message' => 'Token tidak ditemukan.'], 404);
    }
}
