<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentStat extends Model
{
    protected $fillable = [
        'school_id', 'semester', 'npsn', 'nama_sekolah', 'jenjang', 'kecamatan',
        'total_siswa', 'total_laki', 'total_perempuan', 'usia_sesuai', 'usia_sesuai_laki',
        'usia_sesuai_perempuan', 'usia_5_6', 'usia_7_12', 'usia_13_15', 'usia_16_18', 'jumlah_rombel',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
