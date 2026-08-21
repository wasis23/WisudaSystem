<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use App\Models\SimpegEmployeeCache;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramStudiAdminController extends Controller
{
    public function index()
    {
        $programStudis = ProgramStudi::withCount('wisudawans')
            ->orderBy('id', 'asc')
            ->get();

        $dosenList = SimpegEmployeeCache::dosen()
            ->orderBy('nama', 'asc')
            ->get(['id', 'nama', 'nip', 'nidn'])
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'nama' => $d->nama,
                    'nip' => $d->nip ?: ($d->nidn ?: '-'),
                ];
            });

        return Inertia::render('Admin/ProgramStudi/Index', [
            'programStudis' => $programStudis,
            'dosenList' => $dosenList,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_prodi' => 'required|string|max:50|unique:program_studi,kode_prodi',
            'nama_prodi' => 'required|string|max:255',
            'jenjang' => 'required|in:D3,D4,S1,S2,S3',
            'gelar' => 'nullable|string|max:100',
            'kaprodi_nama' => 'nullable|string|max:255',
            'kaprodi_nip' => 'nullable|string|max:100',
        ]);

        ProgramStudi::create($validated);

        return redirect()->back()->with('success', 'Program Studi baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $prodi = ProgramStudi::findOrFail($id);

        $validated = $request->validate([
            'kode_prodi' => 'required|string|max:50|unique:program_studi,kode_prodi,' . $prodi->id,
            'nama_prodi' => 'required|string|max:255',
            'jenjang' => 'required|in:D3,D4,S1,S2,S3',
            'gelar' => 'nullable|string|max:100',
            'kaprodi_nama' => 'nullable|string|max:255',
            'kaprodi_nip' => 'nullable|string|max:100',
        ]);

        $prodi->update($validated);

        return redirect()->back()->with('success', "Gelar dan data Program Studi {$prodi->nama_prodi} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $prodi = ProgramStudi::withCount('wisudawans')->findOrFail($id);

        if ($prodi->wisudawans_count > 0) {
            return redirect()->back()->with('error', 'Program Studi tidak dapat dihapus karena masih memiliki data wisudawan terikat.');
        }

        $prodi->delete();

        return redirect()->back()->with('success', 'Program Studi berhasil dihapus.');
    }
}
