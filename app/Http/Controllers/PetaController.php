<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StudentStat;
use App\Models\TeacherStat;
use App\Support\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PetaController extends Controller
{
    public function index(Request $request): View
    {
        $filters = Filters::fromRequest($request);
        extract($filters, EXTR_SKIP);

        // Sekolah sebagai titik (peta sebaran)
        $schoolQuery = School::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '!=', 0)
            ->where('longitude', '!=', 0);

        if ($kecamatan !== '') {
            $schoolQuery->where('kecamatan', $kecamatan);
        }
        if ($jenjang !== '') {
            $schoolQuery->where('jenjang', $jenjang);
        }

        $sekolah = (clone $schoolQuery)
            ->select('npsn', 'nama_sekolah', 'jenjang', 'status', 'akreditasi', 'kecamatan', 'latitude', 'longitude')
            ->orderBy('nama_sekolah')
            ->get();

        // Bobot heatmap: jumlah siswa per sekolah pada semester terpilih
        $siswaPerSekolah = StudentStat::where('semester', $semester)
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->selectRaw('npsn, SUM(total_siswa) as total_siswa')
            ->groupBy('npsn')
            ->pluck('total_siswa', 'npsn');

        $sekolah = $sekolah->map(function ($s) use ($siswaPerSekolah) {
            $row = $s->toArray();
            $row['siswa'] = (int) ($siswaPerSekolah[$s->npsn] ?? 0);

            return $row;
        });

        // Statistik per kecamatan untuk choropleth
        $siswaKec = StudentStat::where('semester', $semester)
            ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan))
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->selectRaw('kecamatan, COUNT(DISTINCT npsn) as sekolah, SUM(total_siswa) as siswa, SUM(jumlah_rombel) as rombel')
            ->groupBy('kecamatan')
            ->get()
            ->keyBy('kecamatan');

        $guruKec = TeacherStat::where('semester', $semester)
            ->when($kecamatan !== '', fn ($q) => $q->where('kecamatan', $kecamatan))
            ->when($jenjang !== '', fn ($q) => $q->where('jenjang', $jenjang))
            ->selectRaw('kecamatan, SUM(jumlah_guru) as guru')
            ->groupBy('kecamatan')
            ->pluck('guru', 'kecamatan');

        $kecamatanStats = [];
        foreach ($siswaKec as $nama => $row) {
            $guru = (int) ($guruKec[$nama] ?? 0);
            $kecamatanStats[] = [
                'kecamatan' => $nama,
                'sekolah' => (int) $row->sekolah,
                'siswa' => (int) $row->siswa,
                'guru' => $guru,
                'rombel' => (int) $row->rombel,
                'rasio' => $guru > 0 ? round($row->siswa / $guru, 1) : 0,
            ];
        }

        // GeoJSON batas kecamatan
        $geoPath = storage_path('app/data/peta_kecamatan.geojson');
        $geojson = File::exists($geoPath)
            ? File::get($geoPath)
            : 'null';

        return view('dashboard.peta', [
            'filters' => $filters,
            'semesters' => Filters::semesters(),
            'kecamatanList' => Filters::kecamatanList(),
            'jenjangList' => Filters::jenjangList(),
            'sekolah' => $sekolah,
            'kecamatanStats' => $kecamatanStats,
            'geojson' => $geojson,
        ]);
    }
}
