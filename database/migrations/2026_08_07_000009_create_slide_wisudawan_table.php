<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_wisudawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wisudawan_id')->constrained('wisudawan')->cascadeOnDelete();
            $table->string('template_name')->default('default_landscape');
            $table->string('render_image_path')->nullable();
            $table->string('canva_export_url')->nullable();
            $table->enum('status_render', ['pending', 'rendered', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_wisudawan');
    }
};
