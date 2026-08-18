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
            if (!Schema::hasColumn('wisudawan', 'dosen_pembimbing_1')) {
                $table->string('dosen_pembimbing_1')->nullable()->after('judul_ta');
            }
            if (!Schema::hasColumn('wisudawan', 'dosen_pembimbing_2')) {
                $table->string('dosen_pembimbing_2')->nullable()->after('dosen_pembimbing_1');
            }
            if (!Schema::hasColumn('wisudawan', 'dosen_penguji')) {
                $table->string('dosen_penguji')->nullable()->after('dosen_pembimbing_2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn(['dosen_pembimbing_1', 'dosen_pembimbing_2', 'dosen_penguji']);
        });
    }
};
