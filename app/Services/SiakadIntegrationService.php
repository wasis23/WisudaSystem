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
            // 1. Cek view atau langsung tabel mahasiswa_pt / wsia_mahasiswa_pt
            $student = DB::connection('siakad')->table('viewMahasiswaPt')
                ->where('nipd', $cleanNim)
                ->first();

            if (!$student) {
                $student = DB::connection('siakad')->table('viewMahasiswaKeluar')
                    ->where('nipd', $cleanNim)
                    ->first();
            }

            // Fallback direct table query if view doesn't return
            if (!$student) {
                $ptTable = DB::connection('siakad')->getSchemaBuilder()->hasTable('wsia_mahasiswa_pt') ? 'wsia_mahasiswa_pt' : 'mahasiswa_pt';
                $student = DB::connection('siakad')->table($ptTable)
                    ->where('nipd', $cleanNim)
                    ->first();
            }

            if (!$student) {
                return null;
            }

            // 2. Lookup detailed biodata in wsia_mahasiswa
            $bio = null;
            $idPd = !empty($student->id_pd) ? $student->id_pd : ($student->xid_pd ?? null);
            $xidPd = !empty($student->xid_pd) ? $student->xid_pd : null;

            if (!empty($idPd) || !empty($xidPd)) {
                $bio = DB::connection('siakad')->table('wsia_mahasiswa')
                    ->where(function ($q) use ($idPd, $xidPd) {
                        if (!empty($idPd)) {
                            $q->where('id_pd', $idPd)->orWhere('xid_pd', $idPd);
                        }
                        if (!empty($xidPd)) {
                            $q->orWhere('xid_pd', $xidPd)->orWhere('id_pd', $xidPd);
                        }
                    })
                    ->first();
            }

            // 3. Lookup program studi in wsia_sms & resolve authentic D4 / D3 name
            $rawProdi = $student->nm_lemb ?? $student->nm_prodi ?? null;
            $idJenjang = $student->id_jenj_didik ?? null;
            $idSms = $student->id_sms ?? $student->xid_sms ?? null;

            if (!empty($idSms)) {
                $sms = DB::connection('siakad')->table('wsia_sms')
                    ->where(function ($q) use ($idSms) {
                        $q->where('id_sms', $idSms)
                          ->orWhere('xid_sms', $idSms);
                    })
                    ->first();
                if ($sms) {
                    $rawProdi = $sms->nm_lemb ?? $rawProdi;
                    $idJenjang = $sms->id_jenj_didik ?? $idJenjang;
                }
            }

            $jenjangPrefix = ($idJenjang == '23') ? 'D4' : (($idJenjang == '22') ? 'D3' : 'S1');
            $prodiName = $rawProdi;
            if ($rawProdi && !str_starts_with($rawProdi, 'D4') && !str_starts_with($rawProdi, 'D3')) {
                $prodiName = $jenjangPrefix . ' ' . $rawProdi;
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
                'program_studi' => $prodiName,
            ];
        } catch (\Exception $e) {
            Log::warning('SIAKAD Integration error: ' . $e->getMessage());
        }

        return null;
    }
}
