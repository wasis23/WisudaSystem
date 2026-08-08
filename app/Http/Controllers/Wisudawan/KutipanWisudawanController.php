<?php

namespace App\Http\Controllers\Wisudawan;

use App\Http\Controllers\Controller;
use App\Models\KutipanWisudawan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KutipanWisudawanController extends Controller
{
    public function showForm()
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            return redirect()->route('wisudawan.pendaftaran.form')->with('warning', 'Harap isi pendaftaran biodata wisudawan terlebih dahulu.');
        }

        $kutipan = KutipanWisudawan::where('wisudawan_id', $wisudawan->id)->first();

        return Inertia::render('Wisudawan/Kutipan', [
            'wisudawan' => $wisudawan,
            'kutipan' => $kutipan,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $user = auth()->user();
        $wisudawan = $user->wisudawan;

        if (!$wisudawan) {
            return redirect()->back()->with('error', 'Wisudawan belum terdaftar.');
        }

        $validated = $request->validate([
            'kesan_pesan' => 'required|string|max:1000',
            'cita_cita' => 'nullable|string|max:255',
            'motto_hidup' => 'nullable|string|max:500',
            'instagram' => 'nullable|string|max:100',
            'linkedin' => 'nullable|string|max:100',
        ]);

        $socialMedia = [
            'instagram' => $validated['instagram'] ?? null,
            'linkedin' => $validated['linkedin'] ?? null,
        ];

        KutipanWisudawan::updateOrCreate(
            ['wisudawan_id' => $wisudawan->id],
            [
                'kesan_pesan' => $validated['kesan_pesan'],
                'cita_cita' => $validated['cita_cita'] ?? null,
                'motto_hidup' => $validated['motto_hidup'] ?? null,
                'social_media_handles' => $socialMedia,
            ]
        );

        return redirect()->route('wisudawan.dashboard')->with('success', 'Kesan, Pesan & Motto untuk Buku Kenangan berhasil disimpan.');
    }
}
