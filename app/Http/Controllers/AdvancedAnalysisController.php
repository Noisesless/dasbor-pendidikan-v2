<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Analisis lanjutan: kualitas data, sarana & anggaran, kandidat regrouping.
 * Semua dihitung dari data yang sudah ada (Dapodik + Walidata) tanpa asumsi
 * di luar parameter yang ditampilkan ke pengguna.
 */
class AdvancedAnalysisController extends Controller
{
    protected const BASELINE_SEMESTER = '20252';

    // =====================================================================
    // 1. Kualitas & Kepatuhan Data
    // =====================================================================
    public function dataHealth(Request $request)
    {
        $semester = trim((string) $request->query('semester', self::BASELINE_SEMESTER));
        $semesters = DB::table('student_stats')->select('semester')->distinct()->orderByDesc('semester')->pluck('semester')->all();
        if ($semesters !== [] && !in_array($semester, $semesters, true)) {
            $semester = $semesters[0];
        }

        $schools = DB::table('schools')->orderBy('nama_sekolah')->get();
        $siswa = DB::table('student_stats')->where('semester', $semester)->get()->keyBy('npsn');
        $guru = DB::table('teacher_stats')->where('semester', $semester)->get()->keyBy('npsn');

        $ptkPerSekolah = DB::table('teachers')
            ->selectRaw("npsn, COUNT(*) total, SUM(CASE WHEN nip IS NULL OR nip = '' THEN 1 ELSE 0 END) tanpa_nip, SUM(CASE WHEN nuptk IS NULL OR nuptk = '' THEN 1 ELSE 0 END) tanpa_nuptk")
            ->groupBy('npsn')->get()->keyBy('npsn');

        $rows = [];
        $madrasahTanpaGuru = 0;
        foreach ($schools as $s) {
            $st = $siswa[$s->npsn] ?? null;
            $gt = $guru[$s->npsn] ?? null;
            $ptk = $ptkPerSekolah[$s->npsn] ?? null;

            // Data guru madrasah tidak tersedia dalam feed (kewenangan Kemenag),
            // sehingga tidak dihitung sebagai pelanggaran kualitas data sekolah.
            $isMadrasah = in_array($s->jenjang, ['MI', 'MTS', 'MA'], true);
            if ($isMadrasah && (!$gt || (int) $gt->jumlah_guru === 0)) {
                $madrasahTanpaGuru++;
            }

            $issues = [];
            if (!$st || (int) $st->total_siswa === 0) {
                $issues[] = 'tanpa_data_siswa';
            } elseif ((int) $st->total_siswa <= 5) {
                $issues[] = 'siswa_anomali';
            }
            if (!$isMadrasah && (!$gt || (int) $gt->jumlah_guru === 0)) {
                $issues[] = 'tanpa_data_guru';
            }
            if ($ptk && (int) $ptk->tanpa_nip === (int) $ptk->total && (int) $ptk->total > 0) {
                $issues[] = 'ptk_tanpa_nip';
            }
            if ($s->akreditasi === null || in_array($s->akreditasi, ['', 'Belum'], true)) {
                $issues[] = 'akreditasi_kosong';
            }
            if (!$s->latitude || !$s->longitude) {
                $issues[] = 'tanpa_koordinat';
            }
            if ($this->jenjangTidakSesuai($s->jenjang, (string) $s->nama_sekolah)) {
                $issues[] = 'jenjang_tidak_sesuai';
            }
            if (!$s->telepon) {
                $issues[] = 'tanpa_telepon';
            }
            if ($st && (int) $st->jumlah_rombel === 0 && (int) $st->total_siswa > 0) {
                $issues[] = 'rombel_kosong';
            }

            $bobot = [
                'tanpa_data_siswa' => 30, 'tanpa_data_guru' => 25, 'siswa_anomali' => 15,
                'jenjang_tidak_sesuai' => 15, 'ptk_tanpa_nip' => 10, 'rombel_kosong' => 10,
                'akreditasi_kosong' => 5, 'tanpa_koordinat' => 5, 'tanpa_telepon' => 3,
            ];
            $penalti = 0;
            foreach ($issues as $i) {
                $penalti += $bobot[$i] ?? 0;
            }

            $rows[] = [
                'npsn' => $s->npsn,
                'nama_sekolah' => $s->nama_sekolah,
                'jenjang' => $s->jenjang,
                'kecamatan' => $s->kecamatan,
                'siswa' => (int) ($st->total_siswa ?? 0),
                'guru' => (int) ($gt->jumlah_guru ?? 0),
                'skor' => max(0, 100 - $penalti),
                'masalah' => $issues,
            ];
        }

        usort($rows, fn ($a, $b) => $a['skor'] <=> $b['skor'] ?: strcmp($a['nama_sekolah'], $b['nama_sekolah']));

        $problemCount = [];
        foreach ($rows as $r) {
            foreach ($r['masalah'] as $m) {
                $problemCount[$m] = ($problemCount[$m] ?? 0) + 1;
            }
        }
        // Urutkan menurut bobot keparahan, bukan jumlah (telepon kosong bukan prioritas utama)
        $urgensi = [
            'tanpa_data_siswa' => 100, 'tanpa_data_guru' => 90, 'jenjang_tidak_sesuai' => 75,
            'siswa_anomali' => 70, 'ptk_tanpa_nip' => 60, 'rombel_kosong' => 50,
            'akreditasi_kosong' => 40, 'tanpa_koordinat' => 30, 'tanpa_telepon' => 10,
        ];
        uksort($problemCount, fn ($a, $b) => ($urgensi[$b] ?? 0) <=> ($urgensi[$a] ?? 0));

        // Ringkasan per kecamatan
        $perKecamatan = [];
        foreach ($rows as $r) {
            $k = $r['kecamatan'];
            $perKecamatan[$k] ??= ['kecamatan' => $k, 'sekolah' => 0, 'total_skor' => 0, 'bermasalah' => 0];
            $perKecamatan[$k]['sekolah']++;
            $perKecamatan[$k]['total_skor'] += $r['skor'];
            if ($r['masalah'] !== []) {
                $perKecamatan[$k]['bermasalah']++;
            }
        }
        foreach ($perKecamatan as &$k) {
            $k['rata_skor'] = round($k['total_skor'] / max(1, $k['sekolah']), 1);
        }
        unset($k);
        uasort($perKecamatan, fn ($a, $b) => $a['rata_skor'] <=> $b['rata_skor']);

        // Per jenjang
        $perJenjang = [];
        foreach ($rows as $r) {
            $j = $r['jenjang'];
            $perJenjang[$j] ??= ['jenjang' => $j, 'sekolah' => 0, 'total_skor' => 0, 'bermasalah' => 0];
            $perJenjang[$j]['sekolah']++;
            $perJenjang[$j]['total_skor'] += $r['skor'];
            if ($r['masalah'] !== []) {
                $perJenjang[$j]['bermasalah']++;
            }
        }
        foreach ($perJenjang as &$j) {
            $j['rata_skor'] = round($j['total_skor'] / max(1, $j['sekolah']), 1);
        }
        unset($j);
        uasort($perJenjang, fn ($a, $b) => $a['rata_skor'] <=> $b['rata_skor']);

        // Rekonsiliasi antar sumber
        $rekon = [];
        $dapodikJenjang = DB::table('student_stats')->where('semester', $semester)
            ->selectRaw('jenjang, SUM(total_siswa) s')->groupBy('jenjang')->pluck('s', 'jenjang')->all();

        $histLast = DB::table('regional_education_stats')
            ->selectRaw('jenjang, tahun_ajaran, SUM(murid_negeri+murid_swasta) m')
            ->where('tahun_ajaran', '2023/2024')->groupBy('jenjang', 'tahun_ajaran')->pluck('m', 'jenjang')->all();

        foreach (['SD', 'SMP', 'TK'] as $j) {
            $dapo = (int) ($dapodikJenjang[$j] ?? 0);
            $dindik = (int) ($histLast[$j] ?? 0);
            if ($dapo > 0 && $dindik > 0) {
                $rekon[] = [
                    'jenjang' => $j,
                    'dapodik' => $dapo,
                    'pembanding' => $dindik,
                    'sumber' => 'Dindikpora 2023/2024',
                    'selisih' => round(($dapo - $dindik) / $dindik * 100, 1),
                ];
            }
        }

        $kemenagMi = DB::table('regional_education_stats')->where('jenjang', 'MI')
            ->selectRaw('SUM(murid_negeri+murid_swasta) m')->value('m');
        if ($kemenagMi && isset($dapodikJenjang['MI'])) {
            $rekon[] = [
                'jenjang' => 'MI',
                'dapodik' => (int) $dapodikJenjang['MI'],
                'pembanding' => (int) $kemenagMi,
                'sumber' => 'Kemenag 2018/2019',
                'selisih' => round(($dapodikJenjang['MI'] - $kemenagMi) / $kemenagMi * 100, 1),
            ];
        }

        $labelMasalah = [
            'tanpa_data_siswa' => 'Tanpa data siswa',
            'tanpa_data_guru' => 'Tanpa data guru',
            'siswa_anomali' => 'Jumlah siswa tidak wajar (≤5)',
            'jenjang_tidak_sesuai' => 'Nama/jenjang tidak sesuai',
            'ptk_tanpa_nip' => 'Seluruh PTK tanpa NIP',
            'rombel_kosong' => 'Rombel 0',
            'akreditasi_kosong' => 'Akreditasi belum ada',
            'tanpa_koordinat' => 'Tanpa koordinat',
            'tanpa_telepon' => 'Tanpa telepon',
        ];

        $kecamatanList = array_values(array_unique(array_column($rows, 'kecamatan')));
        sort($kecamatanList);

        return view('dashboard.data-health', [
            'semester' => $semester,
            'semesters' => $semesters,
            'rows' => array_slice($rows, 0, 100),
            'totalRows' => count($rows),
            'problemCount' => $problemCount,
            'labelMasalah' => $labelMasalah,
            'perKecamatan' => array_values($perKecamatan),
            'perJenjang' => array_values($perJenjang),
            'rekon' => $rekon,
            'sehat' => count(array_filter($rows, fn ($r) => $r['skor'] === 100)),
            'rataSkor' => round(array_sum(array_column($rows, 'skor')) / max(1, count($rows)), 1),
            'bermasalah' => count(array_filter($rows, fn ($r) => $r['masalah'] !== [])),
            'madrasahTanpaGuru' => $madrasahTanpaGuru,
            'kecamatanList' => $kecamatanList,
        ]);
    }

