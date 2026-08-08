<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti kolom 'role' dari enum lama ke string terbuka,
     * agar nilai 'security' dan 'receptionist' bisa diterima.
     * SQLite tidak support ALTER COLUMN enum langsung,
     * jadi kita lakukan dengan cara: backup → drop → recreate.
     */
    public function up(): void
    {
        // Backup nilai role yang ada
        $users = DB::table('users')->select('id', 'role')->get();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')
                ->default('wisudawan')
                ->after('password');
        });

        // Restore nilai role yang sudah ada sebelumnya
        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $user->role]);
        }
    }

    public function down(): void
    {
        $users = DB::table('users')->select('id', 'role')->get();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin_utama', 'verifikator_prodi', 'verifikator_keuangan', 'panitia_presensi', 'wisudawan'])
                ->default('wisudawan')
                ->after('password');
        });

        foreach ($users as $user) {
            $safeRole = in_array($user->role, ['admin_utama', 'verifikator_prodi', 'verifikator_keuangan', 'panitia_presensi', 'wisudawan'])
                ? $user->role
                : 'wisudawan';

            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $safeRole]);
        }
    }
};
