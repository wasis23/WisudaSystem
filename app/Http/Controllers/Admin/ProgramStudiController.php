<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramStudiController extends Controller
{
    public function index()
    {
        $programStudis = ProgramStudi::withCount('wisudawans')->get();

        return Inertia::render('Admin/ProgramStudi/Index', [
            'programStudis' => $programStudis,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_prodi' => 'required|string|unique:program_studi,kode_prodi',
            'nama_prodi' => 'required|string|max:255',
            'jenjang' => 'required|string|in:D3,D4,S1',
            'kaprodi_nama' => 'nullable|string|max:255',
            'kaprodi_nip' => 'nullable|string|max:100',
        ]);

        ProgramStudi::create($validated);

        return redirect()->back()->with('success', 'Program Studi berhasil ditambahkan.');
    }

    public function update(Request $request, ProgramStudi $prodi)
    {
        $validated = $request->validate([
            'kode_prodi' => 'required|string|unique:program_studi,kode_prodi,' . $prodi->id,
            'nama_prodi' => 'required|string|max:255',
            'jenjang' => 'required|string|in:D3,D4,S1',
            'kaprodi_nama' => 'nullable|string|max:255',
            'kaprodi_nip' => 'nullable|string|max:100',
        ]);

        $prodi->update($validated);

        return redirect()->back()->with('success', 'Program Studi berhasil diperbarui.');
    }
}
