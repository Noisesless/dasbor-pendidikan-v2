<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherStat extends Model
{
    protected $fillable = [
        'school_id', 'semester', 'npsn', 'nama_sekolah', 'jenjang', 'kecamatan',
        'jumlah_guru', 'jumlah_sertifikasi', 'jumlah_pns', 'jumlah_pppk',
        'jumlah_gty', 'jumlah_gtt', 'jumlah_honor',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
