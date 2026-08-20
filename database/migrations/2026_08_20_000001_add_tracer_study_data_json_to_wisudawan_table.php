<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->json('tracer_study_data')->nullable()->after('tracer_kesesuaian_prodi');
        });
    }

    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn('tracer_study_data');
        });
    }
};
