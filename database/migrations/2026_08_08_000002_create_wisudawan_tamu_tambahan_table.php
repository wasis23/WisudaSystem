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
        if (!Schema::hasTable('wisudawan_tamu_tambahan')) {
            Schema::create('wisudawan_tamu_tambahan', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wisudawan_id')->constrained('wisudawan')->onDelete('cascade');
                $table->string('nama_tamu');
                $table->string('hubungan')->nullable();
                $table->string('qr_guest_token')->unique()->nullable();
                $table->boolean('is_hadir')->default(false);
                $table->boolean('snack_diambil')->default(false);
                $table->timestamp('waktu_presensi')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wisudawan_tamu_tambahan');
    }
};
