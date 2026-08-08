<?php

namespace App\Console\Commands;

use App\Models\PeriodeWisuda;
use App\Models\ProgramStudi;
use App\Models\Wisudawan;
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
    protected $description = 'Tarik data mahasiswa dari SIAKAD database lengkap dengan IPK & Judul TA ke tabel wisudawan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses penarikan data mahasiswa & nilai dari SIAKAD...');

        $activePeriode = $this->option('periode')
            ? PeriodeWisuda::find($this->option('periode'))
            : (PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first());

        if (!$activePeriode) {
            $this->error('Periode wisuda tidak ditemukan! Harap buat periode wisuda terlebih dahulu.');
            return 1;
        }

        $this->info("Menghubungkan ke SIAKAD Database untuk Periode Wisuda: {$activePeriode->nama_periode}...");

        try {
            $query = DB::connection('siakad')
                ->table('viewMahasiswaPt as pt')
                ->leftJoin('wsia_mahasiswa as m', 'pt.id_pd', '=', 'm.id_pd')
                ->leftJoin('viewMahasiswaKeluar as mk', 'pt.nipd', '=', 'mk.nipd')
                ->leftJoin('wsia_kuliah_mahasiswa as km', function ($join) {
                    $join->on('pt.id_reg_pd', '=', 'km.id_reg_pd');
                })
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
                    'km.ipk as siakad_ipk',
                    'mk.judul_skripsi',
                    'mk.tgl_keluar',
                    'mk.tgl_sk_yudisium'
                );

            if ($limit = $this->option('limit')) {
                $query->take((int)$limit);
            }

            $students = $query->get();

            if ($students->isEmpty()) {
                $this->warn('Tidak ada data mahasiswa ditemukan dari SIAKAD.');
                return 0;
            }

            $this->info("Ditemukan {$students->count()} data mahasiswa. Memulai sinkronisasi...");

            $bar = $this->output->createProgressBar($students->count());
            $bar->start();

            $syncedCount = 0;

            foreach ($students as $student) {
                if (empty($student->nim)) {
                    $bar->advance();
                    continue;
                }

                $cleanNim = strtoupper(trim($student->nim));

                // 1. Find or create ProgramStudi
                $prodiName = trim($student->program_studi ?? 'Umum');
                $programStudi = ProgramStudi::firstOrCreate(
                    ['nama_prodi' => $prodiName],
                    [
                        'kode_prodi' => Str::slug($prodiName),
                        'jenjang' => str_contains($prodiName, 'D4') ? 'D4' : (str_contains($prodiName, 'D3') ? 'D3' : 'S1'),
                    ]
                );

                // 2. Calculate real IPK & Predikat
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

                // 3. Judul Tugas Akhir
                $judulTa = !empty(trim($student->judul_skripsi ?? '')) && trim($student->judul_skripsi) !== '-'
                    ? trim($student->judul_skripsi)
                    : 'Tugas Akhir ' . $prodiName;

                // 4. Tanggal Lulus
                $tglLulus = !empty($student->tgl_sk_yudisium) && $student->tgl_sk_yudisium !== '0000-00-00'
                    ? $student->tgl_sk_yudisium
                    : (!empty($student->tgl_keluar) && $student->tgl_keluar !== '0000-00-00' ? $student->tgl_keluar : date('Y-m-d'));

                // 5. Create or update Wisudawan record
                Wisudawan::updateOrCreate(
                    ['nim' => $cleanNim],
                    [
                        'periode_wisuda_id' => $activePeriode->id,
                        'program_studi_id'  => $programStudi->id,
                        'nama_lengkap'      => trim($student->nama_lengkap ?? $cleanNim),
                        'jenis_kelamin'     => strtoupper($student->jenis_kelamin ?? 'L'),
                        'email'             => !empty($student->email) ? $student->email : ($cleanNim . '@poltekindonusa.ac.id'),
                        'nomor_hp'          => $student->nomor_hp ?? null,
                        'nik'               => $student->nik ?? null,
                        'tempat_lahir'      => $student->tempat_lahir ?? null,
                        'tanggal_lahir'     => !empty($student->tanggal_lahir) && $student->tanggal_lahir !== '0000-00-00' ? $student->tanggal_lahir : null,
                        'nama_ayah'         => $student->nama_ayah ?? null,
                        'nama_ibu'          => $student->nama_ibu ?? null,
                        'alamat'            => $student->alamat ?? null,
                        'ipk'               => $realIpk,
                        'predikat_kelulusan'=> $predikat,
                        'judul_ta'          => $judulTa,
                        'tanggal_lulus'     => $tglLulus,
                        'status_verifikasi' => 'verified',
                        'qr_code_token'     => Str::random(32),
                    ]
                );

                $syncedCount++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Berhasil meretrieve & men-sinkronisasi {$syncedCount} data wisudawan (termasuk IPK & Judul TA) dari SIAKAD!");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error saat menarik data SIAKAD: ' . $e->getMessage());
            return 1;
        }
    }
}
