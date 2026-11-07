<?php

namespace App\Http\Controllers;

use App\Models\TeacherStat;
use App\Models\Teacher;
use App\Support\Filters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuruController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Filters::fromRequest($request);
        extract($filters, EXTR_SKIP);

        $teacherQuery = TeacherStat::where('semester', $semester);

        if ($kecamatan !== '') {
            $teacherQuery->where('kecamatan', $kecamatan);
        }
        if ($jenjang !== '') {
            $teacherQuery->where('jenjang', $jenjang);
        }

        // Komposisi status kepegawaian per jenjang
        $perJenjang = (clone $teacherQuery)
            ->selectRaw('jenjang,
                SUM(jumlah_guru) as total,
                SUM(jumlah_pns) as pns,
                SUM(jumlah_pppk) as pppk,
                SUM(jumlah_gty) as gty,
                SUM(jumlah_gtt) as gtt,
                SUM(jumlah_honor) as honor')
            ->groupBy('jenjang')
            ->get();

        $jenjangUrut = ['PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA', 'PKBM'];
        $komposisiJenjang = [];
        foreach ($jenjangUrut as $j) {
            $row = $perJenjang->firstWhere('jenjang', $j);
            if (! $row) {
                continue;
            }
            $komposisiJenjang[] = [
                'jenjang' => $j,
                'total' => (int) $row->total,
                'pns' => (int) $row->pns,
                'pppk' => (int) $row->pppk,
                'gty' => (int) $row->gty,
                'gtt' => (int) $row->gtt,
                'honor' => (int) $row->honor,
            ];
        }

        // Tren status kepegawaian 5 semester
        $trendSemesters = TeacherStat::distinct()->orderBy('semester')->pluck('semester');
        $trendStatus = [];
        foreach ($trendSemesters as $sem) {
            $q = TeacherStat::where('semester', $sem);
            if ($kecamatan !== '') {
                $q->where('kecamatan', $kecamatan);
            }
            if ($jenjang !== '') {
                $q->where('jenjang', $jenjang);
            }
            $row = $q->selectRaw('SUM(jumlah_guru) as total,
                SUM(jumlah_pns) as pns, SUM(jumlah_pppk) as pppk,
                SUM(jumlah_gty) as gty, SUM(jumlah_gtt) as gtt,
                SUM(jumlah_honor) as honor')->first();
            $trendStatus[] = [
                'label' => Filters::semesterLabel($sem),
                'total' => (int) $row->total,
                'pns' => (int) $row->pns,
                'pppk' => (int) $row->pppk,
                'gty' => (int) $row->gty,
                'gtt' => (int) $row->gtt,
                'honor' => (int) $row->honor,
            ];
        }

        // Rasio siswa-guru per kecamatan (indikasi krisis guru)
        $siswaPerKec = \App\Models\StudentStat::where('semester', $semester)
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->selectRaw('kecamatan, SUM(total_siswa) as siswa')
            ->groupBy('kecamatan');
        if ($kecamatan !== '') {
            $siswaPerKec->where('kecamatan', $kecamatan);
        }

        $guruPerKec = (clone $teacherQuery)
            ->selectRaw('kecamatan, SUM(jumlah_guru) as guru')
            ->groupBy('kecamatan')
            ->get();

        $rasioKecamatan = $siswaPerKec->get()
            ->map(function ($row) use ($guruPerKec) {
                $guru = $guruPerKec->firstWhere('kecamatan', $row->kecamatan);
                $guruTotal = $guru ? (int) $guru->guru : 0;

                return [
                    'kecamatan' => $row->kecamatan,
                    'siswa' => (int) $row->siswa,
                    'guru' => $guruTotal,
                    'rasio' => $guruTotal > 0 ? round($row->siswa / $guruTotal, 1) : 0,
                ];
            })
            ->sortByDesc('rasio')
            ->values();

        // Sertifikasi per status kepegawaian (dari tabel detail guru)
        $detailQuery = Teacher::query();
        if ($kecamatan !== '') {
            $detailQuery->where('kecamatan', $kecamatan);
        }
        if ($jenjang !== '') {
            $detailQuery->where('jenjang', $jenjang);
        }
        $sertifikasi = (clone $detailQuery)
            ->selectRaw('status_kepegawaian,
                SUM(CASE WHEN sertifikasi = 1 THEN 1 ELSE 0 END) as sudah,
                SUM(CASE WHEN sertifikasi = 1 THEN 0 ELSE 1 END) as belum')
            ->groupBy('status_kepegawaian')
            ->get();

        // Gender guru
        $gender = (clone $detailQuery)
            ->selectRaw('jenis_kelamin, COUNT(*) as total')
            ->groupBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin')
            ->all();

        // Bidang studi top 10
        $bidangStudi = (clone $detailQuery)
            ->where('bidang_studi', '!=', '')
            ->selectRaw('bidang_studi, COUNT(*) as total')
            ->groupBy('bidang_studi')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'bidang_studi')
            ->all();

        // KPI ringkasan
        $kpi = (clone $teacherQuery)->selectRaw('
            COALESCE(SUM(jumlah_guru), 0) as total,
            COALESCE(SUM(jumlah_sertifikasi), 0) as sertifikasi')->first();
        $totalGuru = (int) $kpi->total;

        return view('dashboard.guru', [
            'filters' => $filters,
            'semesters' => Filters::semesters(),
            'kecamatanList' => Filters::kecamatanList(),
            'jenjangList' => Filters::jenjangList(),
            'komposisiJenjang' => $komposisiJenjang,
            'trendStatus' => $trendStatus,
            'rasioKecamatan' => $rasioKecamatan,
            'sertifikasi' => $sertifikasi,
            'gender' => $gender,
            'bidangStudi' => $bidangStudi,
            'totalGuru' => $totalGuru,
            'totalSertifikasi' => (int) $kpi->sertifikasi,
        ]);
    }
}
