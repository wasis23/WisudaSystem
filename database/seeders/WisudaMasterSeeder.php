<?php

namespace Database\Seeders;

use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\StageLayoutConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WisudaMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Study Programs for Politeknik Indonusa Surakarta
        $tif = ProgramStudi::firstOrCreate(
            ['kode_prodi' => 'D3-SI'],
            [
                'nama_prodi' => 'D3 Sistem Informasi',
                'jenjang' => 'D3',
                'kaprodi_nama' => 'Budi Raharjo, M.Kom.',
                'kaprodi_nip' => '198501012010121003',
            ]
        );

        $mi = ProgramStudi::firstOrCreate(
            ['kode_prodi' => 'D3-MI'],
            [
                'nama_prodi' => 'D3 Manajemen Informatika',
                'jenjang' => 'D3',
                'kaprodi_nama' => 'Eko Prasetyo, M.T.',
                'kaprodi_nip' => '198702022012011004',
            ]
        );

        $far = ProgramStudi::firstOrCreate(
            ['kode_prodi' => 'D3-FAR'],
            [
                'nama_prodi' => 'D3 Farmasi',
                'jenjang' => 'D3',
                'kaprodi_nama' => 'apt. Siti Aminah, M.Farm.',
                'kaprodi_nip' => '198904041998022001',
            ]
        );

        // 2. Create Wisuda Periods
        $periode75 = PeriodeWisuda::firstOrCreate(
            ['nomor_periode' => 75],
            [
                'nama_periode' => 'Wisuda Ke-75 Politeknik Indonusa Surakarta',
                'tahun_akademik' => '2025/2026',
                'tanggal_pelaksanaan' => '2026-09-15',
                'kuota_peserta' => 500,
                'tanggal_buka_pendaftaran' => '2026-08-01 08:00:00',
                'tanggal_tutup_pendaftaran' => '2026-08-31 23:59:59',
                'is_active' => true,
            ]
        );

        // 3. Create Default Stage Layout Config
        StageLayoutConfig::firstOrCreate(
            ['periode_wisuda_id' => $periode75->id],
            [
                'photo_x' => 100,
                'photo_y' => 150,
                'photo_w' => 320,
                'photo_h' => 420,
                'nama_x' => 480,
                'nama_y' => 180,
                'nama_font_size' => 48,
                'nim_x' => 480,
                'nim_y' => 250,
                'nim_font_size' => 24,
                'prodi_x' => 480,
                'prodi_y' => 290,
                'prodi_font_size' => 24,
                'ipk_x' => 480,
                'ipk_y' => 340,
                'ipk_font_size' => 28,
                'ta_x' => 480,
                'ta_y' => 400,
                'ta_font_size' => 20,
                'ta_max_w' => 700,
            ]
        );

        // 4. Create Core Users
        User::firstOrCreate(
            ['email' => 'admin@poltekindonusa.ac.id'],
            [
                'name' => 'Administrator Poltek Indonusa',
                'password' => Hash::make('password'),
                'role' => 'admin_utama',
            ]
        );

        User::firstOrCreate(
            ['email' => 'security@poltekindonusa.ac.id'],
            [
                'name' => 'Security Gate Presensi',
                'password' => Hash::make('password'),
                'role' => 'security',
            ]
        );

        User::firstOrCreate(
            ['email' => 'wisudawan@poltekindonusa.ac.id'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'role' => 'wisudawan',
                'program_studi_id' => $tif->id,
            ]
        );
    }
}
