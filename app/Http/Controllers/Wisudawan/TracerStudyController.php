<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TracerStudyController extends Controller
{
    public function showForm()
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        return Inertia::render('Wisudawan/TracerStudy', [
            'wisudawan' => $wisudawan,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'tracer_status_pekerjaan' => 'required|string|max:255',
            'tracer_nama_instansi' => 'nullable|string|max:255',
            'tracer_jabatan' => 'nullable|string|max:255',
            'tracer_pendapatan' => 'nullable|string|max:255',
            'tracer_kesesuaian_prodi' => 'required|string|max:255',
        ]);

        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            // Create initial placeholder wisudawan if not existing
            $periodeActive = \App\Models\PeriodeWisuda::getActive();
            $prodiDefault = \App\Models\ProgramStudi::first();

            $wisudawan = Wisudawan::create([
                'user_id' => $user->id,
                'periode_wisuda_id' => $periodeActive ? $periodeActive->id : 1,
                'program_studi_id' => $user->program_studi_id ?? ($prodiDefault ? $prodiDefault->id : 1),
                'nim' => $user->email,
                'nama_lengkap' => $user->name,
                'tempat_lahir' => '-',
                'tanggal_lahir' => now()->subYears(20)->toDateString(),
                'jenis_kelamin' => 'L',
                'email' => $user->email,
                'nomor_hp' => '-',
                'ipk' => 3.50,
                'predikat_kelulusan' => 'Dengan Pujian',
                'tanggal_lulus' => now()->toDateString(),
                'judul_ta' => '-',
                'qr_code_token' => 'QR-' . strtoupper(\Illuminate\Support\Str::random(8)),
            ]);
        }

        $wisudawan->update([
            'is_tracer_study_filled' => true,
            'tracer_status_pekerjaan' => $validated['tracer_status_pekerjaan'],
            'tracer_nama_instansi' => $validated['tracer_nama_instansi'] ?? null,
            'tracer_jabatan' => $validated['tracer_jabatan'] ?? null,
            'tracer_pendapatan' => $validated['tracer_pendapatan'] ?? null,
            'tracer_kesesuaian_prodi' => $validated['tracer_kesesuaian_prodi'],
        ]);

        return redirect()->route('wisudawan.dashboard')->with('success', 'Data Tracer Study berhasil disimpan! Anda sekarang dapat melengkapi Biodata & Live Preview Layar Wisuda.');
    }
}
