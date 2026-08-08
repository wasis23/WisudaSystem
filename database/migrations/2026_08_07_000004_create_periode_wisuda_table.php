<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_wisuda', function (Blueprint $table) {
            $table->id();
            $table->string('nama_periode');
            $table->integer('nomor_periode');
            $table->string('tahun_akademik');
            $table->date('tanggal_pelaksanaan');
            $table->integer('kuota_peserta')->nullable();
            $table->dateTime('tanggal_buka_pendaftaran');
            $table->dateTime('tanggal_tutup_pendaftaran');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_wisuda');
    }
};
