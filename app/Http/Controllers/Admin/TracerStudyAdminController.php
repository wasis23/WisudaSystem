<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use App\Models\Wisudawan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TracerStudyAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = TracerStudy::with('wisudawan.programStudi');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nim', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        if ($request->filled('status')) {
            $query->where('status_saat_ini', $request->status);
        }

        // Summary Analytics
        $totalResponden = TracerStudy::count();
        $totalWisudawan = Wisudawan::count();
        $persentasePartisipasi = $totalWisudawan > 0 ? round(($totalResponden / $totalWisudawan) * 100, 1) : 0;

        $byStatus = TracerStudy::selectRaw('status_saat_ini, count(*) as count')
            ->whereNotNull('status_saat_ini')
            ->groupBy('status_saat_ini')
            ->pluck('count', 'status_saat_ini');

        $byProdi = TracerStudy::selectRaw('prodi, count(*) as count')
            ->whereNotNull('prodi')
            ->groupBy('prodi')
            ->pluck('count', 'prodi');

        $byGaji = TracerStudy::selectRaw('gaji_per_bulan, count(*) as count')
            ->whereNotNull('gaji_per_bulan')
            ->groupBy('gaji_per_bulan')
            ->pluck('count', 'gaji_per_bulan');

        $byWaktuTunggu = TracerStudy::selectRaw('waktu_tunggu, count(*) as count')
            ->whereNotNull('waktu_tunggu')
            ->groupBy('waktu_tunggu')
            ->pluck('count', 'waktu_tunggu');

        $responses = $query->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $prodiList = ProgramStudi::pluck('nama_prodi');

        return Inertia::render('Admin/TracerStudy/Index', [
            'responses' => $responses,
            'filters' => $request->only(['search', 'prodi', 'status']),
            'stats' => [
                'totalResponden' => $totalResponden,
                'totalWisudawan' => $totalWisudawan,
                'persentasePartisipasi' => $persentasePartisipasi,
                'byStatus' => $byStatus,
                'byProdi' => $byProdi,
                'byGaji' => $byGaji,
                'byWaktuTunggu' => $byWaktuTunggu,
            ],
            'prodiList' => $prodiList,
        ]);
    }

    public function show($id)
    {
        $tracer = TracerStudy::with('wisudawan.programStudi')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $tracer,
        ]);
    }

    public function export(Request $request)
    {
        $fileName = 'Report_Tracer_Study_' . date('Y-m-d_H-i-s') . '.csv';

        $query = TracerStudy::with('wisudawan.programStudi');

        if ($request->filled('prodi')) {
            $query->where('prodi', $request->prodi);
        }

        if ($request->filled('status')) {
            $query->where('status_saat_ini', $request->status);
        }

        $records = $query->latest()->get();

        $response = new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'No',
                'NIM',
                'Nama Lengkap',
                'Email',
                'No WhatsApp',
                'Program Studi',
                'Jenis Kelas',
                'Alamat Lengkap',
                'Status Saat Ini',
                'Status Lainnya',
                'Nama Tempat Bekerja',
                'Gaji per Bulan',
                'Keselarasan Pekerjaan',
                'Kesesuaian Pendidikan',
                'Waktu Tunggu Lulusan',
                'Alamat Tempat Kerja',
                'Jenis Instansi',
                'Posisi / Jabatan',
                'Cakupan Tempat Kerja',
                'Penghasilan Usaha',
                'Keselarasan Usaha',
                'Studi Lanjut',
                'Kampus Studi Lanjut',
                'Alamat Kampus Studi Lanjut',
                'Sumber Dana Kuliah',
                'Kepuasan Layanan Kampus',
                'Saran dan Masukan Alumni',
                'Tanggal Diisi',
            ]);

            $no = 1;
            foreach ($records as $row) {
                fputcsv($handle, [
                    $no++,
                    $row->nim,
                    $row->nama_lengkap,
                    $row->email,
                    $row->no_whatsapp,
                    $row->prodi,
                    $row->jenis_kelas,
                    $row->alamat_lengkap,
                    $row->status_saat_ini,
                    $row->status_lainnya,
                    $row->tempat_bekerja ?: $row->nama_perusahaan,
                    $row->gaji_per_bulan,
                    $row->keselarasan_pekerjaan,
                    $row->kesesuaian_pendidikan,
                    $row->waktu_tunggu,
                    $row->alamat_tempat_kerja,
                    $row->jenis_instansi,
                    $row->posisi_jabatan,
                    $row->cakupan_tempat_kerja,
                    $row->gaji_usaha,
                    $row->keselarasan_usaha,
                    $row->studi_lanjut,
                    $row->kampus_studi_lanjut,
                    $row->alamat_kampus_studi_lanjut,
                    $row->sumber_dana,
                    $row->kepuasan_layanan,
                    $row->saran_masukan,
                    $row->updated_at ? $row->updated_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
