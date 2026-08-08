<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_prodi')->unique();
            $table->string('nama_prodi');
            $table->string('jenjang')->default('D3'); // D3, D4, S1
            $table->string('kaprodi_nama')->nullable();
            $table->string('kaprodi_nip')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
