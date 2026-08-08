<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyaratWisuda extends Model
{
    use HasFactory;

    protected $table = 'syarat_wisuda';

    protected $fillable = [
        'nama_syarat',
        'deskripsi',
        'format_file',
        'max_file_size_kb',
        'is_wajib',
    ];

    protected $casts = [
        'is_wajib' => 'boolean',
        'max_file_size_kb' => 'integer',
    ];

    public function berkas(): HasMany
    {
        return $this->hasMany(BerkasWisudawan::class, 'syarat_wisuda_id');
    }
}
