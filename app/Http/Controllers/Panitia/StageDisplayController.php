<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\StageLayoutConfig;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StageDisplayController extends Controller
{
    public function display(Request $request)
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        if (!$activePeriode) {
            return response()->json(['message' => 'Belum ada periode wisuda aktif.'], 404);
        }

        $wisudawans = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $activePeriode->id)
            ->where('status_verifikasi', 'verified')
            ->orderBy('program_studi_id')
            ->orderBy('nim', 'asc')
            ->get();

        $stageConfig = StageLayoutConfig::getDefaultConfig();

        $activeWisudawanId = Cache::get('active_stage_wisudawan_id');
        $initialIndex = 0;
        if ($activeWisudawanId) {
            $foundIndex = $wisudawans->search(fn($w) => $w->id == $activeWisudawanId);
            if ($foundIndex !== false) {
                $initialIndex = $foundIndex;
            }
        }

        return Inertia::render('Panitia/StageDisplay', [
            'activePeriode' => $activePeriode,
            'wisudawans' => $wisudawans,
            'stageConfig' => $stageConfig,
            'initialIndex' => $initialIndex,
        ]);
    }

    public function control(Request $request)
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $wisudawans = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $activePeriode?->id)
            ->where('status_verifikasi', 'verified')
            ->orderBy('program_studi_id')
            ->orderBy('nim', 'asc')
            ->get();

        $activeWisudawanId = Cache::get('active_stage_wisudawan_id');
        $initialIndex = 0;
        if ($activeWisudawanId) {
            $foundIndex = $wisudawans->search(fn($w) => $w->id == $activeWisudawanId);
            if ($foundIndex !== false) {
                $initialIndex = $foundIndex;
            }
        }

        return Inertia::render('Panitia/StageControl', [
            'activePeriode' => $activePeriode,
            'wisudawans' => $wisudawans,
            'initialIndex' => $initialIndex,
        ]);
    }

    public function setActiveWisudawan(Request $request)
    {
        $validated = $request->validate([
            'wisudawan_id' => 'required|integer|exists:wisudawan,id',
            'index' => 'nullable|integer',
        ]);

        Cache::forever('active_stage_wisudawan_id', $validated['wisudawan_id']);
        if (isset($validated['index'])) {
            Cache::forever('active_stage_index', $validated['index']);
        }

        return response()->json([
            'status' => 'success',
            'active_wisudawan_id' => $validated['wisudawan_id'],
            'index' => $validated['index'] ?? null,
        ]);
    }

    public function getActiveWisudawan()
    {
        $activeId = Cache::get('active_stage_wisudawan_id');
        $activeIndex = Cache::get('active_stage_index', 0);

        return response()->json([
            'active_wisudawan_id' => $activeId,
            'index' => $activeIndex,
        ]);
    }
}
