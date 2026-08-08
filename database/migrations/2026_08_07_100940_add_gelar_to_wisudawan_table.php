<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->string('gelar')->nullable()->default('A.Md.Kom.')->after('nama_lengkap');
        });
    }

    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn('gelar');
        });
    }
};
