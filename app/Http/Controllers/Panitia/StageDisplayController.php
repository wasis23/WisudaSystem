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
            ->where('status_pembayaran_sikeu', 'lunas')
            ->orderByRaw('urutan_tampil IS NULL ASC, urutan_tampil ASC, program_studi_id ASC, nim ASC')
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
            ->where('status_pembayaran_sikeu', 'lunas')
            ->orderByRaw('urutan_tampil IS NULL ASC, urutan_tampil ASC, program_studi_id ASC, nim ASC')
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

    public function downloadTemplate(Request $request)
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $wisudawans = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $activePeriode?->id)
            ->where('status_verifikasi', 'verified')
            ->where('status_pembayaran_sikeu', 'lunas')
            ->orderByRaw('urutan_tampil IS NULL ASC, urutan_tampil ASC, program_studi_id ASC, nim ASC')
            ->get();

        $filename = 'template_urutan_pemanggilan_wisuda.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($wisudawans) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");
            // Header row (2 columns: NIM & Nomor Urut)
            fputcsv($file, ['NIM', 'Nomor Urut']);

            foreach ($wisudawans as $index => $w) {
                fputcsv($file, [
                    $w->nim,
                    $w->urutan_tampil ?? ($index + 1),
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function uploadTemplate(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Check for BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                if (count($data) === 1 && str_contains($data[0], ';')) {
                    $data = explode(';', $data[0]);
                }
                $rows[] = array_map('trim', $data);
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->withErrors(['file' => 'File template kosong atau format tidak dapat dibaca.']);
        }

        // Determine column indexes for NIM and Nomor Urut
        $header = array_map('strtolower', $rows[0]);
        $nimColIndex = false;
        $orderColIndex = false;

        foreach ($header as $idx => $colName) {
            if (str_contains($colName, 'nim')) {
                $nimColIndex = $idx;
            }
            if (str_contains($colName, 'urut') || str_contains($colName, 'nomor') || str_contains($colName, 'no')) {
                $orderColIndex = $idx;
            }
        }

        // Defaults if header not explicitly recognized
        if ($nimColIndex === false) {
            $nimColIndex = 0; // Default to first column for 2-column format
        }
        if ($orderColIndex === false) {
            $orderColIndex = (count($rows[0]) > 1) ? 1 : 0;
        }

        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();

        $updatedCount = 0;
        $orderCounter = 1;

        // Skip header if first row contains column names ("nim", "nomor urut", etc)
        $firstCell = preg_replace('/[^0-9]/', '', $rows[0][$nimColIndex] ?? '');
        $startIndex = (empty($firstCell) && isset($rows[0][$nimColIndex]) && !is_numeric($rows[0][$nimColIndex])) ? 1 : 0;

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!isset($row[$nimColIndex]) || empty($row[$nimColIndex])) {
                continue;
            }

            $rawNim = preg_replace('/[^0-9A-Za-z\-]/', '', $row[$nimColIndex]);
            if (empty($rawNim)) continue;

            $customOrder = isset($row[$orderColIndex]) ? filter_var($row[$orderColIndex], FILTER_VALIDATE_INT) : false;
            $targetOrder = ($customOrder !== false && $customOrder > 0) ? $customOrder : $orderCounter;

            $wisudawan = Wisudawan::where('periode_wisuda_id', $activePeriode?->id)
                ->where('nim', $rawNim)
                ->first();

            if ($wisudawan) {
                $wisudawan->update(['urutan_tampil' => $targetOrder]);
                $orderCounter++;
                $updatedCount++;
            }
        }

        return back()->with('success', "Berhasil memperbarui urutan pemanggilan {$updatedCount} wisudawan.");
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
