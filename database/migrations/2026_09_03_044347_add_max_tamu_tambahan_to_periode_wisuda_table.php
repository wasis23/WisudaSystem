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
        Schema::table('periode_wisuda', function (Blueprint $table) {
            $table->integer('max_tamu_tambahan')->default(60)->after('kuota_peserta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_wisuda', function (Blueprint $table) {
            $table->dropColumn('max_tamu_tambahan');
        });
    }
};
