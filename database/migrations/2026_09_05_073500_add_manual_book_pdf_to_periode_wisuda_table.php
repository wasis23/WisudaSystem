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
            $table->string('manual_book_pdf', 500)->nullable()->after('buku_kenangan_default_foto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_wisuda', function (Blueprint $table) {
            $table->dropColumn('manual_book_pdf');
        });
    }
};
