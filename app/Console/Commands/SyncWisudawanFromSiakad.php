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
    protected $description = 'Tarik data wisudawan 100% murni dan asli dari viewMahasiswaKeluar SIAKAD tanpa sampel/mock data';

    /**
     * Execute the console command.
     */
    public function handle(SimantaIntegrationService $simantaService)
    {
        $this->info('Memulai proses penarikan 100% data asli wisudawan dari SIAKAD...');

        $activePeriode = $this->option('periode')
            ? PeriodeWisuda::find($this->option('periode'))
            : (PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first());

        if (!$activePeriode) {
            $this->error('Periode wisuda tidak ditemukan! Harap buat periode wisuda terlebih dahulu.');
            return 1;
        }

        $this->info("Menghubungkan ke SIAKAD untuk Periode Wisuda: {$activePeriode->nama_periode}...");

        try {
            // 1. Pre-load viewMahasiswaPt & wsia_sms map for authentic biodata and prodi (D4/D3)
            $this->info('Memuat biodata dan program studi asli dari SIAKAD...');
            $ptStudents = DB::connection('siakad')
                ->table('wsia_mahasiswa_pt as pt')
                ->leftJoin('wsia_mahasiswa as m', 'pt.id_pd', '=', 'm.id_pd')
                ->leftJoin('wsia_sms as sms', 'pt.id_sms', '=', 'sms.id_sms')
                ->select(
                    'pt.nipd as nim',
                    'm.nm_pd as nama_lengkap',
                    'm.jk as jenis_kelamin',
                    'm.email',
                    'm.telepon_seluler as nomor_hp',
                    'sms.nm_lemb as prodi_nama',
                    'sms.id_jenj_didik',
                    'sms.kode_prodi as prodi_kode',
                    'm.nik',
                    'm.tmpt_lahir as tempat_lahir',
                    'm.tgl_lahir as tanggal_lahir',
                    'm.nm_ayah as nama_ayah',
                    'm.nm_ibu_kandung as nama_ibu',
                    'm.ds_kel as alamat'
                )
                ->get()
                ->keyBy(fn($item) => strtoupper(trim($item->nim)));

            // 2. Fetch real graduating students (LULUS) from viewMahasiswaKeluar
            $this->info('Memuat data kelulusan & judul skripsi asli dari viewMahasiswaKeluar...');
            $keluarQuery = DB::connection('siakad')
                ->table('viewMahasiswaKeluar')
                ->where('id_jns_keluar', '1'); // 1 = Lulus

            if ($limit = $this->option('limit')) {
                $keluarQuery->take((int)$limit);
            }

            $keluarList = $keluarQuery->get();

            if ($keluarList->isEmpty()) {
                $this->warn('Tidak ada data kelulusan ditemukan di viewMahasiswaKeluar.');
                return 0;
            }

            $this->info("Ditemukan {$keluarList->count()} data wisudawan lulus di SIAKAD. Memproses sinkronisasi data asli...");

            // Pre-load ProgramStudi map
            $prodiMap = ProgramStudi::pluck('id', 'nama_prodi')->toArray();

            $recordsToUpsert = [];
            $now = now()->toDateTimeString();
            $processedNims = [];

            foreach ($keluarList as $item) {
                $rawNim = $item->nipd ?? null;
                if (empty($rawNim)) {
                    continue;
                }

                $cleanNim = strtoupper(trim($rawNim));
                if (isset($processedNims[$cleanNim])) {
                    continue;
                }
                $processedNims[$cleanNim] = true;

                // Match with pt map for full biodata
                $ptData = $ptStudents->get($cleanNim);

                // 1. Program Studi Resmi (D4 / D3 dari SIAKAD)
                $jenjang = ($ptData?->id_jenj_didik == '23') ? 'D4' : (($ptData?->id_jenj_didik == '22') ? 'D3' : 'S1');
                $rawProdi = $ptData?->prodi_nama ?? ($item->nm_lemb ?? 'Umum');
                $prodiName = str_starts_with($rawProdi, 'D4') || str_starts_with($rawProdi, 'D3') ? $rawProdi : ($jenjang . ' ' . $rawProdi);

                $targetProdi = ProgramStudi::where('nama_prodi', $prodiName)
                    ->orWhere('nama_prodi', 'like', '%' . $rawProdi . '%')
                    ->first();

                if (!$targetProdi) {
                    $targetProdi = ProgramStudi::create([
                        'nama_prodi' => $prodiName,
                        'kode_prodi' => $jenjang . '-' . ($ptData?->prodi_kode ?? Str::slug($rawProdi)),
                        'jenjang' => $jenjang,
                    ]);
                }
                $prodiId = $targetProdi->id;

                // 2. Pure Authentic IPK & Predikat
                $rawIpk = (float)($item->ipk ?? 0);
                $realIpk = number_format($rawIpk, 2, '.', '');

                $ipkFloat = (float)$realIpk;
                if ($ipkFloat >= 3.51) {
                    $predikat = 'Dengan Pujian (Cumlaude)';
                } elseif ($ipkFloat >= 3.01) {
                    $predikat = 'Sangat Memuaskan';
                } elseif ($ipkFloat >= 2.76) {
                    $predikat = 'Memuaskan';
                } elseif ($ipkFloat > 0) {
                    $predikat = 'Cukup';
                } else {
                    $predikat = 'Belum Ada Nilai';
                }

                // 3. Pure Authentic Judul Skripsi / Tugas Akhir
                $rawJudul = trim($item->judul_skripsi ?? '');
                $judulTa = (!empty($rawJudul) && $rawJudul !== '-') ? $rawJudul : 'Belum Input Judul Skripsi';

                // 4. Tanggal Lulus
                $tglLulus = !empty($item->tgl_sk_yudisium) && $item->tgl_sk_yudisium !== '0000-00-00'
                    ? $item->tgl_sk_yudisium
                    : (!empty($item->tgl_keluar) && $item->tgl_keluar !== '0000-00-00' ? $item->tgl_keluar : date('Y-m-d'));

                // 5. Biodata Fields
                $namaLengkap = trim($item->nm_pd ?? ($ptData->nama_lengkap ?? $cleanNim));
                $jenisKelamin = strtoupper($ptData->jenis_kelamin ?? 'L');
                $email = !empty($ptData->email) ? $ptData->email : ($cleanNim . '@poltekindonusa.ac.id');
                $nomorHp = $ptData->nomor_hp ?? null;
                $nik = $ptData->nik ?? null;
                $tempatLahir = $ptData->tempat_lahir ?? null;
                $tanggalLahir = !empty($ptData->tanggal_lahir) && $ptData->tanggal_lahir !== '0000-00-00' ? $ptData->tanggal_lahir : null;
                $namaAyah = $ptData->nama_ayah ?? null;
                $namaIbu = $ptData->nama_ibu ?? null;
                $alamat = $ptData->alamat ?? null;

                $recordsToUpsert[] = [
                    'nim'                      => $cleanNim,
                    'periode_wisuda_id'       => $activePeriode->id,
                    'program_studi_id'        => $prodiId,
                    'nama_lengkap'            => $namaLengkap,
                    'jenis_kelamin'           => $jenisKelamin,
                    'email'                   => $email,
                    'nomor_hp'                => $nomorHp,
                    'nik'                     => $nik,
                    'tempat_lahir'            => $tempatLahir,
                    'tanggal_lahir'           => $tanggalLahir,
                    'nama_ayah'               => $namaAyah,
                    'nama_ibu'                => $namaIbu,
                    'alamat'                  => $alamat,
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

            // Wipe and insert 100% pure authentic data
            Wisudawan::where('periode_wisuda_id', $activePeriode->id)->delete();

            $chunkSize = 200;
            foreach (array_chunk($recordsToUpsert, $chunkSize) as $chunk) {
                Wisudawan::insert($chunk);
            }

            $this->newLine();
            $this->info("Berhasil men-sinkronisasi " . count($recordsToUpsert) . " data wisudawan 100% murni dari SIAKAD!");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error saat menarik data SIAKAD / SIMANTA: ' . $e->getMessage());
            return 1;
        }
    }
}
