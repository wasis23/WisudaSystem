<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\StageLayoutConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StageLayoutConfigController extends Controller
{
    public function edit()
    {
        $config = StageLayoutConfig::getDefaultConfig();
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        return Inertia::render('Admin/StageLayout/Config', [
            'config' => $config,
            'activePeriode' => $activePeriode,
        ]);
    }

    public function update(Request $request)
    {
        $config = StageLayoutConfig::getDefaultConfig();

        $validated = $request->validate([
            'bg_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
            'remove_bg' => 'nullable|boolean',
            'photo_x' => 'required|integer',
            'photo_y' => 'required|integer',
            'photo_w' => 'required|integer|min:50',
            'photo_h' => 'required|integer|min:50',
            'nama_x' => 'required|integer',
            'nama_y' => 'required|integer',
            'nama_font_size' => 'required|integer|min:10',
            'nim_x' => 'required|integer',
            'nim_y' => 'required|integer',
            'nim_font_size' => 'required|integer|min:10',
            'prodi_x' => 'required|integer',
            'prodi_y' => 'required|integer',
            'prodi_font_size' => 'required|integer|min:10',
            'ipk_x' => 'required|integer',
            'ipk_y' => 'required|integer',
            'ipk_font_size' => 'required|integer|min:10',
            'ta_x' => 'required|integer',
            'ta_y' => 'required|integer',
            'ta_font_size' => 'required|integer|min:10',
            'ta_max_w' => 'required|integer|min:100',
        ]);

        if ($request->boolean('remove_bg') && $config->bg_image) {
            Storage::disk('public')->delete($config->bg_image);
            $validated['bg_image'] = null;
        } elseif ($request->hasFile('bg_image')) {
            if ($config->bg_image) {
                Storage::disk('public')->delete($config->bg_image);
            }
            $validated['bg_image'] = $request->file('bg_image')->store('stage_bg', 'public');
        } else {
            unset($validated['bg_image']);
        }

        $config->update($validated);

        return redirect()->back()->with('success', 'Pengaturan Presisi Tampilan Panggung & Template Background Berhasil Disimpan.');
    }
}
