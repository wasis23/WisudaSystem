<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\StageLayoutConfig;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function showForm()
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan ? $user->wisudawan->load('programStudi') : null;

        if (!$wisudawan || !$wisudawan->is_tracer_study_filled) {
            return redirect()->route('wisudawan.dashboard')->with('error', 'Silakan isi Data Tracer Study terlebih dahulu untuk membuka menu Biodata & Live Preview Layar Wisuda.');
        }
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();
        $programStudis = ProgramStudi::all();
        $stageConfig = StageLayoutConfig::getDefaultConfig();

        return Inertia::render('Wisudawan/Register', [
            'wisudawan' => $wisudawan,
            'activePeriode' => $activePeriode,
            'programStudis' => $programStudis,
            'stageConfig' => $stageConfig,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'program_studi_id' => 'required|exists:program_studi,id',
            'nim' => 'required|string|max:50',
            'nama_lengkap' => 'required|string|max:255',
            'gelar' => 'nullable|string|max:100',
            'nik' => 'nullable|string|max:20',
            'tempat_lahir' => 'required|string|max:100',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'nomor_hp' => 'required|string|max:20',
            'alamat' => 'required|string|max:500',
            'ipk' => 'required|numeric|between:0.00,4.00',
            'judul_ta' => 'required|string|max:500',
            'tanggal_lulus' => 'required|date',
            'nama_ayah' => 'required|string|max:255',
            'nama_ibu' => 'required|string|max:255',
            'pas_foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        if (!$activePeriode) {
            return redirect()->back()->with('error', 'Belum ada periode wisuda aktif.');
        }

        // Handle Pas Foto Upload
        $pasFotoPath = $user->wisudawan?->pas_foto;
        if ($request->hasFile('pas_foto')) {
            $pasFotoPath = $request->file('pas_foto')->store('pas_foto_wisudawan', 'public');
        }

        // Auto calculate Predikat Kelulusan
        $ipk = (float) $validated['ipk'];
        $predikat = 'Sangat Memuaskan';
        if ($ipk >= 3.51) {
            $predikat = 'Dengan Pujian (Cumlaude)';
        } elseif ($ipk < 3.00) {
            $predikat = 'Memuaskan';
        }

        $qrToken = $user->wisudawan?->qr_code_token ?? 'WSD-' . strtoupper(Str::random(12));

        $data = array_merge($validated, [
            'user_id' => $user->id,
            'periode_wisuda_id' => $activePeriode->id,
            'predikat_kelulusan' => $predikat,
            'pas_foto' => $pasFotoPath,
            'qr_code_token' => $qrToken,
            'status_verifikasi' => 'verified',
        ]);

        Wisudawan::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()->route('wisudawan.dashboard')->with('success', 'Biodata & Pas Foto Wisudawan Berhasil Disimpan.');
    }
}
