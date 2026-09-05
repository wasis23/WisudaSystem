<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BukuKenanganController extends Controller
{
    /**
     * Dapatkan SQL raw CASE untuk pengurutan Program Studi pada Buku Kenangan:
     * 1. Teknologi Rekayasa Otomotif (ID: 4)
     * 2. Teknologi Rekayasa Perangkat Lunak (ID: 5)
     * 3. Produksi Media (ID: 6)
     * 4. Perhotelan (ID: 7)
     * 5. Farmasi (ID: 3)
     * 6. Manajemen Informasi Kesehatan (ID: 8)
     * 7. Teknologi Laboratorium Medis (ID: 9)
     */
    private function getProdiOrderRawSql(string $column = 'program_studi_id'): string
    {
        return "CASE 
            WHEN {$column} = 4 THEN 1
            WHEN {$column} = 5 THEN 2
            WHEN {$column} = 6 THEN 3
            WHEN {$column} = 7 THEN 4
            WHEN {$column} = 3 THEN 5
            WHEN {$column} = 8 THEN 6
            WHEN {$column} = 9 THEN 7
            ELSE 99 END ASC";
    }

    public function index(Request $request)
    {
        $periodes = PeriodeWisuda::orderBy('id', 'desc')->get();
        $selectedPeriodeId = $request->periode_id ?? (PeriodeWisuda::getActive()?->id ?? $periodes->first()?->id);
        $programStudis = ProgramStudi::orderByRaw($this->getProdiOrderRawSql('id'))->get();

        $baseQuery = Wisudawan::where('periode_wisuda_id', $selectedPeriodeId);
        
        $totalWisudawan = (clone $baseQuery)->count();
        $totalTanpaFoto = (clone $baseQuery)->where(function ($q) {
            $q->whereNull('pas_foto')->orWhere('pas_foto', '')->orWhere('pas_foto', '0');
        })->count();
        $totalAdaFoto = $totalWisudawan - $totalTanpaFoto;

        $query = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $selectedPeriodeId);

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        if ($request->filled('status_foto')) {
            if ($request->status_foto === 'tanpa_foto') {
                $query->where(function ($q) {
                    $q->whereNull('pas_foto')->orWhere('pas_foto', '')->orWhere('pas_foto', '0');
                });
            } elseif ($request->status_foto === 'ada_foto') {
                $query->whereNotNull('pas_foto')->where('pas_foto', '!=', '')->where('pas_foto', '!=', '0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('judul_ta', 'like', "%{$search}%");
            });
        }

        $wisudawans = $query->orderByRaw($this->getProdiOrderRawSql('program_studi_id'))->orderBy('ipk', 'desc')
            ->paginate(50)
            ->appends(array_merge([
                'periode_id' => $selectedPeriodeId,
            ], $request->except('page')));
        $currentPeriode = $periodes->firstWhere('id', (int) $selectedPeriodeId);

        return Inertia::render('Admin/BukuKenangan/Index', [
            'periodes' => $periodes,
            'selectedPeriodeId' => (int) $selectedPeriodeId,
            'currentPeriode' => $currentPeriode,
            'programStudis' => $programStudis,
            'wisudawans' => $wisudawans,
            'stats' => [
                'total_wisudawan' => $totalWisudawan,
                'total_tanpa_foto' => $totalTanpaFoto,
                'total_ada_foto' => $totalAdaFoto,
            ],
            'filters' => $request->only(['periode_id', 'program_studi_id', 'status_foto', 'search']),
        ]);
    }

    public function exportTanpaFoto(Request $request)
    {
        $selectedPeriodeId = $request->periode_id ?? (PeriodeWisuda::getActive()?->id ?? PeriodeWisuda::latest()->first()?->id);
        
        if (!$selectedPeriodeId) {
            return redirect()->back()->with('error', 'Belum ada periode wisuda.');
        }

        $periode = PeriodeWisuda::findOrFail($selectedPeriodeId);

        $query = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $periode->id)
            ->where(function ($q) {
                $q->whereNull('pas_foto')->orWhere('pas_foto', '')->orWhere('pas_foto', '0');
            });

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        $wisudawans = $query->orderByRaw($this->getProdiOrderRawSql('program_studi_id'))->orderBy('nim')->get();

        $filename = "Wisudawan_Belum_Upload_Foto_Periode_{$periode->nomor_periode}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($wisudawans, $periode) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Microsoft Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header Column
            fputcsv($file, [
                'No',
                'NIM',
                'Nama Lengkap',
                'Gelar',
                'Program Studi',
                'Jenjang',
                'No HP / WhatsApp',
                'Email',
                'Status Foto',
                'Status Biodata',
                'Status Tracer Study',
                'Status Pembayaran SIKEU',
                'Orang Tua',
                'Alamat',
            ], ';');

            $no = 1;
            foreach ($wisudawans as $w) {
                fputcsv($file, [
                    $no++,
                    "'" . $w->nim, // Prefix with apostrophe so Excel keeps leading characters & numbers intact
                    $w->nama_lengkap,
                    $w->gelar ?: '-',
                    $w->programStudi?->nama_prodi ?: '-',
                    $w->programStudi?->jenjang ?: '-',
                    $w->nomor_hp ? "'" . $w->nomor_hp : '-',
                    $w->email ?: '-',
                    'Belum Upload Foto',
                    $w->is_biodata_filled ? 'Sudah Diisi' : 'Belum Diisi',
                    $w->is_tracer_study_filled ? 'Sudah Diisi' : 'Belum Diisi',
                    $w->status_pembayaran_sikeu === 'lunas' ? 'LUNAS' : 'BELUM LUNAS',
                    $w->orang_tua ?: ($w->nama_ayah ? "{$w->nama_ayah} / {$w->nama_ibu}" : '-'),
                    $w->alamat ?: '-',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateFooterImage(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('updateFooterImage request data:', [
            'all' => $request->except(['footer_image']),
            'has_file' => $request->hasFile('footer_image'),
            'file_valid' => $request->hasFile('footer_image') ? $request->file('footer_image')->isValid() : false,
        ]);

        $request->validate([
            'periode_id' => 'required|exists:periode_wisuda,id',
            'footer_image' => 'required|file|max:10240',
        ]);

        $periode = PeriodeWisuda::findOrFail($request->periode_id);

        if ($request->hasFile('footer_image')) {
            $targetDir = storage_path('app/public/buku_kenangan/footer_images');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $file = $request->file('footer_image');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = 'footer_' . $periode->id . '_' . time() . '.' . $ext;
            $path = $file->storeAs('buku_kenangan/footer_images', $filename, 'public');

            if (!$path) {
                \Illuminate\Support\Facades\Log::error('updateFooterImage storeAs failed for file:', ['filename' => $filename]);
                return redirect()->back()->with('error', 'Gagal menyimpan berkas gambar footer ke disk storage.');
            }

            // Delete old file if exists
            if ($periode->buku_kenangan_footer_image && $periode->buku_kenangan_footer_image !== $path) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($periode->buku_kenangan_footer_image)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($periode->buku_kenangan_footer_image);
                }
            }

            $periode->update([
                'buku_kenangan_footer_image' => $path,
            ]);

            \Illuminate\Support\Facades\Log::info('updateFooterImage saved successfully:', ['path' => $path]);
        }

        return redirect()->back()->with('success', 'Gambar footer dokumen Buku Kenangan berhasil diunggah dan aktif.');
    }

    public function destroyFooterImage(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periode_wisuda,id',
        ]);

        $periode = PeriodeWisuda::findOrFail($request->periode_id);

        if ($periode->buku_kenangan_footer_image) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($periode->buku_kenangan_footer_image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($periode->buku_kenangan_footer_image);
            }
        }

        $periode->update([
            'buku_kenangan_footer_image' => null,
        ]);

        return redirect()->back()->with('success', 'Gambar footer berhasil dihapus.');
    }

    public function updateDefaultFoto(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periode_wisuda,id',
            'default_foto' => 'required|file|max:10240',
        ]);

        $periode = PeriodeWisuda::findOrFail($request->periode_id);

        if ($request->hasFile('default_foto')) {
            $targetDir = storage_path('app/public/buku_kenangan/default_foto');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $file = $request->file('default_foto');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $filename = 'default_foto_' . $periode->id . '_' . time() . '.' . $ext;
            $path = $file->storeAs('buku_kenangan/default_foto', $filename, 'public');

            if (!$path) {
                \Illuminate\Support\Facades\Log::error('updateDefaultFoto storeAs failed for file:', ['filename' => $filename]);
                return redirect()->back()->with('error', 'Gagal menyimpan berkas default foto ke disk storage.');
            }

            // Delete old file if exists
            if ($periode->buku_kenangan_default_foto && $periode->buku_kenangan_default_foto !== $path) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($periode->buku_kenangan_default_foto)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($periode->buku_kenangan_default_foto);
                }
            }

            $periode->update([
                'buku_kenangan_default_foto' => $path,
            ]);

            \Illuminate\Support\Facades\Log::info('updateDefaultFoto saved successfully:', ['path' => $path]);
        }

        return redirect()->back()->with('success', 'Aset siluet default wisudawan berhasil diunggah.');
    }

    public function destroyDefaultFoto(Request $request)
    {
        $request->validate([
            'periode_id' => 'required|exists:periode_wisuda,id',
        ]);

        $periode = PeriodeWisuda::findOrFail($request->periode_id);

        if ($periode->buku_kenangan_default_foto) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($periode->buku_kenangan_default_foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($periode->buku_kenangan_default_foto);
            }
        }

        $periode->update([
            'buku_kenangan_default_foto' => null,
        ]);

        return redirect()->back()->with('success', 'Aset siluet default berhasil direset ke standar sistem.');
    }

    public function updateFotoWisudawan(Request $request, $id)
    {
        $request->validate([
            'pas_foto' => 'required|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $wisudawan = Wisudawan::findOrFail($id);

        if ($wisudawan->pas_foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($wisudawan->pas_foto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($wisudawan->pas_foto);
        }

        $path = $request->file('pas_foto')->store('pas_foto_wisudawan', 'public');
        $wisudawan->update([
            'pas_foto' => $path,
        ]);

        return redirect()->back()->with('success', "Foto untuk {$wisudawan->nama_lengkap} ({$wisudawan->nim}) berhasil diperbarui.");
    }

    public function deleteFotoWisudawan($id)
    {
        $wisudawan = Wisudawan::findOrFail($id);

        if ($wisudawan->pas_foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($wisudawan->pas_foto)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($wisudawan->pas_foto);
        }

        $wisudawan->update([
            'pas_foto' => null,
        ]);

        return redirect()->back()->with('success', "Foto untuk {$wisudawan->nama_lengkap} ({$wisudawan->nim}) berhasil dihapus / dikembalikan ke siluet.");
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');
        set_time_limit(300);

        $selectedPeriodeId = $request->periode_id ?? (PeriodeWisuda::getActive()?->id ?? PeriodeWisuda::latest()->first()?->id);
        
        if (!$selectedPeriodeId) {
            return redirect()->back()->with('error', 'Belum ada periode wisuda.');
        }

        $periode = PeriodeWisuda::findOrFail($selectedPeriodeId);

        $query = Wisudawan::with(['programStudi'])
            ->where('periode_wisuda_id', $periode->id)
            ->where('status_verifikasi', 'verified');

        if ($request->filled('program_studi_id')) {
            $query->where('program_studi_id', $request->program_studi_id);
        }

        $wisudawans = $query->orderByRaw($this->getProdiOrderRawSql('program_studi_id'))->orderBy('ipk', 'desc')->get();

        $groupedByProdi = $wisudawans->groupBy('program_studi_id')->map(function ($items, $prodiId) {
            $prodi = $items->first()?->programStudi;
            
            // Check kaprodi foto path if exists
            $kaprodiFotoPath = null;
            if ($prodi && !empty($prodi->kaprodi_foto)) {
                $pubPath = public_path('storage/' . $prodi->kaprodi_foto);
                $strPath = storage_path('app/public/' . $prodi->kaprodi_foto);
                if (file_exists($pubPath)) {
                    $kaprodiFotoPath = $pubPath;
                } elseif (file_exists($strPath)) {
                    $kaprodiFotoPath = $strPath;
                }
            }

            $rawName = $prodi ? $prodi->nama_prodi : 'Lainnya';
            $cleanName = preg_replace('/^(D3|D4|D-3|D-4|Diploma\s*3|Diploma\s*4|Sarjana\s*Terapan)\s+/i', '', $rawName);
            
            $jenjangUpper = strtoupper($prodi?->jenjang ?? '');
            $jenjangLabel = match ($jenjangUpper) {
                'D3' => 'AHLI MADYA',
                'D4' => 'SARJANA TERAPAN',
                'S1' => 'SARJANA',
                'S2' => 'MAGISTER',
                'S3' => 'DOKTOR',
                default => 'PROGRAM STUDI',
            };

            return [
                'prodi' => $prodi,
                'nama_prodi' => $rawName,
                'nama_prodi_clean' => $cleanName,
                'jenjang' => $prodi?->jenjang ?: '',
                'jenjang_label' => $jenjangLabel,
                'gelar' => $prodi?->gelar ?: '',
                'kaprodi_nama' => $prodi?->kaprodi_nama ?: '',
                'kaprodi_nip' => $prodi?->kaprodi_nip ?: '',
                'kaprodi_foto_path' => $kaprodiFotoPath,
                'wisudawans' => $items,
            ];
        });

        $footerImagePath = null;
        if (!empty($periode->buku_kenangan_footer_image) && $periode->buku_kenangan_footer_image !== '0') {
            $pubPath = public_path('storage/' . $periode->buku_kenangan_footer_image);
            $strPath = storage_path('app/public/' . $periode->buku_kenangan_footer_image);
            if (file_exists($pubPath)) {
                $footerImagePath = $pubPath;
            } elseif (file_exists($strPath)) {
                $footerImagePath = $strPath;
            }
        }

        $defaultFotoPath = public_path('images/default_toga_silhouette.png');
        if (!empty($periode->buku_kenangan_default_foto) && $periode->buku_kenangan_default_foto !== '0') {
            $pubFotoPath = public_path('storage/' . $periode->buku_kenangan_default_foto);
            $strFotoPath = storage_path('app/public/' . $periode->buku_kenangan_default_foto);
            if (file_exists($pubFotoPath)) {
                $defaultFotoPath = $pubFotoPath;
            } elseif (file_exists($strFotoPath)) {
                $defaultFotoPath = $strFotoPath;
            }
        }

        $pdf = Pdf::loadView('pdf.buku_kenangan', [
            'periode' => $periode,
            'groupedByProdi' => $groupedByProdi,
            'totalPeserta' => $wisudawans->count(),
            'footerImagePath' => $footerImagePath,
            'defaultFotoPath' => $defaultFotoPath,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("Buku_Kenangan_Wisuda_{$periode->nomor_periode}.pdf");
    }
}
