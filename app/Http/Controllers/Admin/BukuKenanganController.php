<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BukuKenanganController extends Controller
{
    public function index(Request $request)
    {
        $periodes = PeriodeWisuda::orderBy('id', 'desc')->get();
        $selectedPeriodeId = $request->periode_id ?? (PeriodeWisuda::getActive()?->id ?? $periodes->first()?->id);
        $programStudis = ProgramStudi::all();

        $query = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $selectedPeriodeId);

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        $wisudawans = $query->orderBy('program_studi_id')->orderBy('ipk', 'desc')->get();

        return Inertia::render('Admin/BukuKenangan/Index', [
            'periodes' => $periodes,
            'selectedPeriodeId' => (int) $selectedPeriodeId,
            'programStudis' => $programStudis,
            'wisudawans' => $wisudawans,
            'filters' => $request->only(['periode_id', 'program_studi_id']),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $selectedPeriodeId = $request->periode_id ?? (PeriodeWisuda::getActive()?->id ?? PeriodeWisuda::latest()->first()?->id);
        
        if (!$selectedPeriodeId) {
            return redirect()->back()->with('error', 'Belum ada periode wisuda.');
        }

        $periode = PeriodeWisuda::findOrFail($selectedPeriodeId);

        $query = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $periode->id)
            ->where('status_verifikasi', 'verified');

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        $wisudawans = $query->orderBy('program_studi_id')->orderBy('ipk', 'desc')->get();

        $groupedByProdi = $wisudawans->groupBy(function ($w) {
            return $w->programStudi ? $w->programStudi->nama_prodi : 'Lainnya';
        });

        $pdf = Pdf::loadView('pdf.buku_kenangan', [
            'periode' => $periode,
            'groupedByProdi' => $groupedByProdi,
            'totalPeserta' => $wisudawans->count(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Buku_Kenangan_Wisuda_{$periode->nomor_periode}.pdf");
    }
}
