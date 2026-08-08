<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wisudawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('periode_wisuda_id')->constrained('periode_wisuda')->cascadeOnDelete();
            $table->foreignId('program_studi_id')->constrained('program_studi')->cascadeOnDelete();
            $table->string('nim');
            $table->string('nama_lengkap');
            $table->string('nik')->nullable();
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('email');
            $table->string('nomor_hp');
            $table->text('alamat')->nullable();
            $table->decimal('ipk', 3, 2);
            $table->string('predikat_kelulusan');
            $table->date('tanggal_lulus');
            $table->text('judul_ta');
            $table->string('nama_ayah')->nullable();
            $table->string('nama_ibu')->nullable();
            $table->string('pas_foto')->nullable();
            $table->string('qr_code_token')->unique();
            $table->enum('status_verifikasi', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('catatan_verifikasi')->nullable();
            $table->string('nomor_kursi')->nullable();
            $table->integer('urutan_pemanggilan')->nullable();
            $table->boolean('is_hadir')->default(false);
            $table->dateTime('waktu_presensi')->nullable();
            $table->timestamps();

            $table->unique(['periode_wisuda_id', 'nim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wisudawan');
    }
};
