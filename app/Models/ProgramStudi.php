<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $table = 'program_studi';

    protected $fillable = [
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'gelar',
        'kaprodi_nama',
        'kaprodi_nip',
    ];

    public function wisudawans()
    {
        return $this->hasMany(Wisudawan::class, 'program_studi_id');
    }
}
