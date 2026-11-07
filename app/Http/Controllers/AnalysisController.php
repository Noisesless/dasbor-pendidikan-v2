<?php

namespace App\Http\Controllers;

use App\Services\AnalitikIndikator;
use App\Services\DisarpusParser;
use App\Services\DindikporaParser;
use App\Services\KemenagParser;
use App\Services\WalidataPendidikan;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AnalysisController extends Controller
{
    // =====================================================================
    // Kesenjangan Guru
    // =====================================================================
    public function gap(Request $request)
    {
        $parser = new DindikporaParser();
        $data = $parser->fetchAll();

        $kecamatanList = collect($data['sd'] ?? [])
            ->pluck('kecamatan')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $selected = trim((string) $request->query('kecamatan', ''));
        $labels = ['tk' => 'TK', 'sd' => 'SD', 'smp' => 'SMP', 'sma' => 'SMA', 'smk' => 'SMK'];

        $levelData = [];
        $tSekolah = $tGuru = $tMurid = $tIdeal = 0;

        foreach (DindikporaParser::LEVELS as $level) {
            $rows = collect($data[$level] ?? []);
            if ($selected !== '') {
                $rows = $rows->where('kecamatan', $selected);
            }

            $sekolah = $rows->sum('sekolah_negeri') + $rows->sum('sekolah_swasta');
            $guru = $rows->sum('guru_negeri') + $rows->sum('guru_swasta');
            $murid = $rows->sum('murid_negeri') + $rows->sum('murid_swasta');
            $ideal = $this->idealGuru($murid, $level);

            $levelData[] = [
                'level' => $labels[$level] ?? strtoupper($level),
                'sekolah' => $sekolah,
                'guru' => $guru,
                'murid' => $murid,
                'ideal' => $ideal,
                'gap' => max(0, $ideal - $guru),
                'over' => max(0, $guru - $ideal),
                'rasio' => $guru > 0 ? round($murid / $guru, 1) : 0,
            ];

            $tSekolah += $sekolah;
            $tGuru += $guru;
            $tMurid += $murid;
            $tIdeal += $ideal;
        }

        $summary = [
            'sekolah' => $tSekolah,
            'guru' => $tGuru,
            'murid' => $tMurid,
            'ideal' => $tIdeal,
            'gap' => max(0, $tIdeal - $tGuru),
            'over' => max(0, $tGuru - $tIdeal),
            'rasio' => $tGuru > 0 ? round($tMurid / $tGuru, 1) : 0,
        ];

        // Kesenjangan per kecamatan (akumulasi seluruh jenjang)
        $kecamatanData = [];
        foreach ($kecamatanList as $kec) {
            $sekolah = $guru = $murid = $ideal = 0;

            foreach (DindikporaParser::LEVELS as $level) {
                $row = collect($data[$level] ?? [])->firstWhere('kecamatan', $kec);
                if (! $row) {
                    continue;
                }
                $sekolah += $row['sekolah_negeri'] + $row['sekolah_swasta'];
                $guru += $row['guru_negeri'] + $row['guru_swasta'];
                $m = $row['murid_negeri'] + $row['murid_swasta'];
                $murid += $m;
                $ideal += $this->idealGuru($m, $level);
            }

            $kecamatanData[] = [
                'kecamatan' => $kec,
                'sekolah' => $sekolah,
                'guru' => $guru,
                'murid' => $murid,
                'ideal' => $ideal,
                'gap' => max(0, $ideal - $guru),
                'over' => max(0, $guru - $ideal),
                'rasio' => $guru > 0 ? round($murid / $guru, 1) : 0,
            ];
        }

        return view('dashboard.gap', [
            'title' => 'Kesenjangan Guru',
            'levelData' => $levelData,
            'summary' => $summary,
            'kecamatanList' => $kecamatanList,
            'kecamatanData' => $kecamatanData,
            'selectedKecamatan' => $selected,
        ]);
    }

    // =====================================================================
    // Angka Keluar (rasio murid-guru per jenjang)
    // =====================================================================
    public function dropout()
    {
        $parser = new DindikporaParser();
        $data = $parser->fetchAll();

        $byLevel = [];
        $totalMurid = $totalGuru = $totalSekolah = 0;

        foreach (DindikporaParser::LEVELS as $level) {
            $rows = collect($data[$level] ?? []);
            $sekolah = $rows->sum('sekolah_negeri') + $rows->sum('sekolah_swasta');
            $guru = $rows->sum('guru_negeri') + $rows->sum('guru_swasta');
            $murid = $rows->sum('murid_negeri') + $rows->sum('murid_swasta');

            $byLevel[] = [
                'level' => strtoupper($level),
                'sekolah' => $sekolah,
                'guru' => $guru,
                'murid' => $murid,
                'rasio' => $guru > 0 ? round($murid / $guru, 1) : 0,
                'ideal' => $level === 'tk' ? 15 : 20,
            ];

            $totalSekolah += $sekolah;
            $totalGuru += $guru;
            $totalMurid += $murid;
        }

        $summary = [
            'murid' => $totalMurid,
            'guru' => $totalGuru,
            'sekolah' => $totalSekolah,
            'rasio' => $totalGuru > 0 ? round($totalMurid / $totalGuru, 1) : 0,
        ];

        return view('dashboard.dropout', [
            'title' => 'Angka Keluar',
            'byLevel' => $byLevel,
            'summary' => $summary,
        ]);
    }

    // =====================================================================
    // Literasi & Perpustakaan
    // =====================================================================
    public function literacy(Request $request)
    {
        $parser = new DisarpusParser();
        $visitors = collect($parser->fetchVisitors());
        $members = collect($parser->fetchNewMembers());
        $collections = collect($parser->fetchCollections());

        $availableYears = $visitors->pluck('tahun')
            ->merge($members->pluck('tahun'))
            ->unique()
            ->values()
            ->all();
        rsort($availableYears, SORT_NUMERIC);

        $selectedYear = (string) $request->query('tahun', $availableYears[0] ?? '');

        $monthlyVisitors = [];
        foreach (DisarpusParser::MONTH_ORDER as $m) {
            $row = $visitors->first(fn ($r) => $r['bulan'] === $m && (string) $r['tahun'] === $selectedYear);
            $monthlyVisitors[] = [
                'bulan' => $m,
                'total' => (int) ($row['total'] ?? 0),
                'pelajar' => (int) ($row['pelajar'] ?? 0),
                'mahasiswa' => (int) ($row['mahasiswa'] ?? 0),
                'karyawan' => (int) ($row['karyawan'] ?? 0),
                'umum' => (int) ($row['umum'] ?? 0),
            ];
        }

        $monthlyMembers = [];
        foreach (DisarpusParser::MONTH_ORDER as $m) {
            $row = $members->first(fn ($r) => $r['bulan'] === $m && (string) $r['tahun'] === $selectedYear);
            $monthlyMembers[] = ['bulan' => $m, 'anggota' => (int) ($row['total'] ?? 0)];
        }

        // Agregasi koleksi per kategori untuk tahun terpilih
        $categoryTotals = [];
        $yearCollections = $collections->filter(fn ($r) => (string) $r['tahun'] === $selectedYear);
        foreach ($yearCollections as $row) {
            foreach (DisarpusParser::COLLECTION_CATEGORIES as $cat) {
                $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + (int) ($row['values'][$cat] ?? 0);
            }
        }
        arsort($categoryTotals);
        $categoryData = $categoryTotals;

        $totalVisitors = array_sum(array_column($monthlyVisitors, 'total'));
        $totalMembers = array_sum(array_column($monthlyMembers, 'anggota'));
        $totalCollections = array_sum($categoryTotals);

        $maxVisitorMonth = null;
        foreach ($monthlyVisitors as $mv) {
            if ($maxVisitorMonth === null || $mv['total'] > $maxVisitorMonth['total']) {
                $maxVisitorMonth = $mv;
            }
        }

        return view('dashboard.literacy', [
            'title' => 'Literasi & Perpustakaan',
            'availableYears' => $availableYears,
            'selectedYear' => $selectedYear,
            'monthlyVisitors' => $monthlyVisitors,
            'monthlyMembers' => $monthlyMembers,
            'categoryData' => $categoryData,
            'totalVisitors' => $totalVisitors,
            'totalMembers' => $totalMembers,
            'totalCollections' => $totalCollections,
            'maxVisitorMonth' => $maxVisitorMonth,
        ]);
    }

    // =====================================================================
    // Pendidikan Madrasah
    // =====================================================================
    public function madrasah(Request $request)
    {
        $parser = new KemenagParser();
        $rows = collect($parser->fetchMadrasahAll());

        $levelOrder = ['RA', 'MI', 'MTS', 'MA'];
        $years = $rows->pluck('tahun_ajaran')->unique()->sortDesc()->values()->all();
        $selectedYear = (string) $request->query('tahun_ajaran', $years[0] ?? '');
        $selectedLevel = strtoupper((string) $request->query('level', 'ALL'));

        $filtered = $rows->filter(fn ($r) => (string) $r['tahun_ajaran'] === $selectedYear);
        if ($selectedLevel !== 'ALL') {
            $filtered = $filtered->where('level', $selectedLevel);
        }

        $stats = [
            'sekolah' => $filtered->sum('sekolah_negeri') + $filtered->sum('sekolah_swasta'),
            'negeri' => $filtered->sum('sekolah_negeri'),
            'swasta' => $filtered->sum('sekolah_swasta'),
            'guru' => $filtered->sum('guru_negeri') + $filtered->sum('guru_swasta'),
            'murid' => $filtered->sum('murid_negeri') + $filtered->sum('murid_swasta'),
        ];

        // Rincian per jenjang untuk tahun terpilih
        $levelBreakdown = [];
        foreach ($levelOrder as $lv) {
            $sub = $rows->filter(fn ($r) => $r['level'] === $lv && (string) $r['tahun_ajaran'] === $selectedYear);
            $levelBreakdown[] = [
                'level' => $lv,
                'sekolah' => $sub->sum('sekolah_negeri') + $sub->sum('sekolah_swasta'),
                'guru' => $sub->sum('guru_negeri') + $sub->sum('guru_swasta'),
                'murid' => $sub->sum('murid_negeri') + $sub->sum('murid_swasta'),
            ];
        }

        // Tren murid per tahun ajaran (semua jenjang)
        $yearsAsc = $years;
        rsort($yearsAsc);
        $yearsAsc = array_reverse($yearsAsc);
        $trendData = [];
        foreach ($yearsAsc as $y) {
            $sub = $rows->filter(fn ($r) => (string) $r['tahun_ajaran'] === $y);
            $trendData[] = [
                'tahun' => $y,
                'murid' => $sub->sum('murid_negeri') + $sub->sum('murid_swasta'),
            ];
        }

        // Sebaran murid per kecamatan (tahun terpilih, sesuai filter level)
        $kecamatanData = [];
        foreach ($filtered->groupBy('kecamatan') as $kec => $sub) {
            $kecamatanData[] = [
                'kecamatan' => $kec,
                'sekolah' => $sub->sum('sekolah_negeri') + $sub->sum('sekolah_swasta'),
                'guru' => $sub->sum('guru_negeri') + $sub->sum('guru_swasta'),
                'murid' => $sub->sum('murid_negeri') + $sub->sum('murid_swasta'),
            ];
        }
        usort($kecamatanData, fn ($a, $b) => $b['murid'] <=> $a['murid']);

        // Data sosial: pernikahan & perceraian (tahun terbaru)
        $nikah = collect($parser->fetchNikahCerai());
        $nikahYear = $nikah->max('tahun');
        $nikahRows = $nikah->where('tahun', $nikahYear);
        $nikahStats = [
            'tahun' => $nikahYear,
            'nikah' => $nikahRows->sum('nikah'),
            'talak' => $nikahRows->sum('talak'),
            'cerai' => $nikahRows->sum('cerai'),
            'rujuk' => $nikahRows->sum('rujuk'),
        ];
        $nikahStats['rasio'] = $nikahStats['nikah'] > 0
            ? round($nikahStats['cerai'] / $nikahStats['nikah'] * 100, 1)
            : 0;

        // Data sosial: tempat ibadah (tahun terbaru)
        $ibadah = collect($parser->fetchTempatIbadah());
        $ibadahYear = $ibadah->max('tahun');
        $ibadahRows = $ibadah->where('tahun', $ibadahYear);
        $ibadahStats = [
            'tahun' => $ibadahYear,
            'masjid' => $ibadahRows->sum('masjid'),
            'mushola' => $ibadahRows->sum('mushola'),
            'gereja' => $ibadahRows->sum('gereja'),
            'pura' => $ibadahRows->sum('pura'),
            'vihara' => $ibadahRows->sum('vihara'),
        ];
        $ibadahStats['total'] = array_sum(array_map('intval', array_diff_key($ibadahStats, ['tahun' => 0])));

        return view('dashboard.madrasah', [
            'title' => 'Pendidikan Madrasah',
            'levelOrder' => $levelOrder,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedLevel' => $selectedLevel,
            'stats' => $stats,
            'levelBreakdown' => $levelBreakdown,
            'trendData' => $trendData,
            'kecamatanData' => $kecamatanData,
            'nikahStats' => $nikahStats,
            'ibadahStats' => $ibadahStats,
        ]);
    }

    // =====================================================================
    // Indikator APK / APM / APS
    // =====================================================================
    public function indicator()
    {
        $a = new AnalitikIndikator();
        $ringkasan = $a->ringkasan();
        $rekap = $a->rekapKecamatan();

        $indikatorData = [];
        foreach ($rekap as $r) {
            $indikatorData[] = [
                'name' => ucwords(strtolower($r['kecamatan'])),
                'apk' => (float) $r['apk'],
                'apm' => (float) $r['apm'],
                'aps' => (float) $r['aps'],
            ];
        }

        // Tren siswa per jenjang
        $trenRaw = $a->trenSiswa();
        ksort($trenRaw);
        $trenYears = array_keys($trenRaw);
        $trenSeries = [];
        foreach ($trenRaw as $perJenjang) {
            foreach ($perJenjang as $jenjang => $val) {
                $trenSeries[$jenjang][] = (int) $val;
            }
        }

        // Penduduk per usia
        $pendudukPerUsia = $a->pendudukPerUsia();

        // Sekolah & siswa per jenjang (urut besar → kecil)
        $sekolahPerJenjang = $a->sekolahPerJenjang();
        arsort($sekolahPerJenjang);
        $siswaPerJenjang = $a->siswaPerJenjang();
        arsort($siswaPerJenjang);

        $guruPerStatus = $a->guruPerStatus();
        $prestasi = $a->prestasi();

        // Sorotan APK
        $apkValues = array_column($indikatorData, 'apk');
        $bestIdx = null;
        $worstIdx = null;
        foreach ($indikatorData as $i => $d) {
            if ($bestIdx === null || $d['apk'] > $indikatorData[$bestIdx]['apk']) {
                $bestIdx = $i;
            }
            if ($worstIdx === null || $d['apk'] < $indikatorData[$worstIdx]['apk']) {
                $worstIdx = $i;
            }
        }
        $sorotan = [
            'tertinggi' => $bestIdx !== null ? $indikatorData[$bestIdx] : null,
            'terendah' => $worstIdx !== null ? $indikatorData[$worstIdx] : null,
            'rataRata' => count($apkValues) > 0 ? round(array_sum($apkValues) / count($apkValues), 1) : 0,
        ];

        return view('dashboard.indikator', [
            'title' => 'Indikator APK/APM/APS',
            'ringkasan' => $ringkasan,
            'rekap' => $rekap,
            'indikatorData' => $indikatorData,
            'trenYears' => $trenYears,
            'trenSeries' => $trenSeries,
            'pendudukPerUsia' => $pendudukPerUsia,
            'sekolahPerJenjang' => $sekolahPerJenjang,
            'siswaPerJenjang' => $siswaPerJenjang,
            'guruPerStatus' => $guruPerStatus,
            'prestasi' => $prestasi,
            'sorotan' => $sorotan,
        ]);
    }

    // =====================================================================
    // Akreditasi PAUD
    // =====================================================================
    public function paud()
    {
        $a = new AnalitikIndikator();
        $ak = $a->akreditasiPaud();

        $komposisi = array_values(array_filter([
            ['name' => 'Akreditasi A', 'value' => (int) ($ak['total_a'] ?? 0), 'color' => '#111827'],
            ['name' => 'Akreditasi B', 'value' => (int) ($ak['total_b'] ?? 0), 'color' => '#4b5563'],
            ['name' => 'Akreditasi C', 'value' => (int) ($ak['total_c'] ?? 0), 'color' => '#9ca3af'],
            ['name' => 'Belum Terakreditasi', 'value' => (int) ($ak['total_belum'] ?? 0), 'color' => '#cc0000'],
        ], fn ($k) => $k['value'] > 0));

        return view('dashboard.paud', [
            'title' => 'Akreditasi PAUD',
            'ak' => $ak,
            'komposisi' => $komposisi,
        ]);
    }

    // =====================================================================
    // Data Walidata
    // =====================================================================
    public function walidata(Request $request)
    {
        $w = new WalidataPendidikan();
        $all = $w->all();
        $years = WalidataPendidikan::YEARS;

        $categories = [
            'Pendidikan Dasar' => '#2563eb',
            'Pendidikan Menengah' => '#10b981',
            'Pendidikan Non-Formal' => '#f59e0b',
            'Umum' => '#94a3b8',
        ];

        // Klasifikasi kategori per variabel
        $rowsByCategory = [];
        foreach ($all as $row) {
            $cat = $this->walidataCategory($row['uraian'] ?? '');
            $rowsByCategory[$cat][] = $row;
        }

        // Tren total nilai per tahun
        $flatTrend = [];
        foreach ($years as $y) {
            $sum = 0;
            foreach ($all as $row) {
                $v = $row[$y] ?? null;
                if ($v !== null && $v > 0) {
                    $sum += $v;
                }
            }
            $flatTrend[] = ['year' => $y, 'value' => round($sum, 2)];
        }

        // Statistik per kategori: jumlah variabel + nilai terbaru + delta YoY
        $categoryStats = [];
        foreach ($categories as $catName => $color) {
            $rowsIn = $rowsByCategory[$catName] ?? [];
            $latest = null;
            $prev = null;
            $yearsDesc = $years;
            rsort($yearsDesc);
            foreach ($yearsDesc as $y) {
                $sum = 0;
                foreach ($rowsIn as $row) {
                    $v = $row[$y] ?? null;
                    if ($v !== null && $v > 0) {
                        $sum += $v;
                    }
                }
                if ($sum > 0) {
                    if ($latest === null) {
                        $latest = ['year' => $y, 'value' => round($sum, 2)];
                    } elseif ($prev === null) {
                        $prev = ['year' => $y, 'value' => round($sum, 2)];
                        break;
                    }
                }
            }
            $categoryStats[] = [
                'name' => $catName,
                'color' => $color,
                'count' => count($rowsIn),
                'latest' => $latest,
                'prev' => $prev,
            ];
        }

        // Distribusi kategori untuk pie
        $pieData = [];
        foreach ($categories as $catName => $color) {
            $count = count($rowsByCategory[$catName] ?? []);
            if ($count > 0) {
                $pieData[] = ['name' => $catName, 'value' => $count, 'color' => $color];
            }
        }

        // Tren indikator kunci
        $indicatorKeywords = [
            'Sekolah' => 'sekolah',
            'Siswa' => 'siswa',
            'Guru' => 'guru',
            'Ruang Kelas' => 'ruang kelas',
            'Putus Sekolah' => 'putus sekolah',
            'Rombongan Belajar' => 'rombongan',
            'Rasio' => 'rasio',
        ];
        $indicatorTrends = [];
        foreach ($indicatorKeywords as $label => $kw) {
            $series = [];
            foreach ($years as $y) {
                $sum = 0;
                foreach ($all as $row) {
                    if (str_contains(strtolower((string) $row['uraian']), $kw)) {
                        $v = $row[$y] ?? null;
                        if ($v !== null && $v > 0) {
                            $sum += $v;
                        }
                    }
                }
                $series[] = round($sum, 2);
            }
            $indicatorTrends[] = ['label' => $label, 'data' => $series];
        }

        // Total data points positif
        $totalDataPoints = 0;
        foreach ($all as $row) {
            foreach ($years as $y) {
                $v = $row[$y] ?? null;
                if ($v !== null && $v > 0) {
                    $totalDataPoints++;
                }
            }
        }

        // Tabel variabel (paginasi + pencarian)
        $q = trim((string) $request->query('q', ''));
        $filtered = collect($all);
        if ($q !== '') {
            $ql = strtolower($q);
            $filtered = $filtered->filter(function ($r) use ($ql) {
                return str_contains(strtolower((string) $r['kode']), $ql)
                    || str_contains(strtolower((string) $r['uraian']), $ql);
            });
        }

        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $recentYears = array_slice($years, -6);
        $tableRows = collect($paginator->items())->map(function ($r) use ($recentYears) {
            $recent = [];
            $max = 0;
            foreach ($recentYears as $y) {
                $v = $r[$y] ?? null;
                $recent[$y] = $v;
                if ($v !== null && $v > $max) {
                    $max = $v;
                }
            }

            return $r + ['recent' => $recent, 'recent_max' => $max];
        })->all();

        return view('dashboard.walidata', [
            'title' => 'Data Walidata',
            'totalVariabel' => count($all),
            'totalDataPoints' => $totalDataPoints,
            'years' => $years,
            'recentYears' => $recentYears,
            'flatTrend' => $flatTrend,
            'categoryStats' => $categoryStats,
            'pieData' => $pieData,
            'indicatorTrends' => $indicatorTrends,
            'tableRows' => $tableRows,
            'paginator' => $paginator,
            'q' => $q,
        ]);
    }

    // =====================================================================
    // Analisis Kebijakan (indeks prioritas + tren historis + konteks BPS)
    // =====================================================================
    public function kebijakan(Request $request)
    {
        $semester = trim((string) $request->query('semester', '20252'));
        $availableSemesters = DB::table('student_stats')->select('semester')->distinct()->orderByDesc('semester')->pluck('semester')->all();
        if ($availableSemesters !== [] && !in_array($semester, $availableSemesters, true)) {
            $semester = $availableSemesters[0];
        }

        // ---------- 1. Indikator per kecamatan (data Dapodik) ----------
        $kecamatanList = DB::table('schools')->select('kecamatan')->distinct()->orderBy('kecamatan')->pluck('kecamatan')->all();

        $siswa = DB::table('student_stats')->where('semester', $semester)
            ->selectRaw('kecamatan, SUM(total_siswa) siswa, SUM(usia_sesuai) sesuai, SUM(jumlah_rombel) rombel')
            ->groupBy('kecamatan')->get()->keyBy('kecamatan');

        $guru = DB::table('teacher_stats')->where('semester', $semester)
            ->selectRaw('kecamatan, SUM(jumlah_guru) guru, SUM(jumlah_sertifikasi) sert')
            ->groupBy('kecamatan')->get()->keyBy('kecamatan');

        $akreditasi = DB::table('schools')
            ->whereIn('jenjang', ['SD', 'SMP', 'SMA', 'SMK', 'MI', 'MTS', 'MA'])
            ->selectRaw("kecamatan, COUNT(*) total, SUM(CASE WHEN akreditasi IS NULL OR akreditasi = '' OR akreditasi IN ('C','Belum') THEN 1 ELSE 0 END) rendah")
            ->groupBy('kecamatan')->get()->keyBy('kecamatan');

        $sekolahKecil = DB::table('schools')
            ->leftJoin('student_stats', function ($join) use ($semester) {
                $join->on('student_stats.npsn', '=', 'schools.npsn')->where('student_stats.semester', '=', $semester);
            })
            ->whereIn('schools.jenjang', ['SD', 'SMP'])
            ->selectRaw('schools.kecamatan,
                COUNT(*) total,
                SUM(CASE WHEN student_stats.total_siswa IS NULL THEN 1 ELSE 0 END) tanpa_data,
                SUM(CASE WHEN student_stats.total_siswa < 60 THEN 1 ELSE 0 END) kecil,
                SUM(CASE WHEN student_stats.jumlah_rombel > 0 AND student_stats.total_siswa / student_stats.jumlah_rombel > 32 THEN 1 ELSE 0 END) padat')
            ->groupBy('schools.kecamatan')->get()->keyBy('kecamatan');

        $rows = [];
        foreach ($kecamatanList as $kec) {
            $s = $siswa[$kec] ?? null;
            $g = $guru[$kec] ?? null;
            $a = $akreditasi[$kec] ?? null;
            $sk = $sekolahKecil[$kec] ?? null;

            $totalSiswa = (int) ($s->siswa ?? 0);
            $totalGuru = (int) ($g->guru ?? 0);

            $rows[$kec] = [
                'kecamatan' => $kec,
                'siswa' => $totalSiswa,
                'guru' => $totalGuru,
                'rombel' => (int) ($s->rombel ?? 0),
                'rasio' => $totalGuru > 0 ? round($totalSiswa / $totalGuru, 1) : 0,
                'overage' => $totalSiswa > 0 ? round(($totalSiswa - (int) ($s->sesuai ?? 0)) / $totalSiswa * 100, 1) : 0,
                'belum_sertifikasi' => $totalGuru > 0 ? round((1 - (int) ($g->sert ?? 0) / $totalGuru) * 100, 1) : 0,
                'akreditasi_rendah' => (int) ($a->total ?? 0) > 0 ? round((int) $a->rendah / (int) $a->total * 100, 1) : 0,
                'sekolah_kecil' => (int) ($sk->total ?? 0) > 0 ? round((int) $sk->kecil / (int) $sk->total * 100, 1) : 0,
                'rombel_padat' => (int) ($sk->total ?? 0) > 0 ? round((int) $sk->padat / (int) $sk->total * 100, 1) : 0,
                'tanpa_data' => (int) ($sk->tanpa_data ?? 0),
            ];
        }

        // Bobot komposit (total 100). Nilai tinggi = prioritas tinggi.
        $weights = [
            'rasio' => 20,
            'overage' => 20,
            'belum_sertifikasi' => 15,
            'akreditasi_rendah' => 20,
            'rombel_padat' => 15,
            'sekolah_kecil' => 10,
        ];

        $ranges = [];
        foreach (array_keys($weights) as $key) {
            $values = array_column($rows, $key);
            $ranges[$key] = [min($values), max($values)];
        }

        foreach ($rows as &$row) {
            $score = 0;
            $contrib = [];
            foreach ($weights as $key => $weight) {
                [$min, $max] = $ranges[$key];
                $norm = $max > $min ? ($row[$key] - $min) / ($max - $min) : 0;
                $contrib[$key] = round($norm * $weight, 1);
                $score += $norm * $weight;
            }
            arsort($contrib);
            $row['skor'] = round($score, 1);
            $row['kontribusi'] = $contrib;
            $row['faktor_utama'] = array_key_first($contrib);
        }
        unset($row);

        uasort($rows, fn ($a, $b) => $b['skor'] <=> $a['skor']);
        $rank = 1;
        foreach ($rows as &$row) {
            $row['peringkat'] = $rank++;
        }
        unset($row);

        $faktorLabel = [
            'rasio' => 'Rasio siswa/guru tinggi',
            'overage' => 'Banyak siswa di luar usia',
            'belum_sertifikasi' => 'Guru belum sertifikasi',
            'akreditasi_rendah' => 'Akreditasi C/belum',
            'rombel_padat' => 'Rombel overload',
            'sekolah_kecil' => 'Banyak sekolah kecil',
        ];

        $priorityRows = array_values($rows);

        // ---------- 2. Overage per semester (tren) ----------
        $overageTrend = DB::table('student_stats')
            ->selectRaw('semester, SUM(total_siswa) siswa, SUM(usia_sesuai) sesuai')
            ->whereIn('jenjang', ['SD', 'SMP', 'MI', 'MTS', 'SMA', 'SMK', 'MA'])
            ->groupBy('semester')->orderBy('semester')->get()
            ->map(fn ($r) => [
                'semester' => $r->semester,
                'overage' => $r->siswa > 0 ? round(($r->siswa - $r->sesuai) / $r->siswa * 100, 1) : 0,
            ])->all();

        // ---------- 3. Tren historis Dindikpora + Kemenag ----------
        $histRaw = DB::table('regional_education_stats')
            ->selectRaw('jenjang, tahun_ajaran, COUNT(DISTINCT kecamatan) kec, SUM(murid_negeri+murid_swasta) murid, SUM(guru_negeri+guru_swasta) guru, SUM(sekolah_negeri+sekolah_swasta) sekolah')
            ->groupBy('jenjang', 'tahun_ajaran')->orderBy('tahun_ajaran')->get();

        $kecTotal = DB::table('regional_education_stats')->distinct()->count('kecamatan');

        $histYears = $histRaw->pluck('tahun_ajaran')->unique()->sort()->values()->all();
        $histByJenjang = [];
        $histComplete = [];
        foreach ($histRaw as $r) {
            // Tahun yang tidak mencakup seluruh kecamatan dianggap tidak lengkap
            // (mis. berkas SD 2019/2020 hanya memuat 18 dari 20 kecamatan).
            $lengkap = (int) $r->kec >= $kecTotal;
            $histComplete[$r->jenjang][$r->tahun_ajaran] = $lengkap;
            $histByJenjang[$r->jenjang][$r->tahun_ajaran] = [
                'murid' => $lengkap ? (int) $r->murid : null,
                'guru' => (int) $r->guru,
                'sekolah' => (int) $r->sekolah,
            ];
        }

        $histDatasets = [];
        foreach ($histByJenjang as $jenjang => $series) {
            $data = [];
            foreach ($histYears as $y) {
                $data[] = $series[$y]['murid'] ?? null;
            }
            $histDatasets[$jenjang] = $data;
        }

        // Perubahan murid per kecamatan (tahun lengkap pertama vs terakhir)
        $changeByKecamatan = [];
        foreach (['SD', 'SMP'] as $jenjang) {
            $data = DB::table('regional_education_stats')->where('jenjang', $jenjang)->get();
            $yearsPerKec = [];
            foreach ($data as $r) {
                $yearsPerKec[$r->kecamatan][$r->tahun_ajaran] = (int) $r->murid_negeri + (int) $r->murid_swasta;
            }

            $lengkap = array_keys(array_filter($histComplete[$jenjang] ?? [], fn ($v) => $v));
            sort($lengkap);
            $jenjangYears = array_values(array_intersect($lengkap, $histYears));
            $first = $jenjangYears[0] ?? null;
            $last = $jenjangYears ? $jenjangYears[count($jenjangYears) - 1] : null;

            foreach ($yearsPerKec as $kec => $series) {
                $awal = $series[$first] ?? 0;
                $akhir = $series[$last] ?? 0;
                $changeByKecamatan[$jenjang][$kec] = [
                    'awal' => $awal,
                    'akhir' => $akhir,
                    'delta' => $awal > 0 ? round(($akhir - $awal) / $awal * 100, 1) : 0,
                ];
            }
            $changeByKecamatan[$jenjang]['__years'] = [$first, $last];
        }

        // ---------- 4. Indikator makro BPS ----------
        $bpsRaw = DB::table('regional_indicators')->where('wilayah', 'Banjarnegara')->orderBy('tahun')->get();
        $bpsYears = $bpsRaw->pluck('tahun')->unique()->sort()->values()->all();
        $bpsSeries = [];
        foreach ($bpsRaw as $r) {
            $bpsSeries[$r->indikator][$r->tahun] = (float) $r->nilai;
        }
        $bpsMeta = [
            'hls' => ['label' => 'Harapan Lama Sekolah', 'satuan' => 'tahun', 'warna' => '#2563eb'],
            'rls' => ['label' => 'Rata-rata Lama Sekolah', 'satuan' => 'tahun', 'warna' => '#7c3aed'],
            'ipm' => ['label' => 'IPM', 'satuan' => 'indeks', 'warna' => '#0891b2'],
            'miskin_pct' => ['label' => 'Penduduk Miskin', 'satuan' => '%', 'warna' => '#ef4444'],
            'gini' => ['label' => 'Indeks Gini', 'satuan' => 'indeks', 'warna' => '#f59e0b'],
            'pengeluaran' => ['label' => 'Pengeluaran per Kapita', 'satuan' => 'Rp', 'warna' => '#16a34a'],
            'uhh' => ['label' => 'Usia Harapan Hidup', 'satuan' => 'tahun', 'warna' => '#db2777'],
        ];

        $bpsCards = [];
        foreach (['hls', 'rls', 'ipm', 'miskin_pct'] as $key) {
            $series = $bpsSeries[$key] ?? [];
            if ($series === []) {
                continue;
            }
            $years = array_keys($series);
            $latest = $series[max($years)];
            $prev = count($years) > 1 ? $series[$years[count($years) - 2]] : null;
            $bpsCards[] = [
                'key' => $key,
                'label' => $bpsMeta[$key]['label'],
                'satuan' => $bpsMeta[$key]['satuan'],
                'nilai' => $latest,
                'tahun' => max($years),
                'delta' => $prev !== null ? round($latest - $prev, 2) : null,
                'warna' => $bpsMeta[$key]['warna'],
            ];
        }

        // ---------- 5. Cakupan data ----------
        $coverage = [
            'sekolah_tanpa_siswa' => DB::table('schools')->whereNotIn('npsn',
                DB::table('student_stats')->where('semester', $semester)->select('npsn')
            )->count(),
            'guru_tanpa_nip' => DB::table('teachers')->where(fn ($q) => $q->whereNull('nip')->orWhere('nip', ''))->count(),
            'total_sekolah' => DB::table('schools')->count(),
            'total_ptk' => DB::table('teachers')->count(),
        ];

        return view('dashboard.kebijakan', [
            'semester' => $semester,
            'availableSemesters' => $availableSemesters,
            'priorityRows' => $priorityRows,
            'faktorLabel' => $faktorLabel,
            'overageTrend' => $overageTrend,
            'histYears' => $histYears,
            'histDatasets' => $histDatasets,
            'changeByKecamatan' => $changeByKecamatan,
            'bpsYears' => $bpsYears,
            'bpsSeries' => $bpsSeries,
            'bpsMeta' => $bpsMeta,
            'bpsCards' => $bpsCards,
            'coverage' => $coverage,
            'weights' => $weights,
        ]);
    }

    // =====================================================================
    // Helpers
    // =====================================================================
    private function idealGuru(int $murid, string $level): int
    {
        return (int) ceil($murid / ($level === 'tk' ? 15 : 20));
    }

    private function walidataCategory(string $uraian): string
    {
        $u = strtolower($uraian);
        if (str_contains($u, 'paud') || str_contains($u, 'sekolah dasar') || str_contains($u, 'dasar')) {
            return 'Pendidikan Dasar';
        }
        if (str_contains($u, 'menengah') || str_contains($u, 'sma') || str_contains($u, 'smp') || str_contains($u, 'smk')) {
            return 'Pendidikan Menengah';
        }
        if (str_contains($u, 'nonformal') || str_contains($u, 'non-formal')) {
            return 'Pendidikan Non-Formal';
        }

        return 'Umum';
    }
}
