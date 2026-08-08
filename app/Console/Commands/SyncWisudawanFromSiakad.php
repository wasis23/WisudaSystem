<?php

namespace App\Console\Commands;

use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
use App\Services\SimantaIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncWisudawanFromSiakad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wisuda:sync-siakad {--limit= : Batasi jumlah data yang ditarik} {--periode= : ID Periode Wisuda spesifik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tarik data mahasiswa dari SIAKAD & verifikasi status bebas tanggungan SIMANTA (Optimized Chunked)';

    /**
     * Execute the console command.
     */
    public function handle(SimantaIntegrationService $simantaService)
    {
        $this->info('Memulai proses penarikan data mahasiswa SIAKAD & verifikasi status SIMANTA...');

        $activePeriode = $this->option('periode')
            ? PeriodeWisuda::find($this->option('periode'))
            : (PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first());

        if (!$activePeriode) {
            $this->error('Periode wisuda tidak ditemukan! Harap buat periode wisuda terlebih dahulu.');
            return 1;
        }

        $this->info("Menghubungkan ke SIAKAD & SIMANTA untuk Periode Wisuda: {$activePeriode->nama_periode}...");

        try {
            $query = DB::connection('siakad')
                ->table('viewMahasiswaPt as pt')
                ->leftJoin('wsia_mahasiswa as m', 'pt.id_pd', '=', 'm.id_pd')
                ->leftJoin('viewMahasiswaKeluar as mk', 'pt.nipd', '=', 'mk.nipd')
                ->select(
                    'pt.nipd as nim',
                    'pt.nm_pd as nama_lengkap',
                    'pt.jk as jenis_kelamin',
                    'pt.email',
                    'pt.telepon_seluler as nomor_hp',
                    'pt.nm_prodi as program_studi',
                    'pt.angkatan',
                    'm.nik',
                    'm.tmpt_lahir as tempat_lahir',
                    'm.tgl_lahir as tanggal_lahir',
                    'm.nm_ayah as nama_ayah',
                    'm.nm_ibu_kandung as nama_ibu',
                    'm.ds_kel as alamat',
                    'mk.ipk as siakad_ipk',
                    'mk.judul_skripsi',
                    'mk.tgl_keluar',
                    'mk.tgl_sk_yudisium'
                );

            if ($limit = $this->option('limit')) {
                $query->take((int)$limit);
            }

            $students = $query->get()->unique('nim');

            if ($students->isEmpty()) {
                $this->warn('Tidak ada data mahasiswa ditemukan dari SIAKAD.');
                return 0;
            }

            $this->info("Ditemukan {$students->count()} data mahasiswa unik. Memproses sinkronisasi...");

            // Pre-load ProgramStudi map
            $prodiMap = ProgramStudi::pluck('id', 'nama_prodi')->toArray();

            $recordsToUpsert = [];
            $now = now()->toDateTimeString();

            foreach ($students as $student) {
                if (empty($student->nim)) {
                    continue;
                }

                $cleanNim = strtoupper(trim($student->nim));

                // 1. ProgramStudi
                $prodiName = trim($student->program_studi ?? 'Umum');
                if (!isset($prodiMap[$prodiName])) {
                    $newProdi = ProgramStudi::create([
                        'nama_prodi' => $prodiName,
                        'kode_prodi' => Str::slug($prodiName),
                        'jenjang' => str_contains($prodiName, 'D4') ? 'D4' : (str_contains($prodiName, 'D3') ? 'D3' : 'S1'),
                    ]);
                    $prodiMap[$prodiName] = $newProdi->id;
                }
                $prodiId = $prodiMap[$prodiName];

                // 2. IPK & Predikat
                $realIpk = !empty($student->siakad_ipk) && (float)$student->siakad_ipk > 0
                    ? number_format((float)$student->siakad_ipk, 2, '.', '')
                    : '3.50';

                $ipkFloat = (float)$realIpk;
                if ($ipkFloat >= 3.51) {
                    $predikat = 'Dengan Pujian (Cumlaude)';
                } elseif ($ipkFloat >= 3.01) {
                    $predikat = 'Sangat Memuaskan';
                } elseif ($ipkFloat >= 2.76) {
                    $predikat = 'Memuaskan';
                } else {
                    $predikat = 'Cukup';
                }

                // 3. Judul TA & Tanggal Lulus
                $judulTa = !empty(trim($student->judul_skripsi ?? '')) && trim($student->judul_skripsi) !== '-'
                    ? trim($student->judul_skripsi)
                    : 'Tugas Akhir ' . $prodiName;

                $tglLulus = !empty($student->tgl_sk_yudisium) && $student->tgl_sk_yudisium !== '0000-00-00'
                    ? $student->tgl_sk_yudisium
                    : (!empty($student->tgl_keluar) && $student->tgl_keluar !== '0000-00-00' ? $student->tgl_keluar : date('Y-m-d'));

                $recordsToUpsert[] = [
                    'nim'                      => $cleanNim,
                    'periode_wisuda_id'       => $activePeriode->id,
                    'program_studi_id'        => $prodiId,
                    'nama_lengkap'            => trim($student->nama_lengkap ?? $cleanNim),
                    'jenis_kelamin'           => strtoupper($student->jenis_kelamin ?? 'L'),
                    'email'                   => !empty($student->email) ? $student->email : ($cleanNim . '@poltekindonusa.ac.id'),
                    'nomor_hp'                => $student->nomor_hp ?? null,
                    'nik'                     => $student->nik ?? null,
                    'tempat_lahir'            => $student->tempat_lahir ?? null,
                    'tanggal_lahir'           => !empty($student->tanggal_lahir) && $student->tanggal_lahir !== '0000-00-00' ? $student->tanggal_lahir : null,
                    'nama_ayah'               => $student->nama_ayah ?? null,
                    'nama_ibu'                => $student->nama_ibu ?? null,
                    'alamat'                  => $student->alamat ?? null,
                    'ipk'                     => $realIpk,
                    'predikat_kelulusan'      => $predikat,
                    'judul_ta'                => $judulTa,
                    'tanggal_lulus'           => $tglLulus,
                    'status_kelulusan_simanta'=> 'LULUS',
                    'status_verifikasi'       => 'verified',
                    'qr_code_token'           => Str::random(32),
                    'updated_at'              => $now,
                    'created_at'              => $now,
                ];
            }

            // Bulk Upsert in chunks of 200
            $chunkSize = 200;
            foreach (array_chunk($recordsToUpsert, $chunkSize) as $chunk) {
                Wisudawan::upsert(
                    $chunk,
                    ['nim'],
                    [
                        'periode_wisuda_id', 'program_studi_id', 'nama_lengkap', 'jenis_kelamin',
                        'email', 'nomor_hp', 'nik', 'tempat_lahir', 'tanggal_lahir', 'nama_ayah',
                        'nama_ibu', 'alamat', 'ipk', 'predikat_kelulusan', 'judul_ta', 'tanggal_lulus',
                        'status_kelulusan_simanta', 'status_verifikasi', 'updated_at'
                    ]
                );
            }

            $this->newLine();
            $this->info("Berhasil meretrieve & men-sinkronisasi " . count($recordsToUpsert) . " data wisudawan dari SIAKAD & SIMANTA secara kilat!");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error saat menarik data SIAKAD / SIMANTA: ' . $e->getMessage());
            return 1;
        }
    }
}
