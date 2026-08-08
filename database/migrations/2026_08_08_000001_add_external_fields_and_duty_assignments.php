<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add extra columns to wisudawan table
        Schema::table('wisudawan', function (Blueprint $table) {
            if (!Schema::hasColumn('wisudawan', 'jumlah_tamu_tambahan')) {
                $table->integer('jumlah_tamu_tambahan')->default(0)->after('is_in_auditorium');
            }
            if (!Schema::hasColumn('wisudawan', 'tamu_tambahan_scanned')) {
                $table->integer('tamu_tambahan_scanned')->default(0)->after('jumlah_tamu_tambahan');
            }
            if (!Schema::hasColumn('wisudawan', 'status_kelulusan_simanta')) {
                $table->string('status_kelulusan_simanta')->default('LULUS')->nullable()->after('tamu_tambahan_scanned');
            }
        });

        // 2. Table to store SIMPEG employees assigned to Security / Receptionist scan duty
        if (!Schema::hasTable('duty_assignments')) {
            Schema::create('duty_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('simpeg_id_sdm')->nullable();
                $table->string('simpeg_username');
                $table->string('simpeg_nip')->nullable();
                $table->string('nama_pegawai');
                $table->string('duty_role'); // 'security' or 'receptionist'
                $table->boolean('is_active')->default(true);
                $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Table to store additional guests registered by wisudawan
        if (!Schema::hasTable('wisudawan_tamu_tambahan')) {
            Schema::create('wisudawan_tamu_tambahan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wisudawan_id')->constrained('wisudawan')->onDelete('cascade');
                $table->string('nama_tamu');
                $table->string('hubungan')->default('Orang Tua / Wali'); // Ayah, Ibu, Suami, Istri, Saudarai, dll
                $table->string('qr_guest_token')->unique()->nullable();
                $table->boolean('is_hadir')->default(false);
                $table->boolean('snack_diambil')->default(false);
                $table->timestamp('waktu_presensi')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wisudawan_tamu_tambahan');
        Schema::dropIfExists('duty_assignments');

        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn(['jumlah_tamu_tambahan', 'tamu_tambahan_scanned', 'status_kelulusan_simanta']);
        });
    }
};
