<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageLayoutConfig extends Model
{
    use HasFactory;

    protected $table = 'stage_layout_configs';

    protected $fillable = [
        'periode_wisuda_id',
        'bg_image',
        'photo_x',
        'photo_y',
        'photo_w',
        'photo_h',
        'nama_x',
        'nama_y',
        'nama_font_size',
        'nim_x',
        'nim_y',
        'nim_font_size',
        'prodi_x',
        'prodi_y',
        'prodi_font_size',
        'ipk_x',
        'ipk_y',
        'ipk_font_size',
        'ta_x',
        'ta_y',
        'ta_font_size',
        'ta_max_w',
    ];

    public static function getDefaultConfig()
    {
        $activePeriode = PeriodeWisuda::getActive() ?? PeriodeWisuda::latest()->first();
        
        return self::firstOrCreate(
            ['periode_wisuda_id' => $activePeriode?->id],
            [
                'photo_x' => 100,
                'photo_y' => 150,
                'photo_w' => 320,
                'photo_h' => 420,
                'nama_x' => 480,
                'nama_y' => 180,
                'nama_font_size' => 48,
                'nim_x' => 480,
                'nim_y' => 250,
                'nim_font_size' => 24,
                'prodi_x' => 480,
                'prodi_y' => 290,
                'prodi_font_size' => 24,
                'ipk_x' => 480,
                'ipk_y' => 340,
                'ipk_font_size' => 28,
                'ta_x' => 480,
                'ta_y' => 400,
                'ta_font_size' => 20,
                'ta_max_w' => 700,
            ]
        );
    }

    public static function getActiveConfig()
    {
        return self::getDefaultConfig();
    }
}
