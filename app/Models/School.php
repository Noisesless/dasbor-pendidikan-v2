<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $fillable = [
        'npsn', 'nama_sekolah', 'jenjang', 'status', 'akreditasi', 'kecamatan',
        'kode_wilayah', 'alamat', 'kepala_sekolah', 'telepon', 'latitude', 'longitude',
    ];

    public function studentStats()
    {
        return $this->hasMany(StudentStat::class);
    }

    public function teacherStats()
    {
        return $this->hasMany(TeacherStat::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
}
