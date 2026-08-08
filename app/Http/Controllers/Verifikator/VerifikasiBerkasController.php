<?php

namespace App\Http\Controllers\Verifikator;

use App\Http\Controllers\Controller;
use App\Models\BerkasWisudawan;
use App\Models\SyaratWisuda;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerifikasiBerkasController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Wisudawan::with(['programStudi', 'periodeWisuda', 'berkas']);

        // Scope by prodi if user is verifikator_prodi
        if ($user->isVerifikatorProdi() && $user->program_studi_id) {
            $query->where('program_studi_id', $user->program_studi_id);
        }

        // Filter by status if requested
        if ($request->has('status') && in_array($request->status, ['pending', 'verified', 'rejected'])) {
            $query->where('status_verifikasi', $request->status);
        }

        // Search by NIM or Nama
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nim', 'like', "%{$search}%")
                  ->orWhere('nama_lengkap', 'like', "%{$search}%");
            });
        }

        $wisudawans = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'pending' => Wisudawan::when($user->isVerifikatorProdi() && $user->program_studi_id, fn($q) => $q->where('program_studi_id', $user->program_studi_id))->where('status_verifikasi', 'pending')->count(),
            'verified' => Wisudawan::when($user->isVerifikatorProdi() && $user->program_studi_id, fn($q) => $q->where('program_studi_id', $user->program_studi_id))->where('status_verifikasi', 'verified')->count(),
            'rejected' => Wisudawan::when($user->isVerifikatorProdi() && $user->program_studi_id, fn($q) => $q->where('program_studi_id', $user->program_studi_id))->where('status_verifikasi', 'rejected')->count(),
        ];

        return Inertia::render('Verifikator/Index', [
            'wisudawans' => $wisudawans,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Wisudawan $wisudawan)
    {
        $wisudawan->load(['programStudi.fakultas', 'periodeWisuda', 'berkas.syaratWisuda', 'berkas.verifier', 'kutipan']);
        $syaratList = SyaratWisuda::all();

        return Inertia::render('Verifikator/Show', [
            'wisudawan' => $wisudawan,
            'syaratList' => $syaratList,
        ]);
    }

    public function approveBerkas(BerkasWisudawan $berkas)
    {
        $berkas->update([
            'status' => 'approved',
            'catatan' => null,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $wisudawan = $berkas->wisudawan;

        // Check if all mandatory requirements are approved
        $wajibSyaratIds = SyaratWisuda::where('is_wajib', true)->pluck('id');
        $approvedWajibCount = BerkasWisudawan::where('wisudawan_id', $wisudawan->id)
            ->whereIn('syarat_wisuda_id', $wajibSyaratIds)
            ->where('status', 'approved')
            ->count();

        if ($approvedWajibCount >= $wajibSyaratIds->count()) {
            $wisudawan->update([
                'status_verifikasi' => 'verified',
                'catatan_verifikasi' => null,
            ]);
        }

        return redirect()->back()->with('success', 'Berkas berhasil disetujui (Approved).');
    }

    public function rejectBerkas(Request $request, BerkasWisudawan $berkas)
    {
        $request->validate([
            'catatan' => 'required|string|max:500',
        ]);

        $berkas->update([
            'status' => 'rejected',
            'catatan' => $request->catatan,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $wisudawan = $berkas->wisudawan;
        $wisudawan->update([
            'status_verifikasi' => 'rejected',
            'catatan_verifikasi' => "Berkas {$berkas->syaratWisuda->nama_syarat} ditolak: {$request->catatan}",
        ]);

        return redirect()->back()->with('success', 'Berkas berhasil ditolak dengan catatan.');
    }
}
