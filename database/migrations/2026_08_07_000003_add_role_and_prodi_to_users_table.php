<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin_utama', 'verifikator_prodi', 'verifikator_keuangan', 'panitia_presensi', 'wisudawan'])->default('wisudawan')->after('password');
            $table->foreignId('program_studi_id')->nullable()->after('role')->constrained('program_studi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['program_studi_id']);
            $table->dropColumn(['role', 'program_studi_id']);
        });
    }
};
