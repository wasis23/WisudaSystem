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
        Schema::table('users', function (Blueprint $table) {
            // Ubah menjadi string untuk menghilangkan limit constraint ENUM dari SQLite
            $table->string('role')->default('wisudawan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore enum limits
            $table->enum('role', ['admin_utama', 'verifikator_prodi', 'verifikator_keuangan', 'panitia_presensi', 'wisudawan'])->default('wisudawan')->change();
        });
    }
};
