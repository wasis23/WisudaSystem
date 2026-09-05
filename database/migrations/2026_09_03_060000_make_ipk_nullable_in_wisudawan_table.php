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
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->decimal('ipk', 3, 2)->nullable()->default(null)->change();
            $table->string('predikat_kelulusan')->nullable()->default('-')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->decimal('ipk', 3, 2)->nullable(false)->change();
            $table->string('predikat_kelulusan')->nullable(false)->change();
        });
    }
};
