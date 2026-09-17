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
        if (Schema::hasTable('wisudawan') && !Schema::hasColumn('wisudawan', 'is_dummy')) {
            Schema::table('wisudawan', function (Blueprint $table) {
                $table->boolean('is_dummy')->default(false)->after('status_verifikasi')->index();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_dummy')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_dummy')->default(false)->after('role')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wisudawan') && Schema::hasColumn('wisudawan', 'is_dummy')) {
            Schema::table('wisudawan', function (Blueprint $table) {
                $table->dropColumn('is_dummy');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_dummy')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_dummy');
            });
        }
    }
};
