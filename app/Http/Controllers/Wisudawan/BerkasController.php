<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\BerkasWisudawan;
use App\Models\SyaratWisuda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BerkasController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            return redirect()->route('wisudawan.pendaftaran.form')->with('warning', 'Harap lengkapi biodata pendaftaran wisuda terlebih dahulu.');
        }

        $syaratList = SyaratWisuda::orderBy('id', 'asc')->get();
        $berkasList = BerkasWisudawan::where('wisudawan_id', $wisudawan->id)
            ->with('syaratWisuda')
            ->get()
            ->keyBy('syarat_wisuda_id');

        return Inertia::render('Wisudawan/Berkas', [
            'wisudawan' => $wisudawan,
            'syaratList' => $syaratList,
            'berkasList' => $berkasList,
        ]);
    }

    public function upload(Request $request, SyaratWisuda $syarat)
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            return redirect()->back()->with('error', 'Wisudawan belum terdaftar.');
        }

        $allowedFormats = explode(',', str_replace(' ', '', strtolower($syarat->format_file)));
        $mimesPattern = implode(',', $allowedFormats);

        $request->validate([
            'file' => "required|file|mimes:{$mimesPattern}|max:{$syarat->max_file_size_kb}",
        ], [
            'file.mimes' => "Format file harus berupa: {$syarat->format_file}",
            'file.max' => "Ukuran file maksimal " . ($syarat->max_file_size_kb / 1024) . " MB",
        ]);

        $existingBerkas = BerkasWisudawan::where('wisudawan_id', $wisudawan->id)
            ->where('syarat_wisuda_id', $syarat->id)
            ->first();

        if ($existingBerkas && $existingBerkas->file_path) {
            Storage::disk('public')->delete($existingBerkas->file_path);
        }

        $file = $request->file('file');
        $filePath = $file->store('berkas_wisudawan', 'public');

        BerkasWisudawan::updateOrCreate(
            [
                'wisudawan_id' => $wisudawan->id,
                'syarat_wisuda_id' => $syarat->id,
            ],
            [
                'file_path' => $filePath,
                'original_filename' => $file->getClientOriginalName(),
                'status' => 'pending',
                'catatan' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        // Update overall wisudawan status to pending if it was rejected
        if ($wisudawan->status_verifikasi === 'rejected') {
            $wisudawan->update(['status_verifikasi' => 'pending']);
        }

        return redirect()->back()->with('success', "Berkas {$syarat->nama_syarat} berhasil diunggah.");
    }
}
