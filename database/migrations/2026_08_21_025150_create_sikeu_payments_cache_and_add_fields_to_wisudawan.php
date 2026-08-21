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
        // 1. Create sikeu_payments_cache table
        if (!Schema::hasTable('sikeu_payments_cache')) {
            Schema::create('sikeu_payments_cache', function (Blueprint $table) {
                $table->id();
                $table->string('nim')->index();
                $table->string('nama')->nullable();
                $table->string('status_bayar')->default('belum_lunas')->index(); // 'lunas', 'belum_lunas'
                $table->unsignedBigInteger('total_bayar')->default(0);
                $table->unsignedBigInteger('total_tagihan')->default(0);
                $table->integer('jumlah_undangan_extra')->default(0);
                $table->integer('total_kuota_undangan')->default(2);
                $table->integer('snack_kuota')->default(3);
                $table->dateTime('tanggal_bayar')->nullable();
                $table->string('no_transaksi')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('metode_bayar')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->foreignId('wisudawan_id')->nullable()->constrained('wisudawan')->nullOnDelete();
                $table->timestamps();

                $table->unique('nim');
            });
        }

        // 2. Add payment fields to wisudawan table
        Schema::table('wisudawan', function (Blueprint $table) {
            if (!Schema::hasColumn('wisudawan', 'status_pembayaran_sikeu')) {
                $table->string('status_pembayaran_sikeu')->default('belum_lunas')->index(); // 'lunas', 'belum_lunas'
            }
            if (!Schema::hasColumn('wisudawan', 'nominal_bayar_wisuda')) {
                $table->unsignedBigInteger('nominal_bayar_wisuda')->default(0);
            }
            if (!Schema::hasColumn('wisudawan', 'nominal_tagihan_wisuda')) {
                $table->unsignedBigInteger('nominal_tagihan_wisuda')->default(0);
            }
            if (!Schema::hasColumn('wisudawan', 'jumlah_undangan_extra_sikeu')) {
                $table->integer('jumlah_undangan_extra_sikeu')->default(0);
            }
            if (!Schema::hasColumn('wisudawan', 'tanggal_bayar_sikeu')) {
                $table->dateTime('tanggal_bayar_sikeu')->nullable();
            }
            if (!Schema::hasColumn('wisudawan', 'nomor_transaksi_sikeu')) {
                $table->string('nomor_transaksi_sikeu')->nullable();
            }
            if (!Schema::hasColumn('wisudawan', 'sikeu_synced_at')) {
                $table->timestamp('sikeu_synced_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wisudawan', function (Blueprint $table) {
            $table->dropColumn([
                'status_pembayaran_sikeu',
                'nominal_bayar_wisuda',
                'nominal_tagihan_wisuda',
                'jumlah_undangan_extra_sikeu',
                'tanggal_bayar_sikeu',
                'nomor_transaksi_sikeu',
                'sikeu_synced_at',
            ]);
        });

        Schema::dropIfExists('sikeu_payments_cache');
    }
};
