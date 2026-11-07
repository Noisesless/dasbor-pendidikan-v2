<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $fillable = [
        'school_id', 'nama_lengkap', 'nip', 'nuptk', 'jenis_kelamin', 'npsn',
        'satuan_pendidikan', 'jenjang', 'kecamatan', 'status_kepegawaian', 'sertifikasi', 'bidang_studi',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
