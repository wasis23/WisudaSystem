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
            if (!Schema::hasColumn('wisudawan', 'foto_keluar_gate')) {
                $table->string('foto_keluar_gate')->nullable()->after('pas_foto');
            }
            if (!Schema::hasColumn('wisudawan', 'waktu_keluar_gate')) {
                $table->timestamp('waktu_keluar_gate')->nullable()->after('waktu_presensi_venue');
            }
        });

        Schema::table('wisudawan_tamu_tambahan', function (Blueprint $table) {
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'foto_keluar_gate')) {
                $table->string('foto_keluar_gate')->nullable()->after('qr_guest_token');
            }
            if (!Schema::hasColumn('wisudawan_tamu_tambahan', 'waktu_keluar_gate')) {
                $table->timestamp('waktu_keluar_gate')->nullable()->after('waktu_presensi_venue');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            if (Schema::hasColumn('wisudawan', 'foto_keluar_gate')) {
                $table->dropColumn(['foto_keluar_gate', 'waktu_keluar_gate']);
            }
        });

        Schema::table('wisudawan_tamu_tambahan', function (Blueprint $table) {
            if (Schema::hasColumn('wisudawan_tamu_tambahan', 'foto_keluar_gate')) {
                $table->dropColumn(['foto_keluar_gate', 'waktu_keluar_gate']);
            }
        });
    }
};