    // =====================================================================
    // 2. Sarana: backlog ruang kelas & estimasi anggaran
    // =====================================================================
    public function sarana(Request $request)
    {
        $unit = [
            'ringan' => (float) $request->query('ringan', 150),
            'sedang' => (float) $request->query('sedang', 250),
            'berat' => (float) $request->query('berat', 400),
            'baru' => (float) $request->query('baru', 500),
        ];
        $targetRombel = max(20, (float) $request->query('rasio', 28));

        $hasil = $this->saranaComputation($unit, $targetRombel);

        return view('dashboard.sarana', array_merge($hasil, [
            'unit' => $unit,
            'targetRombel' => $targetRombel,
        ]));
    }

    /**
     * Perhitungan backlog ruang kelas & estimasi biaya.
     * Dipakai bersama oleh halaman Sarana dan halaman Rekomendasi.
     *
     * @param  array{ringan: float, sedang: float, berat: float, baru: float}  $unit
     * @return array<string, mixed>
     */
    protected function saranaComputation(array $unit, float $targetRombel): array
    {        $walidata = $this->walidataRows();

        $defs = [
            'SD' => [
                'nama' => 'SD',
                'total' => '/^Jumlah ruang kelas pada Sekolah Dasar$/i',
                'baik' => '/kondisi baik pada Sekolah Dasar$/i',
                'ringan' => '/kondisi rusak ringan pada Sekolah Dasar$/i',
                'sedang' => '/kondisi rusak sedang pada Sekolah Dasar$/i',
                'berat' => '/kondisi rusak berat pada Sekolah Dasar$/i',
            ],
            'SMP' => [
                'nama' => 'SMP',
                'total' => '/^Jumlah ruang kelas pada Sekolah Menengah Pertama$/i',
                'baik' => '/kondisi baik pada Sekolah Menengah Pertama$/i',
                'ringan' => '/kondisi rusak ri[g]{1,2}an pada Sekolah Menengah Pertama$/i',
                'sedang' => '/kondisi rusak sedang pada Sekolah Menengah Pertama$/i',
                'berat' => '/kondisi rusak berat pada Sekolah Menengah Pertama$/i',
            ],
            'SMA' => [
                'nama' => 'SMA',
                'total' => '/ruang kelas pada Sekolah Menengah Atas (baik Negeri maupun Swasta)?$/i',
                'baik' => '/kondisi baik pada Sekolah Menengah Atas( baik Negeri maupun Swasta)?$/i',
                'ringan' => '/kondisi rusak ringan pada Sekolah Menengah Atas( baik Negeri maupun Swasta)?$/i',
                'sedang' => '/kondisi rusak sedang pada Sekolah Menengah Atas( baik Negeri maupun Swasta)?$/i',
                'berat' => '/kondisi rusak berat pada Sekolah Menengah Atas( baik Negeri maupun Swasta)?$/i',
            ],
            'SMK' => [
                'nama' => 'SMK',
                'total' => '/ruang kelas pada sekolah menengah kejuruan$/i',
                'baik' => '/kondisi baik pada sekolah menengah kejuruan$/i',
                'ringan' => '/rusak ringan pada sekolah menengah kejuruan$/i',
                'sedang' => '/rusak sedang pada sekolah menengah kejuruan$/i',
                'berat' => '/rusak berat pada sekolah menengah kejuruan$/i',
            ],
        ];

        $sarana = [];
        foreach ($defs as $jenjang => $def) {
            $item = ['jenjang' => $def['nama']];
            foreach (['total', 'baik', 'ringan', 'sedang', 'berat'] as $key) {
                $item[$key] = $this->walidataValue($walidata, $def[$key]);
            }
            $sarana[$jenjang] = $item;
        }

        // Siswa & rombel Dapodik sebagai pembanding kapasitas
        $dapo = DB::table('student_stats')->where('semester', self::BASELINE_SEMESTER)
            ->whereIn('jenjang', ['SD', 'SMP', 'SMA', 'SMK'])
            ->selectRaw('jenjang, SUM(total_siswa) siswa, SUM(jumlah_rombel) rombel')
            ->groupBy('jenjang')->get()->keyBy('jenjang');

        $biaya = ['ringan' => 0.0, 'sedang' => 0.0, 'berat' => 0.0, 'baru' => 0.0];
        foreach ($sarana as $jenjang => &$row) {
            $row['siswa'] = (int) ($dapo[$jenjang]->siswa ?? 0);
            $row['rombel'] = (int) ($dapo[$jenjang]->rombel ?? 0);
            $row['kebutuhan_ruang'] = $row['siswa'] > 0 ? (int) ceil($row['siswa'] / $targetRombel) : 0;
            $row['defisit'] = max(0, $row['kebutuhan_ruang'] - (int) $row['total']);
            $row['rusak_sedang_berat'] = (int) $row['sedang'] + (int) $row['berat'];
            $row['persen_rusak'] = (int) $row['total'] > 0
                ? round(((int) $row['ringan'] + (int) $row['sedang'] + (int) $row['berat']) / (int) $row['total'] * 100, 1)
                : 0;

            foreach (['ringan', 'sedang', 'berat', 'baru'] as $key) {
                $qty = $key === 'baru' ? $row['defisit'] : (int) $row[$key];
                $row['biaya_' . $key] = $qty * $unit[$key];
                $biaya[$key] += $row['biaya_' . $key];
            }
            $row['total_biaya'] = array_sum([$row['biaya_ringan'], $row['biaya_sedang'], $row['biaya_berat'], $row['biaya_baru']]);
        }
        unset($row);

        // Tren indikator agregat 2020-2024
        $trenDefs = [
            'tersedia' => '/^jumlah ruang kelas yang tersedia$/i',
            'layak' => '/^jumlah ruang kelas yang layak$/i',
            'rusak' => '/^jumlah ruang kelas yang rusak sedang\/berat$/i',
            'perlu_rehab' => '/^jumlah ruang kelas sekolah yang perlu direhabilitasi$/i',
        ];
        $trendLabels = $this->walidataYears();
        $tren = [];
        foreach ($trenDefs as $key => $pattern) {
            $series = [];
            foreach ($trendLabels as $y) {
                $series[] = $this->walidataValueByYear($walidata, $pattern, $y);
            }
            $tren[$key] = $series;
        }

        $totalBiaya = array_sum($biaya);

        return [
            'sarana' => array_values($sarana),
            'biaya' => $biaya,
            'totalBiaya' => $totalBiaya,
            'trendLabels' => $trendLabels,
            'tren' => $tren,
            'semester' => self::BASELINE_SEMESTER,
            'targetRombel' => $targetRombel,
        ];
    }

