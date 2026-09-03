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
            $table->string('buku_kenangan_default_foto', 500)->nullable()->after('buku_kenangan_footer_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_wisuda', function (Blueprint $table) {
            $table->dropColumn('buku_kenangan_default_foto');
        });
    }
};
