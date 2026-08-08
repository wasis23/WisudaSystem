<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasWisudawan extends Model
{
    use HasFactory;

    protected $table = 'berkas_wisudawan';

    protected $fillable = [
        'wisudawan_id',
        'syarat_wisuda_id',
        'file_path',
        'original_filename',
        'status',
        'catatan',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function wisudawan(): BelongsTo
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }

    public function syaratWisuda(): BelongsTo
    {
        return $this->belongsTo(SyaratWisuda::class, 'syarat_wisuda_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
