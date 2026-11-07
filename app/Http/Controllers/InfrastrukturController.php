<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StudentStat;
use App\Support\Filters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfrastrukturController extends Controller
{
    /**
     * Standar maksimal siswa per rombel menurut Permendikbud 22/2016.
     */
    private const ROMBEL_MAX = [
        'PAUD' => 20,
        'TK' => 20,
        'SD' => 32,
        'MI' => 32,
        'SMP' => 36,
        'MTS' => 36,
        'SMA' => 36,
        'SMK' => 36,
        'MA' => 36,
    ];

    public function index(Request $request): View
    {
        $filters = Filters::fromRequest($request);
        extract($filters, EXTR_SKIP);

        $studentQuery = StudentStat::where('semester', $semester);
        $schoolQuery = School::query();

        if ($kecamatan !== '') {
            $studentQuery->where('kecamatan', $kecamatan);
            $schoolQuery->where('kecamatan', $kecamatan);
        }
        if ($jenjang !== '') {
            $studentQuery->where('jenjang', $jenjang);
            $schoolQuery->where('jenjang', $jenjang);
        }

        // Analisis kepadatan rombel per jenjang vs Permendikbud 22/2016
        $rombelRows = (clone $studentQuery)
            ->selectRaw('jenjang,
                COUNT(DISTINCT npsn) as jumlah_sekolah,
                SUM(total_siswa) as total_siswa,
                SUM(jumlah_rombel) as total_rombel')
            ->groupBy('jenjang')
            ->get();

        $rombelPerJenjang = [];
        foreach ($rombelRows as $row) {
            $standar = self::ROMBEL_MAX[$row->jenjang] ?? 0;
            $rataRata = $row->total_rombel > 0
                ? round($row->total_siswa / $row->total_rombel, 1)
                : 0;

            $rombelPerJenjang[] = [
                'jenjang' => $row->jenjang,
                'sekolah' => (int) $row->jumlah_sekolah,
                'siswa' => (int) $row->total_siswa,
                'rombel' => (int) $row->total_rombel,
                'rata_rata' => $rataRata,
                'standar' => $standar,
                'status' => $standar > 0
                    ? ($rataRata > $standar ? 'melebihi' : ('sesuai'))
                    : '-',
            ];
        }

        // Distribusi sekolah berdasarkan kepatuhan rombel (per sekolah)
        $sekolahRombel = (clone $studentQuery)
            ->selectRaw('jenjang, total_siswa, jumlah_rombel')
            ->get();

        $patuh = ['sesuai' => 0, 'melebihi' => 0, 'belum_rukun' => 0];
        foreach ($sekolahRombel as $sr) {
            if ($sr->jumlah_rombel <= 0) {
                continue;
            }
            $ukuran = $sr->total_siswa / $sr->jumlah_rombel;
            $standar = self::ROMBEL_MAX[$sr->jenjang] ?? 0;

            if ($standar === 0 || $ukuran <= $standar) {
                $patuh['sesuai']++;
            } else {
                $patuh['melebihi']++;
            }
        }

        // Transisi antar jenjang (funnel)
        $jenjangFunnel = ['PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK'];
        $funnelRows = (clone $studentQuery)
            ->whereIn('jenjang', $jenjangFunnel)
            ->selectRaw('jenjang, SUM(total_siswa) as total')
            ->groupBy('jenjang')
            ->pluck('total', 'jenjang');
        $funnel = [];
        foreach ($jenjangFunnel as $j) {
            $funnel[] = [
                'jenjang' => $j,
                'siswa' => (int) ($funnelRows[$j] ?? 0),
            ];
        }

        // Akreditasi
        $akreditasi = (clone $schoolQuery)
            ->selectRaw('COALESCE(NULLIF(akreditasi, ""), "Belum Terakreditasi") as akreditasi, COUNT(*) as total')
            ->groupBy('akreditasi')
            ->pluck('total', 'akreditasi')
            ->all();

        // Status sekolah per jenjang
        $statusPerJenjang = (clone $schoolQuery)
            ->selectRaw('jenjang, status, COUNT(*) as total')
            ->groupBy('jenjang', 'status')
            ->get();

        $jenjangUrut = ['PAUD', 'TK', 'SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA', 'PKBM'];
        $statusData = [];
        foreach ($statusPerJenjang as $row) {
            $statusData[$row->jenjang][$row->status] = (int) $row->total;
        }
        $statusChart = [];
        foreach ($jenjangUrut as $j) {
            if (isset($statusData[$j])) {
                $statusChart[] = [
                    'jenjang' => $j,
                    'negeri' => $statusData[$j]['Negeri'] ?? 0,
                    'swasta' => $statusData[$j]['Swasta'] ?? 0,
                ];
            }
        }

        return view('dashboard.infrastruktur', [
            'filters' => $filters,
            'semesters' => Filters::semesters(),
            'kecamatanList' => Filters::kecamatanList(),
            'jenjangList' => Filters::jenjangList(),
            'rombelPerJenjang' => $rombelPerJenjang,
            'patuh' => $patuh,
            'funnel' => $funnel,
            'akreditasi' => $akreditasi,
            'statusChart' => $statusChart,
        ]);
    }
}
