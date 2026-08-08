<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodeWisudaController extends Controller
{
    public function index()
    {
        $periodes = PeriodeWisuda::withCount('wisudawan')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Periode/Index', [
            'periodes' => $periodes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_periode' => 'required|string|max:255',
            'nomor_periode' => 'required|integer',
            'tahun_akademik' => 'required|string|max:50',
            'tanggal_pelaksanaan' => 'required|date',
            'kuota_peserta' => 'nullable|integer',
            'tanggal_buka_pendaftaran' => 'required|date',
            'tanggal_tutup_pendaftaran' => 'required|date|after_or_equal:tanggal_buka_pendaftaran',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_active']) && $validated['is_active']) {
            PeriodeWisuda::where('is_active', true)->update(['is_active' => false]);
        }

        PeriodeWisuda::create($validated);

        return redirect()->back()->with('success', 'Periode Wisuda berhasil ditambahkan.');
    }

    public function update(Request $request, PeriodeWisuda $periode)
    {
        $validated = $request->validate([
            'nama_periode' => 'required|string|max:255',
            'nomor_periode' => 'required|integer',
            'tahun_akademik' => 'required|string|max:50',
            'tanggal_pelaksanaan' => 'required|date',
            'kuota_peserta' => 'nullable|integer',
            'tanggal_buka_pendaftaran' => 'required|date',
            'tanggal_tutup_pendaftaran' => 'required|date|after_or_equal:tanggal_buka_pendaftaran',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['is_active']) && $validated['is_active']) {
            PeriodeWisuda::where('id', '!=', $periode->id)->update(['is_active' => false]);
        }

        $periode->update($validated);

        return redirect()->back()->with('success', 'Periode Wisuda berhasil diperbarui.');
    }

    public function toggleActive(PeriodeWisuda $periode)
    {
        if (!$periode->is_active) {
            PeriodeWisuda::where('id', '!=', $periode->id)->update(['is_active' => false]);
            $periode->update(['is_active' => true]);
        } else {
            $periode->update(['is_active' => false]);
        }

        return redirect()->back()->with('success', 'Status periode wisuda berhasil diperbarui.');
    }

    public function syncSiakad(Request $request)
    {
        $limit = $request->input('limit', 500);
        $periodeId = $request->input('periode_id');

        \Illuminate\Support\Facades\Artisan::call('wisuda:sync-siakad', array_filter([
            '--limit' => $limit,
            '--periode' => $periodeId,
        ]));

        return redirect()->back()->with('success', 'Berhasil menarik data wisudawan dari database SIAKAD.');
    }
}
