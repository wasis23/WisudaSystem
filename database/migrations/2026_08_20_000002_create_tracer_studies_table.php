<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracer_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wisudawan_id')->constrained('wisudawan')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Section 1: Data Diri & Akademik
            $table->string('nim')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->string('email')->nullable();
            $table->string('no_whatsapp')->nullable();
            $table->text('prodi')->nullable();
            $table->string('prodi_lainnya')->nullable();
            $table->string('jenis_kelas')->nullable();
            $table->text('alamat_lengkap')->nullable();

            // Section 2: Status Pekerjaan & Karir
            $table->text('status_saat_ini')->nullable();
            $table->string('status_lainnya')->nullable();
            $table->string('tempat_bekerja')->nullable();
            $table->text('gaji_per_bulan')->nullable();
            $table->text('keselarasan_pekerjaan')->nullable();
            $table->text('kesesuaian_pendidikan')->nullable();
            $table->text('waktu_tunggu')->nullable();
            $table->text('alamat_tempat_kerja')->nullable();
            $table->text('jenis_instansi')->nullable();
            $table->string('jenis_instansi_lainnya')->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->text('posisi_jabatan')->nullable();
            $table->string('posisi_lainnya')->nullable();
            $table->text('cakupan_tempat_kerja')->nullable();
            $table->string('tingkat_tempat_kerja_lainnya')->nullable();

            // Section 3: Kewirausahaan & Studi Lanjut
            $table->string('nama_usaha')->nullable();
            $table->text('gaji_usaha')->nullable();
            $table->text('keselarasan_usaha')->nullable();
            $table->text('studi_lanjut')->nullable();
            $table->string('kampus_studi_lanjut')->nullable();
            $table->text('alamat_kampus_studi_lanjut')->nullable();
            $table->text('sumber_dana')->nullable();
            $table->string('sumber_dana_lainnya')->nullable();

            // Section 4: Evaluasi Kompetensi Saat Lulus (1-5)
            $table->unsignedTinyInteger('lulus_etika')->default(0);
            $table->unsignedTinyInteger('lulus_keahlian_ilmu')->default(0);
            $table->unsignedTinyInteger('lulus_bahasa_inggris')->default(0);
            $table->unsignedTinyInteger('lulus_teknologi_informasi')->default(0);
            $table->unsignedTinyInteger('lulus_komunikasi')->default(0);
            $table->unsignedTinyInteger('lulus_kerjasama_tim')->default(0);
            $table->unsignedTinyInteger('lulus_pengembangan_diri')->default(0);

            // Section 4: Evaluasi Kompetensi Diperlukan Kerja (1-5)
            $table->unsignedTinyInteger('kerja_etika')->default(0);
            $table->unsignedTinyInteger('kerja_keahlian_ilmu')->default(0);
            $table->unsignedTinyInteger('kerja_bahasa_inggris')->default(0);
            $table->unsignedTinyInteger('kerja_teknologi_informasi')->default(0);
            $table->unsignedTinyInteger('kerja_komunikasi')->default(0);
            $table->unsignedTinyInteger('kerja_kerjasama_tim')->default(0);
            $table->unsignedTinyInteger('kerja_pengembangan_diri')->default(0);

            // Section 4: Penekanan Metode Pembelajaran (1-5)
            $table->unsignedTinyInteger('metode_perkuliahan')->default(0);
            $table->unsignedTinyInteger('metode_demonstrasi')->default(0);
            $table->unsignedTinyInteger('metode_proyek_riset')->default(0);
            $table->unsignedTinyInteger('metode_magang')->default(0);
            $table->unsignedTinyInteger('metode_praktikum')->default(0);
            $table->unsignedTinyInteger('metode_kerja_lapangan')->default(0);
            $table->unsignedTinyInteger('metode_diskusi')->default(0);

            // Section 5: Kepuasan & Masukan
            $table->text('kepuasan_layanan')->nullable();
            $table->text('saran_masukan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_studies');
    }
};
