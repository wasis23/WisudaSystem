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
        Schema::table('wisudawan', function (Blueprint $table) {
            if (!Schema::hasColumn('wisudawan', 'waktu_presensi_venue')) {
                $table->timestamp('waktu_presensi_venue')->nullable()->after('waktu_presensi');
            }
        });

        Schema::table('wisudawan_tamu_tambahan', function (Blueprint $table) {
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'is_hadir_gate')) {
                $table->boolean('is_hadir_gate')->default(false)->after('qr_guest_token');
            }
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'is_hadir_venue')) {
                $table->boolean('is_hadir_venue')->default(false)->after('is_hadir_gate');
            }
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'waktu_presensi_gate')) {
                $table->timestamp('waktu_presensi_gate')->nullable()->after('waktu_presensi');
            }
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'waktu_presensi_venue')) {
                $table->timestamp('waktu_presensi_venue')->nullable()->after('waktu_presensi_gate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            if (Schema::hasColumn('wisudawan', 'waktu_presensi_venue')) {
                $table->dropColumn('waktu_presensi_venue');
            }
        });

        Schema::table('wisudawan_tamu_tambahan', function (Blueprint $table) {
            if (Schema::hasColumn('wisudawan_tamu_tambahan', 'is_hadir_gate')) {
                $table->dropColumn(['is_hadir_gate', 'is_hadir_venue', 'waktu_presensi_gate', 'waktu_presensi_venue']);
            }
        });
    }
};
