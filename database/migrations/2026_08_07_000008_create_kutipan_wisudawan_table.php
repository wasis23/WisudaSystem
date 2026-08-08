<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kutipan_wisudawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wisudawan_id')->constrained('wisudawan')->cascadeOnDelete();
            $table->text('kesan_pesan')->nullable();
            $table->string('cita_cita')->nullable();
            $table->text('motto_hidup')->nullable();
            $table->json('social_media_handles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kutipan_wisudawan');
    }
};
