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
                'bg_image' => 'stage_bg/background.png',
                'photo_x' => 300,
                'photo_y' => 470,
                'photo_w' => 480,
                'photo_h' => 600,
                'nama_x' => 40,
                'nama_y' => 440,
                'nama_font_size' => 44,
                'nim_x' => 80,
                'nim_y' => 1100,
                'nim_font_size' => 24,
                'prodi_x' => 80,
                'prodi_y' => 1100,
                'prodi_font_size' => 24,
                'ipk_x' => 80,
                'ipk_y' => 1100,
                'ipk_font_size' => 24,
                'ta_x' => 80,
                'ta_y' => 1100,
                'ta_font_size' => 20,
                'ta_max_w' => 920,
            ]
        );
    }

    public static function getActiveConfig()
    {
        return self::getDefaultConfig();
    }
}
