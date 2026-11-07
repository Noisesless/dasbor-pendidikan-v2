<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Population extends Model
{
    protected $fillable = [
        'kecamatan', 'tahun',
        'u04_l', 'u04_p', 'u04_t',
        'u56_l', 'u56_p', 'u56_t',
        'u712_l', 'u712_p', 'u712_t',
        'u1315_l', 'u1315_p', 'u1315_t',
        'u1618_l', 'u1618_p', 'u1618_t',
        'u18_t', 'total_penduduk',
    ];
}
