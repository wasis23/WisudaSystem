<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PresensiWisudawanController extends Controller
{
    /**
     * Halaman 1: Presensi Gate (Scanner Barcode / Kamera)
     */
    public function index()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $query = Wisudawan::with(['programStudi'])
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
     * Halaman 2: Presensi Wisudawan (Daftar & Status Kehadiran / Auditorium)
     */
    public function listWisudawan(Request $request)
    {
        $periodes = PeriodeWisuda::orderBy('id', 'desc')->get();
        $activePeriode = PeriodeWisuda::getActive() ?? $periodes->first();
        $selectedPeriodeId = $request->periode_id ?? $activePeriode?->id;
        $programStudis = ProgramStudi::all();

        $query = Wisudawan::with(['programStudi'])
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
     * Action Scan QR Code Token
     */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_code_token' => 'required|string',
        ]);

        $token = trim($request->qr_code_token);

        $wisudawan = Wisudawan::with(['programStudi'])
            ->where('qr_code_token', $token)
            ->first();

        if (!$wisudawan) {
            return redirect()->back()->with('error', "QR Code Invalid ($token). Data wisudawan tidak ditemukan.");
        }

        if ($wisudawan->status_verifikasi !== 'verified') {
            return redirect()->back()->with('error', "AKSES DITOLAK: Wisudawan {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}) belum lolos verifikasi!");
        }

        if ($wisudawan->is_hadir) {
            return redirect()->back()->with('warning', "RE-SCAN NOTICE: {$wisudawan->nama_lengkap} sudah tercatat presensi pada " . $wisudawan->waktu_presensi);
        }

        $wisudawan->update([
            'is_hadir' => true,
            'is_in_auditorium' => true,
            'waktu_presensi' => now(),
        ]);

        return redirect()->back()->with('success', "PRESENSI BERHASIL! Selamat Datang, {$wisudawan->nama_lengkap} (NIM: {$wisudawan->nim}). Telah masuk Auditorium.");
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
