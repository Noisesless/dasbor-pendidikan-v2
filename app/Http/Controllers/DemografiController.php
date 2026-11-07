<?php

namespace App\Http\Controllers;

use App\Models\StudentStat;
use App\Models\TeacherStat;
use App\Support\Filters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemografiController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Filters::fromRequest($request);
        extract($filters, EXTR_SKIP);

        // Agregasi siswa per kelompok jenjang
        $studentQuery = StudentStat::where('semester', $semester);
        $popQuery = function () use ($kecamatan) {
            return \App\Models\Population::where('tahun', 2026)
                ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan));
        };

        $pop = $popQuery()->selectRaw('
            COALESCE(SUM(u04_t), 0) as u04,
            COALESCE(SUM(u56_t), 0) as u56,
            COALESCE(SUM(u712_t), 0) as u712,
            COALESCE(SUM(u1315_t), 0) as u1315,
            COALESCE(SUM(u1618_t), 0) as u1618,
            COALESCE(SUM(u04_l), 0) as u04_l, COALESCE(SUM(u04_p), 0) as u04_p,
            COALESCE(SUM(u56_l), 0) as u56_l, COALESCE(SUM(u56_p), 0) as u56_p,
            COALESCE(SUM(u712_l), 0) as u712_l, COALESCE(SUM(u712_p), 0) as u712_p,
            COALESCE(SUM(u1315_l), 0) as u1315_l, COALESCE(SUM(u1315_p), 0) as u1315_p,
            COALESCE(SUM(u1618_l), 0) as u1618_l, COALESCE(SUM(u1618_p), 0) as u1618_p
        ')->first();

        $byGroup = function (array $jenjang) use ($studentQuery) {
            return (clone $studentQuery)
                ->whereIn('jenjang', $jenjang)
                ->selectRaw('
                    COALESCE(SUM(total_siswa), 0) as siswa,
                    COALESCE(SUM(usia_sesuai), 0) as sesuai,
                    COALESCE(SUM(total_laki), 0) as laki,
                    COALESCE(SUM(total_perempuan), 0) as perempuan')
                ->first();
        };

        $grup = [
            'PAUD/TK' => ['PAUD', 'TK'],
            'SD/MI' => ['SD', 'MI'],
            'SMP/MTS' => ['SMP', 'MTS'],
            'SMA/SMK/MA' => ['SMA', 'SMK', 'MA'],
        ];

        $pendudukByGrup = [
            'PAUD/TK' => (int) $pop->u04 + (int) $pop->u56,
            'SD/MI' => (int) $pop->u712,
            'SMP/MTS' => (int) $pop->u1315,
            'SMA/SMK/MA' => (int) $pop->u1618,
        ];

        $apkApm = [];
        foreach ($grup as $label => $jenjangList) {
            $row = $byGroup($jenjangList);
            $penduduk = $pendudukByGrup[$label];
            $apkApm[] = [
                'grup' => $label,
                'siswa' => (int) $row->siswa,
                'penduduk' => $penduduk,
                'apk' => $penduduk > 0 ? round($row->siswa * 100 / $penduduk, 2) : 0,
                'apm' => $row->siswa > 0 ? round($row->sesuai * 100 / $row->siswa, 2) : 0,
                'ipg' => $row->laki > 0 ? round($row->perempuan / $row->laki, 3) : 0,
            ];
        }

        // Tren APK 5 semester (semua kecamatan, sederhana per grup jenjang)
        $semestersAll = StudentStat::distinct()->orderBy('semester')->pluck('semester');
        $apkTrend = [];
        foreach ($semestersAll as $sem) {
            $siswaSd = StudentStat::where('semester', $sem)->whereIn('jenjang', ['SD', 'MI']);
            $siswaSmp = StudentStat::where('semester', $sem)->whereIn('jenjang', ['SMP', 'MTS']);
            $siswaSma = StudentStat::where('semester', $sem)->whereIn('jenjang', ['SMA', 'SMK', 'MA']);
            if ($kecamatan !== '') {
                $siswaSd->where('kecamatan', $kecamatan);
                $siswaSmp->where('kecamatan', $kecamatan);
                $siswaSma->where('kecamatan', $kecamatan);
            }
            $sd = (clone $siswaSd)->sum('total_siswa');
            $smp = (clone $siswaSmp)->sum('total_siswa');
            $sma = (clone $siswaSma)->sum('total_siswa');
            $apkTrend[] = [
                'label' => Filters::semesterLabel($sem),
                'sd' => (int) $sd,
                'smp' => (int) $smp,
                'sma' => (int) $sma,
            ];
        }

        // Piramida penduduk usia sekolah
        $piramida = [
            ['label' => '0-4 th', 'laki' => (int) $pop->u04_l, 'perempuan' => (int) $pop->u04_p],
            ['label' => '5-6 th', 'laki' => (int) $pop->u56_l, 'perempuan' => (int) $pop->u56_p],
            ['label' => '7-12 th', 'laki' => (int) $pop->u712_l, 'perempuan' => (int) $pop->u712_p],
            ['label' => '13-15 th', 'laki' => (int) $pop->u1315_l, 'perempuan' => (int) $pop->u1315_p],
            ['label' => '16-18 th', 'laki' => (int) $pop->u1618_l, 'perempuan' => (int) $pop->u1618_p],
        ];

        // Siswa per kecamatan (heatmap bar)
        $siswaKecamatan = (clone $studentQuery)
            ->selectRaw("kecamatan, SUM(total_siswa) as siswa,
                SUM(CASE WHEN jenjang IN ('SD','MI') THEN total_siswa ELSE 0 END) as sd,
                SUM(CASE WHEN jenjang IN ('SMP','MTS') THEN total_siswa ELSE 0 END) as smp,
                SUM(CASE WHEN jenjang IN ('SMA','SMK','MA') THEN total_siswa ELSE 0 END) as sma")
            ->groupBy('kecamatan')
            ->orderBy('kecamatan')
            ->get();

        // Guru per kecamatan (untuk pemerataan akses layanan)
        $guruKecamatan = TeacherStat::where('semester', $semester)
            ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan))
            ->selectRaw('kecamatan, SUM(jumlah_guru) as guru')
            ->groupBy('kecamatan')
            ->orderBy('kecamatan')
            ->get();

        return view('dashboard.demografi', [
            'filters' => $filters,
            'semesters' => Filters::semesters(),
            'kecamatanList' => Filters::kecamatanList(),
            'jenjangList' => Filters::jenjangList(),
            'apkApm' => collect($apkApm),
            'apkTrend' => $apkTrend,
            'piramida' => $piramida,
            'siswaKecamatan' => $siswaKecamatan,
            'guruKecamatan' => $guruKecamatan,
        ]);
    }
}
