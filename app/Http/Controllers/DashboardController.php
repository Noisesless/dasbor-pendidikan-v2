<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StudentStat;
use App\Models\TeacherStat;
use App\Support\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Filters::fromRequest($request);
        extract($filters, EXTR_SKIP);

        $studentQuery = StudentStat::where('semester', $semester);
        $teacherQuery = TeacherStat::where('semester', $semester);
        $schoolQuery = School::query();

        if ($kecamatan !== '') {
            $studentQuery->where('kecamatan', $kecamatan);
            $teacherQuery->where('kecamatan', $kecamatan);
            $schoolQuery->where('kecamatan', $kecamatan);
        }
        if ($jenjang !== '') {
            $studentQuery->where('jenjang', $jenjang);
            $teacherQuery->where('jenjang', $jenjang);
            $schoolQuery->where('jenjang', $jenjang);
        }

        // KPI utama
        $kpiSiswa = (clone $studentQuery)->selectRaw('
            COALESCE(SUM(total_siswa), 0) as total_siswa,
            COALESCE(SUM(total_laki), 0) as total_laki,
            COALESCE(SUM(total_perempuan), 0) as total_perempuan,
            COALESCE(SUM(jumlah_rombel), 0) as total_rombel
        ')->first();

        $kpiGuru = (clone $teacherQuery)->selectRaw('
            COALESCE(SUM(jumlah_guru), 0) as total_guru,
            COALESCE(SUM(jumlah_sertifikasi), 0) as total_sertifikasi
        ')->first();

        $totalSekolah = (clone $schoolQuery)->count();
        $totalSiswa = (int) $kpiSiswa->total_siswa;
        $totalGuru = (int) $kpiGuru->total_guru;
        $totalRombel = (int) $kpiSiswa->total_rombel;
        $rasioSiswaGuru = $totalGuru > 0 ? round($totalSiswa / $totalGuru, 1) : 0;

        // Distribusi akreditasi
        $akreditasi = (clone $schoolQuery)
            ->selectRaw('COALESCE(NULLIF(akreditasi, ""), "Belum Terakreditasi") as akreditasi, COUNT(*) as total')
            ->groupBy('akreditasi')
            ->pluck('total', 'akreditasi')
            ->all();

        // Rincian per jenjang
        $perJenjang = (clone $studentQuery)
            ->selectRaw('jenjang, COUNT(DISTINCT npsn) as jumlah_sekolah,
                SUM(total_siswa) as total_siswa,
                SUM(jumlah_rombel) as total_rombel')
            ->groupBy('jenjang')
            ->get()
            ->keyBy('jenjang');

        $guruPerJenjang = (clone $teacherQuery)
            ->selectRaw('jenjang, SUM(jumlah_guru) as total_guru')
            ->groupBy('jenjang')
            ->pluck('total_guru', 'jenjang');

        $jenjangList = ['PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA', 'PKBM'];
        $perJenjangData = [];
        foreach ($jenjangList as $j) {
            $row = $perJenjang->get($j);
            $perJenjangData[] = [
                'jenjang' => $j,
                'sekolah' => (int) ($row->jumlah_sekolah ?? 0),
                'siswa' => (int) ($row->total_siswa ?? 0),
                'guru' => (int) ($guruPerJenjang->get($j, 0)),
                'rombel' => (int) ($row->total_rombel ?? 0),
            ];
        }

        // Tren 5 semester (siswa & guru)
        $trendSiswa = StudentStat::selectRaw('semester, SUM(total_siswa) as total')
            ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan))
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->groupBy('semester')
            ->orderBy('semester')
            ->pluck('total', 'semester');

        $trendGuru = TeacherStat::selectRaw('semester, SUM(jumlah_guru) as total')
            ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan))
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->groupBy('semester')
            ->orderBy('semester')
            ->pluck('total', 'semester');

        $semesters = $trendSiswa->keys()->merge($trendGuru->keys())->unique()->sort()->values();
        $trend = [];
        foreach ($semesters as $s) {
            $trend[] = [
                'semester' => $s,
                'label' => Filters::semesterLabel($s),
                'siswa' => (int) ($trendSiswa[$s] ?? 0),
                'guru' => (int) ($trendGuru[$s] ?? 0),
            ];
        }

        // Top kecamatan berdasarkan jumlah siswa
        $kecamatanSiswa = (clone $studentQuery)
            ->selectRaw('kecamatan, SUM(total_siswa) as total')
            ->groupBy('kecamatan')
            ->orderByDesc('total')
            ->get();

        return view('dashboard.index', [
            'filters' => $filters,
            'semesters' => Filters::semesters(),
            'kecamatanList' => Filters::kecamatanList(),
            'jenjangList' => Filters::jenjangList(),
            'totalSekolah' => $totalSekolah,
            'totalSiswa' => $totalSiswa,
            'totalGuru' => $totalGuru,
            'totalRombel' => $totalRombel,
            'rasioSiswaGuru' => $rasioSiswaGuru,
            'akreditasi' => $akreditasi,
            'perJenjangData' => $perJenjangData,
            'trend' => $trend,
            'kecamatanSiswa' => $kecamatanSiswa,
            'totalLaki' => (int) $kpiSiswa->total_laki,
            'totalPerempuan' => (int) $kpiSiswa->total_perempuan,
            'totalSertifikasi' => (int) $kpiGuru->total_sertifikasi,
        ]);
    }
}
