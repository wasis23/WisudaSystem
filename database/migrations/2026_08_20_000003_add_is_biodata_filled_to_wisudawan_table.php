<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->boolean('is_biodata_filled')->default(false)->after('is_tracer_study_filled');
        });
    }

    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn('is_biodata_filled');
        });
    }
};