    // =====================================================================
    // 3. Kandidat regrouping sekolah
    // =====================================================================
    public function regrouping(Request $request)
    {
        $radius = (float) $request->query('radius', 1.0);
        $radius = min(5.0, max(0.3, $radius));
        $jenjang = in_array($request->query('jenjang'), ['SD', 'SMP'], true) ? $request->query('jenjang') : 'SD';

        return view('dashboard.regrouping', $this->regroupingComputation($jenjang, $radius));
    }

    /**
     * Pencarian pasangan sekolah berdekatan.
     * Dipakai bersama oleh halaman Regrouping dan halaman Rekomendasi.
     *
     * @return array<string, mixed>
     */
    protected function regroupingComputation(string $jenjang, float $radius): array
    {
        $schools = DB::table('schools')->where('jenjang', $jenjang)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->where('latitude', '!=', 0)->where('longitude', '!=', 0)
            // Satuan yang jelas bukan SD/SMP (salah klasifikasi jenjang di sumber) dikeluarkan
            ->where(function ($q) {
                foreach (['universitas', 'institut', 'akademi', 'politeknik', 'sekolah tinggi'] as $kata) {
                    $q->where('nama_sekolah', 'not like', '%' . $kata . '%');
                }
            })
            ->get(['npsn', 'nama_sekolah', 'jenjang', 'kecamatan', 'status', 'latitude', 'longitude']);

        $siswa = DB::table('student_stats')->where('semester', self::BASELINE_SEMESTER)
            ->get(['npsn', 'total_siswa', 'jumlah_rombel'])->keyBy('npsn');
        $guru = DB::table('teacher_stats')->where('semester', self::BASELINE_SEMESTER)
            ->get(['npsn', 'jumlah_guru'])->keyBy('npsn');

        // Bucket grid ~0.01 derajat (≈1,1 km) untuk menghindari perbandingan O(n²)
        $cell = 0.01;
        $grid = [];
        foreach ($schools as $s) {
            $gx = (int) floor($s->longitude / $cell);
            $gy = (int) floor($s->latitude / $cell);
            $grid[$gx . ':' . $gy][] = $s;
        }

        $pairs = [];
        $latKm = 111.0;
        $lngKm = 111.0 * cos(deg2rad(-7.4));

        foreach ($grid as $key => $list) {
            [$gx, $gy] = array_map('intval', explode(':', $key));
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    $other = $grid[($gx + $dx) . ':' . ($gy + $dy)] ?? [];
                    foreach ($list as $a) {
                        foreach ($other as $b) {
                            if ($a->npsn >= $b->npsn) {
                                continue;
                            }
                            $d = sqrt(
                                (($a->latitude - $b->latitude) * $latKm) ** 2
                                + (($a->longitude - $b->longitude) * $lngKm) ** 2
                            );
                            if ($d > $radius) {
                                continue;
                            }

                            $sa = (int) ($siswa[$a->npsn]->total_siswa ?? 0);
                            $sb = (int) ($siswa[$b->npsn]->total_siswa ?? 0);
                            $ra = (int) ($siswa[$a->npsn]->jumlah_rombel ?? 0);
                            $rb = (int) ($siswa[$b->npsn]->jumlah_rombel ?? 0);
                            $ga = (int) ($guru[$a->npsn]->jumlah_guru ?? 0);
                            $gb = (int) ($guru[$b->npsn]->jumlah_guru ?? 0);
                            // Sekolah tanpa data siswa bukan kandidat (tidak ada yang digabung)
                            if ($sa === 0 || $sb === 0) {
                                continue;
                            }

                            // Minimal rombel mengikuti jumlah tingkat: SD 6, SMP 3
                            $minRombel = $jenjang === 'SD' ? 6 : 3;
                            $rombelGabung = max($minRombel, (int) ceil(($sa + $sb) / 28));
                            $guruGabung = (int) ceil(($sa + $sb) / 24);

                            $pairs[] = [
                                'jarak' => round($d, 2),
                                'a' => [
                                    'npsn' => $a->npsn, 'nama' => $a->nama_sekolah,
                                    'kecamatan' => $a->kecamatan, 'status' => $a->status,
                                    'siswa' => $sa, 'rombel' => $ra, 'guru' => $ga,
                                ],
                                'b' => [
                                    'npsn' => $b->npsn, 'nama' => $b->nama_sekolah,
                                    'kecamatan' => $b->kecamatan, 'status' => $b->status,
                                    'siswa' => $sb, 'rombel' => $rb, 'guru' => $gb,
                                ],
                                'siswa_gabung' => $sa + $sb,
                                'hemat_rombel' => max(0, $ra + $rb - $rombelGabung),
                                'hemat_guru' => max(0, $ga + $gb - $guruGabung),
                                'satu_kecamatan' => $a->kecamatan === $b->kecamatan,
                            ];
                        }
                    }
                }
            }
        }

        // Prioritas: pasangan kecil yang berdekatan. Pasangan dengan koordinat
        // persis sama (jarak ~0) dipisahkan karena itu masalah kualitas data,
        // bukan kandidat regrouping.
        $identik = [];
        $pairs = array_values(array_filter($pairs, function ($p) use (&$identik) {
            if ($p['jarak'] < 0.02) {
                $identik[] = $p;
                return false;
            }

            return true;
        }));

        usort($pairs, function ($x, $y) {
            return [$x['siswa_gabung'], $x['jarak']] <=> [$y['siswa_gabung'], $y['jarak']];
        });

        $totalHematRombel = array_sum(array_column($pairs, 'hemat_rombel'));
        $totalHematGuru = array_sum(array_column($pairs, 'hemat_guru'));

        // Pasangan saling tumpang tindih (satu sekolah bisa masuk banyak pasangan),
        // jadi total potensi dihitung lewat pencocokan rakus tanpa tumpang tindih.
        $kandidat = $pairs;
        usort($kandidat, fn ($a, $b) => [$b['hemat_rombel'], $b['hemat_guru'], $a['jarak']] <=> [$a['hemat_rombel'], $a['hemat_guru'], $b['jarak']]);
        $dipakai = [];
        $hematRombelRencana = 0;
        $hematGuruRencana = 0;
        $pasanganTerpilih = 0;
        foreach ($kandidat as $p) {
            $na = $p['a']['npsn'];
            $nb = $p['b']['npsn'];
            if (isset($dipakai[$na]) || isset($dipakai[$nb])) {
                continue;
            }
            if ($p['hemat_rombel'] <= 0) {
                continue;
            }
            $dipakai[$na] = true;
            $dipakai[$nb] = true;
            $hematRombelRencana += $p['hemat_rombel'];
            $hematGuruRencana += $p['hemat_guru'];
            $pasanganTerpilih++;
        }

        $sekolahKecil = $schools->filter(fn ($s) => (int) ($siswa[$s->npsn]->total_siswa ?? 0) > 0 && (int) $siswa[$s->npsn]->total_siswa < 60)->count();

        // Sebaran pasangan per kecamatan — dipakai halaman Rekomendasi untuk
        // menentukan wilayah verifikasi lapangan tahap pertama.
        $pairsPerKecamatan = [];
        foreach ($pairs as $p) {
            $kec = $p['a']['kecamatan'];
            $pairsPerKecamatan[$kec] = ($pairsPerKecamatan[$kec] ?? 0) + 1;
        }
        arsort($pairsPerKecamatan);

        return [
            'pairs' => array_slice($pairs, 0, 40),
            'totalPairs' => count($pairs),
            'radius' => $radius,
            'jenjang' => $jenjang,
            'totalHematRombel' => $totalHematRombel,
            'totalHematGuru' => $totalHematGuru,
            'hematRombelRencana' => $hematRombelRencana,
            'hematGuruRencana' => $hematGuruRencana,
            'pasanganTerpilih' => $pasanganTerpilih,
            'sekolahKecil' => $sekolahKecil,
            'identik' => $identik,
            'pairsPerKecamatan' => array_slice($pairsPerKecamatan, 0, 5, true),
            'totalSekolah' => $schools->count(),
            'semester' => self::BASELINE_SEMESTER,
        ];
    }

    // =====================================================================
    // Helper Walidata
    // =====================================================================
    /**
     * Deteksi nama sekolah yang jelas bukan milik jenjangnya
     * (mis. STIMIK / SLB / LKP dilabeli SD pada data sumber).
     */
    protected function jenjangTidakSesuai(string $jenjang, string $nama): bool
    {
        $nama = strtoupper(preg_replace('/\s+/', ' ', trim($nama)) ?? '');
        if ($nama === '') {
            return false;
        }

        // Nama yang jelas bukan satuan pendidikan dasar/menengah
        if (preg_match('/^(SLB|LKP|LPK|PPS|PONPES|PONDOK|STIMIK|STIE|STIKES|STMIK|STIA|AMIK|UNIVERSITAS|INSTITUT|AKADEMI|POLITEKNIK|SEKOLAH TINGGI)\b/', $nama)) {
            return true;
        }

        $pola = [
            'SD' => '/^(SD|SDN|SDS|SDIT|SEKOLAH DASAR)\b/',
            'SMP' => '/^(SMP|SMPN|SMPS|SMPIT|SLTP|SEKOLAH MENENGAH PERTAMA|SEKOLAH RAKYAT MENENGAH PERTAMA)\b/',
            'SMA' => '/^(SMA|SMAN|SMAS|SMU|SEKOLAH MENENGAH ATAS)\b/',
            'SMK' => '/^(SMK|SMKN|SMKS|SMEA|STM|SEKOLAH MENENGAH KEJURUAN)\b/',
            'MI' => '/^(MI|MIN|MIS|MADRASAH IBTIDAIYAH)\b/',
            'MTS' => '/^(MTS|MTSN|MTSS|MADRASAH TSANAWIYAH)\b/',
            'MA' => '/^(MA|MAN|MAS|MADRASAH ALIYAH)\b/',
        ];

        // Jenjang dengan penamaan baku: periksa awalan nama
        if (isset($pola[$jenjang])) {
            return preg_match($pola[$jenjang], $nama) !== 1;
        }

        // TK/PAUD: penamaan bebas, jadi hanya ditandai bila nama jelas jenjang lain
        if (in_array($jenjang, ['TK', 'PAUD'], true)) {
            return preg_match('/^(SD|SDN|SDS|SMP|SMPN|SMA|SMAN|SMK|SMKN|MI|MIN|MTS|MTSN|MA|MAN)\b/', $nama) === 1;
        }

        return false;
    }

    /** @return array<int, array<string, mixed>> */
    protected function walidataRows(): array
    {
        static $rows = null;
        if ($rows !== null) {
            return $rows;
        }

        $raw = @file_get_contents(storage_path('app/data/walidata-pendidikan.json'));
        if ($raw === false) {
            return $rows = [];
        }

        return $rows = json_decode(ltrim($raw, "\xEF\xBB\xBF"), true) ?: [];
    }

    /** @return string[] */
    protected function walidataYears(): array
    {
        return ['2020', '2021', '2022', '2023', '2024'];
    }

    protected function walidataValue(array $rows, string $pattern, string $year = '2023'): float
    {
        return $this->walidataValueByYear($rows, $pattern, $year);
    }

    protected function walidataValueByYear(array $rows, string $pattern, string $year): float
    {
        foreach ($rows as $r) {
            if (preg_match($pattern, trim((string) $r['uraian']))) {
                return (float) ($r[$year] ?? 0);
            }
        }

        return 0.0;
    }
}
