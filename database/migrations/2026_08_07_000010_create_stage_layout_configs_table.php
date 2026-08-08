<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_layout_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_wisuda_id')->nullable()->constrained('periode_wisuda')->cascadeOnDelete();
            $table->string('bg_image')->nullable();
            
            // Photo position & dimensions
            $table->integer('photo_x')->default(100);
            $table->integer('photo_y')->default(150);
            $table->integer('photo_w')->default(320);
            $table->integer('photo_h')->default(420);
            
            // Text positions & font sizes (px)
            $table->integer('nama_x')->default(480);
            $table->integer('nama_y')->default(180);
            $table->integer('nama_font_size')->default(48);
            
            $table->integer('nim_x')->default(480);
            $table->integer('nim_y')->default(250);
            $table->integer('nim_font_size')->default(24);
            
            $table->integer('prodi_x')->default(480);
            $table->integer('prodi_y')->default(290);
            $table->integer('prodi_font_size')->default(24);

            $table->integer('ipk_x')->default(480);
            $table->integer('ipk_y')->default(340);
            $table->integer('ipk_font_size')->default(28);

            $table->integer('ta_x')->default(480);
            $table->integer('ta_y')->default(400);
            $table->integer('ta_font_size')->default(20);
            $table->integer('ta_max_w')->default(700);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_layout_configs');
    }
};
