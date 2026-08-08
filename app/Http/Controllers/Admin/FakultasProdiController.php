<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FakultasProdiController extends Controller
{
    public function index()
    {
        $fakultas = Fakultas::with(['programStudi' => function ($q) {
            $q->withCount('wisudawan');
        }])->get();

        return Inertia::render('Admin/FakultasProdi/Index', [
            'fakultas' => $fakultas,
        ]);
    }

    public function storeFakultas(Request $request)
    {
        $validated = $request->validate([
            'kode_fakultas' => 'required|string|max:50|unique:fakultas,kode_fakultas',
            'nama_fakultas' => 'required|string|max:255',
            'dekan_nama' => 'nullable|string|max:255',
            'dekan_nip' => 'nullable|string|max:100',
        ]);

        Fakultas::create($validated);

        return redirect()->back()->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function storeProdi(Request $request)
    {
        $validated = $request->validate([
            'fakultas_id' => 'required|exists:fakultas,id',
            'kode_prodi' => 'required|string|max:50|unique:program_studi,kode_prodi',
            'nama_prodi' => 'required|string|max:255',
            'jenjang' => 'required|in:D3,D4,S1,S2,S3',
            'kaprodi_nama' => 'nullable|string|max:255',
            'kaprodi_nip' => 'nullable|string|max:100',
        ]);

        ProgramStudi::create($validated);

        return redirect()->back()->with('success', 'Program Studi berhasil ditambahkan.');
    }
}
