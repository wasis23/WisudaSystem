<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiakadIntegrationService
{
    /**
     * Get student details by NIM from SIAKAD database including wsia_mahasiswa biodata
     */
    public function getStudentByNim(string $nim): ?array
    {
        $cleanNim = strtoupper(trim($nim));

        try {
            $student = DB::connection('siakad')->table('viewMahasiswaPt')
                ->where('nipd', $cleanNim)
                ->first();

            if (!$student) {
                $student = DB::connection('siakad')->table('viewMahasiswaKeluar')
                    ->where('nipd', $cleanNim)
                    ->first();
            }

            if (!$student) {
                return null;
            }

            // Lookup detailed biodata in wsia_mahasiswa
            $bio = null;
            if (!empty($student->id_pd) || !empty($student->xid_pd)) {
                $bio = DB::connection('siakad')->table('wsia_mahasiswa')
                    ->where(function ($q) use ($student) {
                        if (!empty($student->id_pd)) $q->where('id_pd', $student->id_pd);
                        if (!empty($student->xid_pd)) $q->orWhere('xid_pd', $student->xid_pd);
                    })
                    ->first();
            }

            $alamatParts = array_filter([
                $bio->jln ?? null,
                $bio->ds_kel ? 'Kel. ' . $bio->ds_kel : null,
                $bio->kode_pos ?? null,
            ]);

            return [
                'nim'           => $student->nipd ?? $cleanNim,
                'nama_lengkap'  => $bio->nm_pd ?? $student->nm_pd ?? $cleanNim,
                'nama_ayah'     => $bio->nm_ayah ?? null,
                'nama_ibu'      => $bio->nm_ibu_kandung ?? null,
                'tempat_lahir'  => $bio->tmpt_lahir ?? null,
                'tanggal_lahir' => $bio->tgl_lahir ?? null,
                'jenis_kelamin' => $bio->jk ?? $student->jk ?? 'L',
                'nik'           => $bio->nik ?? null,
                'nomor_hp'      => $bio->telepon_seluler ?? $bio->telepon_rumah ?? $student->telepon_seluler ?? null,
                'email'         => $bio->email_poltek ?: ($bio->email ?: ($student->email ?? strtolower($cleanNim) . '@students.poltekindonusa.ac.id')),
                'alamat'        => !empty($alamatParts) ? implode(', ', $alamatParts) : null,
                'program_studi' => $student->nm_lemb ?? $student->nm_prodi ?? null,
            ];
        } catch (\Exception $e) {
            Log::warning('SIAKAD Integration error: ' . $e->getMessage());
        }

        return null;
    }
}
