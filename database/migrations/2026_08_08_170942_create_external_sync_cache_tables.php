<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cache tabel untuk menyimpan data pegawai dari SIMPEG.
     * Di-sync sekali (atau terjadwal), tidak perlu request ke SIMPEG setiap saat.
     */
    public function up(): void
    {
        // ── 1. SIMPEG Cache ────────────────────────────────────────────────────
        if (!Schema::hasTable('simpeg_employees_cache')) {
            Schema::create('simpeg_employees_cache', function (Blueprint $table) {
                $table->id();
                $table->string('id_sdm')->nullable()->index();
                $table->string('nidn')->nullable()->index();
                $table->string('nip')->nullable();
                $table->string('username')->index();
                $table->string('nama');
                $table->string('status')->default('Tendik');   // Dosen / Tendik / etc
                $table->string('jenis')->default('tendik');    // dosen / tendik
                $table->string('email')->nullable();
                $table->timestamp('synced_at')->nullable();    // kapan data ini di-pull dari SIMPEG
                $table->timestamps();

                $table->unique('username');
            });
        }

        // ── 2. SIMANTA Mahasiswa Lulus Cache ──────────────────────────────────
        if (!Schema::hasTable('simanta_mahasiswa_lulus_cache')) {
            Schema::create('simanta_mahasiswa_lulus_cache', function (Blueprint $table) {
                $table->id();
                $table->string('nim')->index();
                $table->string('nama')->nullable();
                $table->text('judul_ta')->nullable();
                $table->string('kode_prodi', 5)->nullable();
                $table->string('nama_prodi')->nullable();
                $table->string('status_persetujuan')->nullable();
                $table->tinyInteger('status_lulus')->default(0);  // 0=Belum, 1=Lulus
                $table->date('tanggal_pengajuan')->nullable();
                $table->date('tanggal_pendaftaran_ta')->nullable();
                $table->date('tanggal_seminar_proposal')->nullable();
                $table->date('tanggal_pendadaran')->nullable()->index(); // Tanggal sidang — kunci filter
                $table->date('tanggal_pengumpulan_laporan')->nullable();

                // Rentang sync yang digunakan untuk mengambil data ini
                $table->date('sync_tgl_dari')->nullable();
                $table->date('sync_tgl_sampai')->nullable();
                $table->timestamp('synced_at')->nullable();

                // Link ke wisudawan jika sudah ada di sistem wisuda
                $table->foreignId('wisudawan_id')->nullable()->constrained('wisudawan')->nullOnDelete();

                $table->timestamps();

                // Setiap NIM hanya satu record (upsert saat sync)
                $table->unique('nim');
            });
        }

        // ── 3. Sync Log ────────────────────────────────────────────────────────
        if (!Schema::hasTable('external_sync_logs')) {
            Schema::create('external_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->string('source');          // simpeg / simanta / sikeu
                $table->string('action');          // sync_all / sync_lulus / check_payment
                $table->integer('records_fetched')->default(0);
                $table->integer('records_inserted')->default(0);
                $table->integer('records_updated')->default(0);
                $table->string('status');          // success / partial / failed
                $table->text('notes')->nullable();
                $table->json('filter_params')->nullable(); // parameter yang digunakan saat sync
                $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('external_sync_logs');
        Schema::dropIfExists('simanta_mahasiswa_lulus_cache');
        Schema::dropIfExists('simpeg_employees_cache');
    }
};
