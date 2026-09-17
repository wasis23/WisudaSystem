<?php

namespace Database\Seeders;

use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\User;
use App\Models\Wisudawan;
use App\Models\WisudawanTamuTambahan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyTestingWisudawanSeeder extends Seeder
{
    public function run(): void
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();
        $prodi = ProgramStudi::first();

        $dummyAccounts = [
            [
                'nim' => 'TEST-99001',
                'nama' => '[TESTING] Wisudawan Alpha',
                'email' => 'test1@wisuda.test',
                'prodi_id' => $prodi?->id ?? 1,
                'tamu_1' => '[TESTING] Ayah Wisudawan Alpha',
                'tamu_2' => '[TESTING] Ibu Wisudawan Alpha',
            ],
            [
                'nim' => 'TEST-99002',
                'nama' => '[TESTING] Wisudawan Beta',
                'email' => 'test2@wisuda.test',
                'prodi_id' => $prodi?->id ?? 1,
                'tamu_1' => '[TESTING] Ayah Wisudawan Beta',
                'tamu_2' => '[TESTING] Ibu Wisudawan Beta',
            ],
            [
                'nim' => 'TEST-99003',
                'nama' => '[TESTING] Wisudawan Gamma',
                'email' => 'test3@wisuda.test',
                'prodi_id' => $prodi?->id ?? 1,
                'tamu_1' => '[TESTING] Ayah Wisudawan Gamma',
                'tamu_2' => '[TESTING] Ibu Wisudawan Gamma',
            ],
            [
                'nim' => 'TEST-99004',
                'nama' => '[TESTING] Wisudawan Delta',
                'email' => 'test4@wisuda.test',
                'prodi_id' => $prodi?->id ?? 1,
                'tamu_1' => '[TESTING] Ayah Wisudawan Delta',
                'tamu_2' => '[TESTING] Ibu Wisudawan Delta',
            ],
            [
                'nim' => 'TEST-99005',
                'nama' => '[TESTING] Wisudawan Epsilon',
                'email' => 'test5@wisuda.test',
                'prodi_id' => $prodi?->id ?? 1,
                'tamu_1' => '[TESTING] Ayah Wisudawan Epsilon',
                'tamu_2' => '[TESTING] Ibu Wisudawan Epsilon',
            ],
        ];

        foreach ($dummyAccounts as $idx => $acc) {
            $user = User::updateOrCreate(
                ['email' => $acc['email']],
                [
                    'name' => $acc['nama'],
                    'password' => Hash::make('password'),
                    'role' => 'wisudawan',
                    'program_studi_id' => $acc['prodi_id'],
                    'is_dummy' => true,
                ]
            );

            $wisudawan = Wisudawan::withoutGlobalScope('excludeDummy')->updateOrCreate(
                ['nim' => $acc['nim']],
                [
                    'user_id' => $user->id,
                    'periode_wisuda_id' => $activePeriode?->id ?? 1,
                    'program_studi_id' => $acc['prodi_id'],
                    'nama_lengkap' => $acc['nama'],
                    'nik' => '337201010101000' . ($idx + 1),
                    'tempat_lahir' => 'Surakarta',
                    'tanggal_lahir' => '2001-01-0' . ($idx + 1),
                    'jenis_kelamin' => $idx % 2 === 0 ? 'L' : 'P',
                    'tanggal_lulus' => '2026-08-01',
                    'email' => $acc['email'],
                    'nomor_hp' => '08123456789' . ($idx + 1),
                    'alamat' => 'Jl. K.H. Samanhudi No. 93, Surakarta (Alamat Testing)',
                    'ipk' => '3.85',
                    'predikat_kelulusan' => 'Dengan Pujian (Cumlaude)',
                    'judul_ta' => 'Pengujian Alur Presensi dan Scanner Wisuda Smart System',
                    'dosen_pembimbing_1' => 'Dosen Pembimbing Testing, M.Kom.',
                    'dosen_penguji' => 'Dosen Penguji Testing, M.Cs.',
                    'nama_ayah' => $acc['tamu_1'],
                    'nama_ibu' => $acc['tamu_2'],
                    'qr_code_token' => 'WSD-' . $acc['nim'],
                    'status_verifikasi' => 'verified',
                    'status_pembayaran_sikeu' => 'lunas',
                    'is_tracer_study_filled' => true,
                    'is_biodata_filled' => true,
                    'is_dummy' => true,
                    'is_hadir' => false,
                    'is_in_auditorium' => false,
                    'jumlah_tamu_tambahan' => 2,
                ]
            );

            // Create or update 2 guests for each dummy wisudawan
            WisudawanTamuTambahan::withoutGlobalScope('excludeDummy')->updateOrCreate(
                [
                    'wisudawan_id' => $wisudawan->id,
                    'qr_guest_token' => 'GST1-' . $acc['nim'],
                ],
                [
                    'nama_tamu' => $acc['tamu_1'],
                    'hubungan' => 'Orang Tua / Ayah',
                    'is_hadir' => false,
                    'is_hadir_gate' => false,
                    'is_hadir_venue' => false,
                    'snack_diambil' => false,
                ]
            );

            WisudawanTamuTambahan::withoutGlobalScope('excludeDummy')->updateOrCreate(
                [
                    'wisudawan_id' => $wisudawan->id,
                    'qr_guest_token' => 'GST2-' . $acc['nim'],
                ],
                [
                    'nama_tamu' => $acc['tamu_2'],
                    'hubungan' => 'Orang Tua / Ibu',
                    'is_hadir' => false,
                    'is_hadir_gate' => false,
                    'is_hadir_venue' => false,
                    'snack_diambil' => false,
                ]
            );
        }

        $this->command->info('5 Akun Testing / Dummy Wisudawan berhasil dibuat dengan flag is_dummy = true.');
    }
}
