<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->boolean('is_tracer_study_filled')->default(false)->after('waktu_presensi');
            $table->string('tracer_status_pekerjaan')->nullable()->after('is_tracer_study_filled');
            $table->string('tracer_nama_instansi')->nullable()->after('tracer_status_pekerjaan');
            $table->string('tracer_jabatan')->nullable()->after('tracer_nama_instansi');
            $table->string('tracer_pendapatan')->nullable()->after('tracer_jabatan');
            $table->string('tracer_kesesuaian_prodi')->nullable()->after('tracer_pendapatan');
        });
    }

    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn([
                'is_tracer_study_filled',
                'tracer_status_pekerjaan',
                'tracer_nama_instansi',
                'tracer_jabatan',
                'tracer_pendapatan',
                'tracer_kesesuaian_prodi',
            ]);
        });
    }
};
