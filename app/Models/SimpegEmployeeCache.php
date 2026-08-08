<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimpegEmployeeCache extends Model
{
    protected $table = 'simpeg_employees_cache';

    protected $fillable = [
        'id_sdm', 'nidn', 'nip', 'username', 'nama',
        'status', 'jenis', 'email', 'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    /**
     * Scope: hanya dosen
     */
    public function scopeDosen($query)
    {
        return $query->where('jenis', 'dosen');
    }

    /**
     * Scope: hanya tendik/pegawai
     */
    public function scopeTendik($query)
    {
        return $query->where('jenis', 'tendik');
    }

    /**
     * Scope: search by nama / nidn / username
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('nama', 'like', "%{$keyword}%")
              ->orWhere('nidn', 'like', "%{$keyword}%")
              ->orWhere('username', 'like', "%{$keyword}%")
              ->orWhere('nip', 'like', "%{$keyword}%");
        });
    }
}
