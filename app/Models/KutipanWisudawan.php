<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KutipanWisudawan extends Model
{
    use HasFactory;

    protected $table = 'kutipan_wisudawan';

    protected $fillable = [
        'wisudawan_id',
        'kesan_pesan',
        'cita_cita',
        'motto_hidup',
        'social_media_handles',
    ];

    protected $casts = [
        'social_media_handles' => 'array',
    ];

    public function wisudawan(): BelongsTo
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }
}
