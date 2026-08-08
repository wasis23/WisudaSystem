<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyaratWisuda;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SyaratWisudaController extends Controller
{
    public function index()
    {
        $syarat = SyaratWisuda::orderBy('id', 'asc')->get();

        return Inertia::render('Admin/Syarat/Index', [
            'syaratList' => $syarat,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_syarat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'format_file' => 'required|string|max:100',
            'max_file_size_kb' => 'required|integer|min:512|max:20480',
            'is_wajib' => 'boolean',
        ]);

        SyaratWisuda::create($validated);

        return redirect()->back()->with('success', 'Persyaratan Wisuda berhasil ditambahkan.');
    }

    public function update(Request $request, SyaratWisuda $syarat)
    {
        $validated = $request->validate([
            'nama_syarat' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'format_file' => 'required|string|max:100',
            'max_file_size_kb' => 'required|integer|min:512|max:20480',
            'is_wajib' => 'boolean',
        ]);

        $syarat->update($validated);

        return redirect()->back()->with('success', 'Persyaratan Wisuda berhasil diperbarui.');
    }

    public function destroy(SyaratWisuda $syarat)
    {
        $syarat->delete();

        return redirect()->back()->with('success', 'Persyaratan Wisuda berhasil dihapus.');
    }
}
