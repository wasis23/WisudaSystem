<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideWisudawan extends Model
{
    use HasFactory;

    protected $table = 'slide_wisudawan';

    protected $fillable = [
        'wisudawan_id',
        'template_name',
        'render_image_path',
        'canva_export_url',
        'status_render',
    ];

    public function wisudawan(): BelongsTo
    {
        return $this->belongsTo(Wisudawan::class, 'wisudawan_id');
    }
}
