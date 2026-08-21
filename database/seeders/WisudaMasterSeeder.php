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
        $prodiList = [
            ['kode_prodi' => 'D4-TRO', 'nama_prodi' => 'D4 Teknologi Rekayasa Otomotif', 'jenjang' => 'D4'],
            ['kode_prodi' => 'D4-TRPL', 'nama_prodi' => 'D4 Teknologi Rekayasa Perangkat Lunak', 'jenjang' => 'D4'],
            ['kode_prodi' => 'D4-PM', 'nama_prodi' => 'D4 Produksi Media', 'jenjang' => 'D4'],
            ['kode_prodi' => 'D3-HTL', 'nama_prodi' => 'D3 Perhotelan', 'jenjang' => 'D3'],
            ['kode_prodi' => 'D3-FAR', 'nama_prodi' => 'D3 Farmasi', 'jenjang' => 'D3'],
            ['kode_prodi' => 'D4-MIK', 'nama_prodi' => 'D4 Manajemen Informasi Kesehatan', 'jenjang' => 'D4'],
            ['kode_prodi' => 'D4-TLM', 'nama_prodi' => 'D4 Teknologi Laboratorium Medis', 'jenjang' => 'D4'],
        ];

        foreach ($prodiList as $p) {
            ProgramStudi::firstOrCreate(
                ['nama_prodi' => $p['nama_prodi']],
                [
                    'kode_prodi' => $p['kode_prodi'],
                    'jenjang' => $p['jenjang'],
                ]
            );
        }

        // 2. Create Wisuda Periods
        $periode75 = PeriodeWisuda::firstOrCreate(
            ['nomor_periode' => 75],
            [
                'nama_periode' => 'Wisuda Ke-75 Politeknik Indonusa Surakarta',
                'tahun_akademik' => '2025/2026',
                'tanggal_pelaksanaan' => '2026-10-03',
                'kuota_peserta' => 500,
                'tanggal_buka_pendaftaran' => '2026-08-18 08:00:00',
                'tanggal_tutup_pendaftaran' => '2026-09-21 23:59:59',
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
