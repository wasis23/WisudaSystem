<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SikeuPaymentCache extends Model
{
    protected $table = 'sikeu_payments_cache';

    protected $fillable = [
        'nim',
        'nama',
        'status_bayar',
        'total_bayar',
        'total_tagihan',
        'jumlah_undangan_extra',
        'total_kuota_undangan',
        'snack_kuota',
        'tanggal_bayar',
        'no_transaksi',
        'keterangan',
        'metode_bayar',
        'synced_at',
        'wisudawan_id',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
        'synced_at' => 'datetime',
        'total_bayar' => 'integer',
        'total_tagihan' => 'integer',
        'jumlah_undangan_extra' => 'integer',
        'total_kuota_undangan' => 'integer',
        'snack_kuota' => 'integer',
    ];

    public function wisudawan()
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }

    public function scopeLunas($query)
    {
        return $query->where('status_bayar', 'lunas');
    }

    public function scopeBelumLunas($query)
    {
        return $query->where('status_bayar', 'belum_lunas');
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('nim', 'like', "%{$keyword}%")
              ->orWhere('nama', 'like', "%{$keyword}%")
              ->orWhere('no_transaksi', 'like', "%{$keyword}%");
        });
    }
}
