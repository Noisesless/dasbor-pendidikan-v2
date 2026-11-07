<?php

namespace App\Services;

use App\Support\KecamatanGeo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Layanan Hybrid RAG (Online Gemini API + Offline Local Retrieval Engine)
 * untuk Chatbot Analitik Data Pendidikan Kabupaten Banjarnegara.
 *
 * Lingkup: Seluruh data pendidikan di dalam aplikasi (Dapodik, Kemenag,
 * Dindikpora, Disarpus, BPS, Walidata, SIMU-DIK) dengan fokus khusus pada
 * halaman /rekomendasi dan penjelasan keterbatasan data guru MI & MTs.
 */
class EducationRagService
{
    private const BASELINE_SEMESTER = '20252';

    /**
     * Proses pertanyaan pengguna dengan mode: 'auto' | 'online' | 'offline'.
     *
     * @param  string  $question
     * @param  string  $mode
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{answer: string, mode_used: string, sources: array<int, string>, follow_ups: array<int, string>}
     */
    public function ask(string $question, string $mode = 'auto', array $history = []): array
    {
        $question = trim($question);
        $retrieval = $this->retrieveContext($question);

        if ($retrieval['out_of_scope']) {
            return [
                'answer' => "Pertanyaan tersebut berada di luar lingkup data aplikasi **Dasbor Pendidikan Kabupaten Banjarnegara**.\n\nSaya hanya dapat menjawab pertanyaan seputar data satuan pendidikan, peserta didik, guru, infrastruktur, peta sebaran, indikator makro (APK/APM/HLS/RLS/IPM), serta **Rekomendasi Kepala Dinas** (termasuk analisis kekurangan data guru **MI dan MTs**).",
                'mode_used' => 'offline-guard',
                'sources' => ['Batasan Lingkup Aplikasi'],
                'follow_ups' => [
                    'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                    'Bagaimana perbandingan rasio siswa per guru jika MI dan MTs dipisahkan?',
                    'Apa 3 rekomendasi kritis untuk Kepala Dinas tahun ini?',
                ],
            ];
        }

        $preferOnline = in_array($mode, ['auto', 'online'], true);
        $apiKey = (string) config('services.gemini.api_key', '');

        if ($preferOnline && $apiKey !== '') {
            $onlineAnswer = $this->callGemini($question, $retrieval['context_text'], $history, $apiKey);
            if ($onlineAnswer !== null) {
                return [
                    'answer' => $onlineAnswer,
                    'mode_used' => 'online-gemini',
                    'sources' => $retrieval['sources'],
                    'follow_ups' => $retrieval['follow_ups'],
                ];
            }
        }

        return [
            'answer' => $this->synthesizeOffline($question, $retrieval),
            'mode_used' => 'offline-rag',
            'sources' => $retrieval['sources'],
            'follow_ups' => $retrieval['follow_ups'],
        ];
    }

    /**
     * Ambil konteks faktual (RAG Chunks) dari database sesuai entitas & topik pertanyaan.
     *
     * @return array{
     *   out_of_scope: bool,
     *   topics: array<int, string>,
     *   matched_kecamatan: array<int, string>,
     *   matched_jenjang: array<int, string>,
     *   matched_schools: array<int, array<string, mixed>>,
     *   core: array<string, mixed>,
     *   context_text: string,
     *   sources: array<int, string>,
     *   follow_ups: array<int, string>
     * }
     */
    public function retrieveContext(string $question): array
    {
        $qLower = mb_strtolower($question);

        $outOfScopeKeywords = [
            'resep masakan', 'prediksi bola', 'skor pertandingan', 'harga saham', 'kripto',
            'bitcoin', 'cuaca hari ini', 'jadwal bioskop', 'zodiak', 'ramalan bintang',
        ];
        foreach ($outOfScopeKeywords as $kw) {
            if (str_contains($qLower, $kw)) {
                return [
                    'out_of_scope' => true,
                    'topics' => [],
                    'matched_kecamatan' => [],
                    'matched_jenjang' => [],
                    'matched_schools' => [],
                    'core' => [],
                    'context_text' => '',
                    'sources' => [],
                    'follow_ups' => [],
                ];
            }
        }

        $core = $this->buildCoreFacts();
        $topics = $this->detectTopics($qLower);

        // Deteksi kecamatan yang disebut
        $matchedKecamatan = [];
        foreach (array_keys($core['kecamatan']) as $kec) {
            $kecLower = mb_strtolower($kec);
            if (str_contains($qLower, $kecLower) || ($kecLower === 'purwareja klampok' && str_contains($qLower, 'klampok'))) {
                $matchedKecamatan[] = $kec;
            }
        }

        // Deteksi jenjang yang disebut
        $matchedJenjang = [];
        $jenjangMap = [
            'MI' => ['\bmi\b', 'madrasah ibtidaiyah'],
            'MTS' => ['\bmts\b', 'tsanawiyah'],
            'MA' => ['\bma\b', 'madrasah aliyah'],
            'SD' => ['\bsd\b', 'sekolah dasar'],
            'SMP' => ['\bsmp\b', 'menengah pertama'],
            'SMA' => ['\bsma\b', 'menengah atas'],
            'SMK' => ['\bsmk\b', 'kejuruan'],
            'PAUD' => ['\bpaud\b', 'usia dini'],
            'TK' => ['\btk\b', 'taman kanak'],
            'PKBM' => ['\bpkbm\b', 'kesetaraan', 'paket'],
        ];
        foreach ($jenjangMap as $jenjang => $patterns) {
            foreach ($patterns as $pat) {
                if (preg_match('/' . $pat . '/u', $qLower)) {
                    $matchedJenjang[] = $jenjang;
                    break;
                }
            }
        }

        // Deteksi pencarian sekolah / NPSN
        $matchedSchools = $this->searchSchools($qLower);

        $sources = [
            'Dapodik Semester 20252 (TA 2025/2026)',
            'Halaman Rekomendasi Kepala Dinas (/rekomendasi)',
        ];

        $chunks = [];
        $chunks[] = $this->formatCoreChunk($core);
        $chunks[] = $this->formatMadrasahGapChunk($core);
        $chunks[] = $this->formatRecommendationsChunk($core);

        if ($matchedKecamatan !== []) {
            $sources[] = 'Profil Kecamatan Dapodik & Kemenag';
            foreach ($matchedKecamatan as $kec) {
                $chunks[] = $this->formatKecamatanChunk($core['kecamatan'][$kec]);
            }
        }

        if ($matchedJenjang !== []) {
            $sources[] = 'Rekapitulasi Jenjang Satuan Pendidikan';
            $chunks[] = $this->formatJenjangChunk($core, $matchedJenjang);
        }

        if ($matchedSchools !== []) {
            $sources[] = 'Direktori Satuan Pendidikan (schools & SIMU-DIK)';
            $chunks[] = $this->formatSchoolsChunk($matchedSchools);
        }

        if (in_array('bps', $topics, true) || in_array('indikator', $topics, true)) {
            $sources[] = 'BPS Kabupaten Banjarnegara (2018-2024)';
            $chunks[] = $this->formatBpsChunk($core);
        }

        $followUps = $this->suggestFollowUps($topics, $matchedKecamatan, $matchedJenjang);

        return [
            'out_of_scope' => false,
            'topics' => $topics,
            'matched_kecamatan' => $matchedKecamatan,
            'matched_jenjang' => $matchedJenjang,
            'matched_schools' => $matchedSchools,
            'core' => $core,
            'context_text' => implode("\n\n", $chunks),
            'sources' => array_values(array_unique($sources)),
            'follow_ups' => $followUps,
        ];
    }

    /**
     * Kumpulkan fakta inti dari database aplikasi.
     *
     * @return array<string, mixed>
     */
    private function buildCoreFacts(): array
    {
        $semester = self::BASELINE_SEMESTER;
        $madrasahJenjang = ['MI', 'MTS', 'MA'];

        $schoolsByJenjang = DB::table('schools')
            ->selectRaw('jenjang, COUNT(*) as sekolah, SUM(CASE WHEN status = "Negeri" THEN 1 ELSE 0 END) as negeri, SUM(CASE WHEN status = "Swasta" THEN 1 ELSE 0 END) as swasta')
            ->groupBy('jenjang')
            ->get()
            ->keyBy('jenjang');

        $siswaRows = DB::table('student_stats')->where('semester', $semester)->get();
        $guruRows = DB::table('teacher_stats')->where('semester', $semester)->get();

        $totalSekolah = (int) DB::table('schools')->count();
        $totalSiswa = (int) $siswaRows->sum('total_siswa');
        $totalSiswaUmum = (int) $siswaRows->reject(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true))->sum('total_siswa');
        $totalGuru = (int) $guruRows->sum('jumlah_guru');
        $totalSertifikasi = (int) $guruRows->sum('jumlah_sertifikasi');
        $totalUsiaSesuai = (int) $siswaRows->sum('usia_sesuai');

        $miSiswa = (int) $siswaRows->where('jenjang', 'MI')->sum('total_siswa');
        $mtsSiswa = (int) $siswaRows->where('jenjang', 'MTS')->sum('total_siswa');
        $maSiswa = (int) $siswaRows->where('jenjang', 'MA')->sum('total_siswa');

        $miSekolah = (int) ($schoolsByJenjang['MI']->sekolah ?? 210);
        $mtsSekolah = (int) ($schoolsByJenjang['MTS']->sekolah ?? 41);
        $maSekolah = (int) ($schoolsByJenjang['MA']->sekolah ?? 19);

        $miGuruDapodik = (int) $guruRows->where('jenjang', 'MI')->sum('jumlah_guru');
        $mtsGuruDapodik = (int) $guruRows->where('jenjang', 'MTS')->sum('jumlah_guru');
        $maGuruDapodik = (int) $guruRows->where('jenjang', 'MA')->sum('jumlah_guru');

        // Agregat Kemenag
        $miGuruKemenag = (int) DB::table('regional_education_stats')
            ->where('sumber', 'kemenag')->where('jenjang', 'MI')->where('tahun_ajaran', '2018/2019')
            ->selectRaw('COALESCE(SUM(guru_negeri + guru_swasta), 0) as g')->value('g') ?: 1563;
        $mtsGuruKemenag = (int) DB::table('regional_education_stats')
            ->where('sumber', 'kemenag')->where('jenjang', 'MTS')->where('tahun_ajaran', '2024/2025')
            ->selectRaw('COALESCE(SUM(guru_negeri + guru_swasta), 0) as g')->value('g') ?: 745;
        $maGuruKemenag = (int) DB::table('regional_education_stats')
            ->where('sumber', 'kemenag')->where('jenjang', 'MA')->where('tahun_ajaran', '2024/2025')
            ->selectRaw('COALESCE(SUM(guru_negeri + guru_swasta), 0) as g')->value('g') ?: 363;
        $estimasiGuruMadrasah = $miGuruKemenag + $mtsGuruKemenag + $maGuruKemenag;

        $padatAll = $siswaRows->filter(fn ($r) => (int) $r->jumlah_rombel > 0 && (int) $r->total_siswa / (int) $r->jumlah_rombel > 32);
        $padatUmum = $siswaRows->reject(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true))
            ->filter(fn ($r) => (int) $r->jumlah_rombel > 0 && (int) $r->total_siswa / (int) $r->jumlah_rombel > 32);
        $padatMadrasahPlaceholder = $siswaRows->filter(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true)
            && (int) $r->jumlah_rombel <= 1 && (int) $r->total_siswa > 32);

        $sekolahPerKec = DB::table('schools')->selectRaw('kecamatan, COUNT(*) as n')->groupBy('kecamatan')->pluck('n', 'kecamatan');
        $miPerKec = DB::table('schools')->where('jenjang', 'MI')->selectRaw('kecamatan, COUNT(*) as n')->groupBy('kecamatan')->pluck('n', 'kecamatan');
        $mtsPerKec = DB::table('schools')->where('jenjang', 'MTS')->selectRaw('kecamatan, COUNT(*) as n')->groupBy('kecamatan')->pluck('n', 'kecamatan');

        $siswaPerKec = $siswaRows->groupBy('kecamatan');
        $guruPerKec = $guruRows->groupBy('kecamatan');

        $kecamatan = [];
        foreach ($siswaPerKec as $kec => $rows) {
            $sTotal = (int) $rows->sum('total_siswa');
            $sUmum = (int) $rows->reject(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true))->sum('total_siswa');
            $sMi = (int) $rows->where('jenjang', 'MI')->sum('total_siswa');
            $sMts = (int) $rows->where('jenjang', 'MTS')->sum('total_siswa');
            $sMa = (int) $rows->where('jenjang', 'MA')->sum('total_siswa');
            $g = isset($guruPerKec[$kec]) ? (int) $guruPerKec[$kec]->sum('jumlah_guru') : 0;
            $sert = isset($guruPerKec[$kec]) ? (int) $guruPerKec[$kec]->sum('jumlah_sertifikasi') : 0;
            $sesuai = (int) $rows->sum('usia_sesuai');

            $kecamatan[$kec] = [
                'nama' => $kec,
                'sekolah' => (int) ($sekolahPerKec[$kec] ?? 0),
                'mi_sekolah' => (int) ($miPerKec[$kec] ?? 0),
                'mts_sekolah' => (int) ($mtsPerKec[$kec] ?? 0),
                'siswa_total' => $sTotal,
                'siswa_umum' => $sUmum,
                'siswa_mi' => $sMi,
                'siswa_mts' => $sMts,
                'siswa_ma' => $sMa,
                'guru_dapodik' => $g,
                'rasio_gabungan' => $g > 0 ? round($sTotal / $g, 1) : 0,
                'rasio_umum' => $g > 0 ? round($sUmum / $g, 1) : 0,
                'sertifikasi_pct' => $g > 0 ? round($sert / $g * 100, 1) : 0,
                'overage_pct' => $sTotal > 0 ? round((1 - $sesuai / $sTotal) * 100, 1) : 0,
            ];
        }

        // Rekap per jenjang
        $jenjangStats = [];
        foreach ($schoolsByJenjang as $j => $row) {
            $sJ = (int) $siswaRows->where('jenjang', $j)->sum('total_siswa');
            $gJ = (int) $guruRows->where('jenjang', $j)->sum('jumlah_guru');
            $rJ = (int) $siswaRows->where('jenjang', $j)->sum('jumlah_rombel');
            $jenjangStats[$j] = [
                'jenjang' => $j,
                'sekolah' => (int) $row->sekolah,
                'negeri' => (int) $row->negeri,
                'swasta' => (int) $row->swasta,
                'siswa' => $sJ,
                'guru_dapodik' => $gJ,
                'rombel' => $rJ,
                'rasio' => $gJ > 0 ? round($sJ / $gJ, 1) : null,
            ];
        }

        $punyaSiswa = $siswaRows->pluck('npsn')->unique()->all();
        $tanpaDataSiswa = (int) DB::table('schools')->whereNotIn('npsn', $punyaSiswa)->count();
        $tanpaTelepon = (int) DB::table('schools')->where(fn ($q) => $q->whereNull('telepon')->orWhere('telepon', ''))->count();
        $akreditasiRendah = (int) DB::table('schools')
            ->where(fn ($q) => $q->whereNull('akreditasi')->orWhere('akreditasi', '')->orWhere('akreditasi', 'C'))
            ->count();

        $kecRasioGabunganTinggi = array_filter($kecamatan, fn ($k) => $k['rasio_gabungan'] > 20);
        uasort($kecRasioGabunganTinggi, fn ($a, $b) => $b['rasio_gabungan'] <=> $a['rasio_gabungan']);

        $kecRasioUmumTinggi = array_filter($kecamatan, fn ($k) => $k['rasio_umum'] > 20);
        uasort($kecRasioUmumTinggi, fn ($a, $b) => $b['rasio_umum'] <=> $a['rasio_umum']);

        return [
            'semester' => $semester,
            'total_sekolah' => $totalSekolah,
            'total_siswa' => $totalSiswa,
            'total_siswa_umum' => $totalSiswaUmum,
            'total_siswa_madrasah' => $miSiswa + $mtsSiswa + $maSiswa,
            'total_guru' => $totalGuru,
            'total_sertifikasi' => $totalSertifikasi,
            'pct_sertifikasi' => $totalGuru > 0 ? round($totalSertifikasi / $totalGuru * 100, 1) : 0,
            'rasio_gabungan' => $totalGuru > 0 ? round($totalSiswa / $totalGuru, 1) : 0,
            'rasio_umum' => $totalGuru > 0 ? round($totalSiswaUmum / $totalGuru, 1) : 0,
            'rasio_estimasi_lengkap' => ($totalGuru + $estimasiGuruMadrasah) > 0
                ? round($totalSiswa / ($totalGuru + $estimasiGuruMadrasah), 1)
                : 0,
            'siswa_overage' => max(0, $totalSiswa - $totalUsiaSesuai),
            'pct_overage' => $totalSiswa > 0 ? round((1 - $totalUsiaSesuai / $totalSiswa) * 100, 1) : 0,
            'mi' => [
                'sekolah' => $miSekolah,
                'siswa' => $miSiswa,
                'guru_dapodik' => $miGuruDapodik,
                'guru_kemenag_2018' => $miGuruKemenag,
            ],
            'mts' => [
                'sekolah' => $mtsSekolah,
                'siswa' => $mtsSiswa,
                'guru_dapodik' => $mtsGuruDapodik,
                'guru_kemenag_2024' => $mtsGuruKemenag,
            ],
            'ma' => [
                'sekolah' => $maSekolah,
                'siswa' => $maSiswa,
                'guru_dapodik' => $maGuruDapodik,
                'guru_kemenag_2024' => $maGuruKemenag,
            ],
            'estimasi_guru_madrasah' => $estimasiGuruMadrasah,
            'rombel_padat_total' => $padatAll->count(),
            'rombel_padat_umum' => $padatUmum->count(),
            'rombel_padat_madrasah_placeholder' => $padatMadrasahPlaceholder->count(),
            'rombel_padat_umum_per_jenjang' => $padatUmum->groupBy('jenjang')->map->count()->sortDesc()->all(),
            'tanpa_data_siswa' => $tanpaDataSiswa,
            'tanpa_telepon' => $tanpaTelepon,
            'akreditasi_rendah' => $akreditasiRendah,
            'kecamatan' => $kecamatan,
            'kec_rasio_gabungan_tinggi' => $kecRasioGabunganTinggi,
            'kec_rasio_umum_tinggi' => $kecRasioUmumTinggi,
            'jenjang_stats' => $jenjangStats,
        ];
    }

    /**
     * Deteksi topik utama dari teks pertanyaan.
     *
     * @return array<int, string>
     */
    private function detectTopics(string $q): array
    {
        $topics = [];
        $rules = [
            'madrasah_gap' => ['mts', 'mi ', ' mi', 'madrasah', 'kemenag', 'emis', 'kekurangan data', 'belum sempurna', 'blank spot', 'duplikat'],
            'rekomendasi' => ['rekomendasi', 'kepala dinas', 'prioritas', 'keputusan', 'kritis', 'kebijakan'],
            'guru_rasio' => ['guru', 'rasio', 'sertifikasi', 'pendidik', 'ptk', 'pppk', 'pns', 'mutasi', 'redistribusi'],
            'sarana' => ['sarana', 'prasarana', 'ruang kelas', 'rusak', 'anggaran', 'rehab', 'biaya', 'dak'],
            'regrouping' => ['regrouping', 'sekolah kecil', 'penggabungan', 'merger'],
            'rombel' => ['rombel', 'rombongan belajar', 'padat', 'gemuk'],
            'data_health' => ['kualitas data', 'data kosong', 'telepon', 'koordinat', 'simudik', 'anomali'],
            'akreditasi' => ['akreditasi', 'mutu'],
            'overage' => ['luar usia', 'overage', 'tinggal kelas', 'putus sekolah', 'angka keluar'],
            'bps' => ['bps', 'hls', 'rls', 'ipm', 'harapan lama', 'rata-rata lama', 'kemiskinan', 'gini', 'apk', 'apm'],
        ];

        foreach ($rules as $topic => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($q, $kw)) {
                    $topics[] = $topic;
                    break;
                }
            }
        }

        if (preg_match('/\b(mi|mts|ma)\b/u', $q) && !in_array('madrasah_gap', $topics, true)) {
            $topics[] = 'madrasah_gap';
        }

        return $topics ?: ['umum'];
    }

    /**
     * Cari satuan pendidikan spesifik jika pengguna menyebut nama sekolah atau NPSN.
     *
     * @return array<int, array<string, mixed>>
     */
    private function searchSchools(string $q): array
    {
        if (preg_match('/\b(\d{8})\b/', $q, $m)) {
            $npsn = $m[1];
            $school = DB::table('schools')->where('npsn', $npsn)->first();
            if ($school) {
                return [$this->enrichSchool($school)];
            }
        }

        if (preg_match('/\b(sdn|sd\s+negeri|smpn|smp\s+negeri|sman|sma\s+negeri|smkn|smk\s+negeri|min|mtsn|man|mi\s+|mts\s+|ma\s+)[a-z0-9\s]{3,30}/u', $q, $m)) {
            $term = trim($m[0]);
            $rows = DB::table('schools')
                ->where('nama_sekolah', 'like', '%' . $term . '%')
                ->limit(5)
                ->get();
            $out = [];
            foreach ($rows as $r) {
                $out[] = $this->enrichSchool($r);
            }

            return $out;
        }

        return [];
    }

    private function enrichSchool(object $s): array
    {
        $st = DB::table('student_stats')->where('semester', self::BASELINE_SEMESTER)->where('npsn', $s->npsn)->first();
        $gt = DB::table('teacher_stats')->where('semester', self::BASELINE_SEMESTER)->where('npsn', $s->npsn)->first();

        return [
            'npsn' => $s->npsn,
            'nama_sekolah' => $s->nama_sekolah,
            'jenjang' => $s->jenjang,
            'status' => $s->status,
            'akreditasi' => $s->akreditasi ?: 'Belum',
            'kecamatan' => $s->kecamatan,
            'telepon' => $s->telepon ?: '-',
            'siswa' => (int) ($st->total_siswa ?? 0),
            'rombel' => (int) ($st->jumlah_rombel ?? 0),
            'guru' => (int) ($gt->jumlah_guru ?? 0),
            'sertifikasi' => (int) ($gt->jumlah_sertifikasi ?? 0),
        ];
    }

    private function formatCoreChunk(array $c): string
    {
        return "[FAKTA INTI KABUPATEN BANJARNEGARA - SEMESTER {$c['semester']}]\n"
            . "- Total Satuan Pendidikan: " . number_format($c['total_sekolah'], 0, ',', '.') . " sekolah di 20 kecamatan.\n"
            . "- Total Peserta Didik: " . number_format($c['total_siswa'], 0, ',', '.') . " siswa (terdiri atas "
            . number_format($c['total_siswa_umum'], 0, ',', '.') . " siswa sekolah umum non-madrasah dan "
            . number_format($c['total_siswa_madrasah'], 0, ',', '.') . " siswa madrasah MI/MTs/MA).\n"
            . "- Total Guru Tercatat di Dapodik (teacher_stats): " . number_format($c['total_guru'], 0, ',', '.')
            . " guru (bersertifikat: " . number_format($c['total_sertifikasi'], 0, ',', '.') . " guru atau {$c['pct_sertifikasi']}%).\n"
            . "- Siswa di Luar Usia Jenjang (Overage): " . number_format($c['siswa_overage'], 0, ',', '.') . " siswa ({$c['pct_overage']}%).\n"
            . "- Sekolah Akreditasi C/Belum: " . number_format($c['akreditasi_rendah'], 0, ',', '.') . " sekolah.\n"
            . "- Sekolah Tanpa Data Siswa: {$c['tanpa_data_siswa']} sekolah | Tanpa Nomor Telepon: {$c['tanpa_telepon']} sekolah.";
    }

    private function formatMadrasahGapChunk(array $c): string
    {
        $kecGab = implode(', ', array_map(
            fn ($k) => "{$k['nama']} (gabungan {$k['rasio_gabungan']}:1 vs sekolah umum {$k['rasio_umum']}:1, siswa MI+MTs: " . ($k['siswa_mi'] + $k['siswa_mts']) . ")",
            array_slice($c['kec_rasio_gabungan_tinggi'], 0, 5)
        ));

        return "[PENJELASAN KEKURANGAN DATA GURU MI & MTs PADA ANALISIS /REKOMENDASI]\n"
            . "1. Kekosongan Data Guru Per-NPSN di Dapodik (teacher_stats):\n"
            . "   - Jenjang MI: Terdapat {$c['mi']['sekolah']} MI dengan " . number_format($c['mi']['siswa'], 0, ',', '.') . " siswa, namun rekaman guru di tabel teacher_stats adalah {$c['mi']['guru_dapodik']} (0 guru).\n"
            . "   - Jenjang MTs: Terdapat {$c['mts']['sekolah']} MTs dengan " . number_format($c['mts']['siswa'], 0, ',', '.') . " siswa, namun rekaman guru di tabel teacher_stats adalah {$c['mts']['guru_dapodik']} (0 guru).\n"
            . "   - Jenjang MA: Terdapat {$c['ma']['sekolah']} MA dengan " . number_format($c['ma']['siswa'], 0, ',', '.') . " siswa, namun rekaman guru di tabel teacher_stats adalah {$c['ma']['guru_dapodik']} (0 guru).\n"
            . "   - Total terdapat " . number_format($c['mi']['siswa'] + $c['mts']['siswa'], 0, ',', '.') . " siswa MI & MTs (" . number_format($c['total_siswa_madrasah'], 0, ',', '.') . " siswa jika termasuk MA) yang masuk ke dalam perhitungan total siswa, tetapi pembagi gurunya di Dapodik masih 0.\n"
            . "2. Kendala pada Sumber Data Agregat Satu Data Kemenag (storage/app/data/kemenag/):\n"
            . "   - Pada berkas MTs (mts.csv), data agregat kecamatan TA 2024/2025 mencatat {$c['mts']['guru_kemenag_2024']} guru MTs (165 negeri + 580 swasta di 41 MTs), tetapi tidak memiliki rincian per NPSN maupun status sertifikasi.\n"
            . "   - Pada berkas MI (mi.csv), 7 dari 9 tahun ajaran (2017/2018 dan 2019/2020 s/d 2024/2025) rusak karena isinya terduplikasi persis dengan angka MTs (4 negeri, 37 swasta). Data agregat guru MI yang valid terakhir hanya pada TA 2018/2019 yakni " . number_format($c['mi']['guru_kemenag_2018'], 0, ',', '.') . " guru MI (58 negeri + 1.505 swasta di 202 MI).\n"
            . "3. Dampak Distorsi pada Analisis di Halaman /rekomendasi:\n"
            . "   - Distorsi Rasio Siswa/Guru: Rasio gabungan tampak sebesar {$c['rasio_gabungan']}:1 dan membuat " . count($c['kec_rasio_gabungan_tinggi']) . " kecamatan tampak melampaui ambang 20:1 ({$kecGab}). Padahal jika dihitung khusus sekolah umum non-madrasah (PAUD, TK, SD, SMP, SMA, SMK, PKBM), rasionya adalah {$c['rasio_umum']}:1 (dan hanya " . count($c['kec_rasio_umum_tinggi']) . " kecamatan yang di atas 20:1, yakni Pandanarum 20,9:1). Jika estimasi " . number_format($c['estimasi_guru_madrasah'], 0, ',', '.') . " guru agregat Kemenag digabungkan, rasio kabupaten adalah {$c['rasio_estimasi_lengkap']}:1.\n"
            . "   - Distorsi Rombel Padat (>32 siswa/rombel): Dari {$c['rombel_padat_total']} satuan pendidikan yang terdeteksi rombel padat, sebanyak {$c['rombel_padat_madrasah_placeholder']} adalah madrasah (MI/MTs/MA) karena kolom jumlah_rombel di tabel student_stats masih bernilai placeholder 1 rombel per madrasah. Pada sekolah umum yang data rombelnya lengkap, hanya ada {$c['rombel_padat_umum']} sekolah dengan rombel padat.";
    }

    private function formatRecommendationsChunk(array $c): string
    {
        return "[RINGKASAN 8 REKOMENDASI KEPALA DINAS (/rekomendasi)]\n"
            . "1. [KRITIS - Sarana & Prasarana] Rehabilitasi 534 ruang kelas rusak sedang/berat (total 1.187 dari 6.235 ruang tidak layak pakai; defisit 342 ruang di SMA & SMK; estimasi biaya Rp 366,55 miliar dalam 3 tahap APBD/DAK).\n"
            . "2. [KRITIS - Data & Perencanaan] Integrasi data guru {$c['mi']['sekolah']} MI dan {$c['mts']['sekolah']} MTs (total 270 madrasah) dari EMIS Kemenag serta verifikasi {$c['tanpa_data_siswa']} sekolah tanpa data murid.\n"
            . "3. [KRITIS - Guru & Tenaga Kependidikan] Redistribusi guru dengan memisahkan rasio sekolah umum ({$c['rasio_umum']}:1) dari rasio gabungan ({$c['rasio_gabungan']}:1) yang terdistorsi kekosongan data guru MI & MTs.\n"
            . "4. [TINGGI - Efisiensi Satuan Pendidikan] Regrouping 114 SD kecil (<60 siswa); terdapat 24 pasangan SD berjarak <1 km dengan 18 pasangan terpilih tanpa tumpang tindih.\n"
            . "5. [TINGGI - Mutu Pembelajaran] Penanganan rombel padat (>32 siswa/rombel) pada {$c['rombel_padat_umum']} sekolah umum serta sinkronisasi jumlah rombel {$c['rombel_padat_madrasah_placeholder']} madrasah.\n"
            . "6. [TINGGI - Mutu & Akuntabilitas] Pendampingan akreditasi {$c['akreditasi_rendah']} satuan pendidikan berpredikat C atau belum terakreditasi.\n"
            . "7. [SEDANG - Pemerataan Akses] Penurunan {$c['pct_overage']}% (" . number_format($c['siswa_overage'], 0, ',', '.') . " siswa) yang belajar di luar rentang usia jenjang.\n"
            . "8. [SEDANG - Pemerataan & Kualitas Data] Peningkatan Rata-rata Lama Sekolah (RLS 6,88 tahun vs HLS 11,71 tahun) lewat PKBM Paket B/C dan kelengkapan nomor telepon ({$c['tanpa_telepon']} sekolah belum terisi setelah rekonsiliasi 176 nomor dari SIMU-DIK).";
    }

    private function formatKecamatanChunk(array $k): string
    {
        return "[PROFIL KECAMATAN " . strtoupper($k['nama']) . "]\n"
            . "- Jumlah Sekolah: {$k['sekolah']} satuan pendidikan (termasuk {$k['mi_sekolah']} MI dan {$k['mts_sekolah']} MTs).\n"
            . "- Siswa Total: " . number_format($k['siswa_total'], 0, ',', '.') . " siswa (Sekolah Umum: " . number_format($k['siswa_umum'], 0, ',', '.')
            . " | MI: " . number_format($k['siswa_mi'], 0, ',', '.') . " | MTs: " . number_format($k['siswa_mts'], 0, ',', '.') . " | MA: " . number_format($k['siswa_ma'], 0, ',', '.') . ").\n"
            . "- Guru Tercatat di Dapodik: " . number_format($k['guru_dapodik'], 0, ',', '.') . " guru (Sertifikasi: {$k['sertifikasi_pct']}%).\n"
            . "- Rasio Siswa/Guru Gabungan: {$k['rasio_gabungan']}:1 | Rasio Riil Sekolah Umum Non-Madrasah: {$k['rasio_umum']}:1.\n"
            . "- Proporsi Siswa di Luar Usia (Overage): {$k['overage_pct']}%.";
    }

    /**
     * @param  array<string, mixed>  $c
     * @param  array<int, string>  $jenjangs
     */
    private function formatJenjangChunk(array $c, array $jenjangs): string
    {
        $lines = ["[REKAPITULASI PER JENJANG]"];
        foreach ($jenjangs as $j) {
            $st = $c['jenjang_stats'][$j] ?? null;
            if (!$st) {
                continue;
            }
            $rasioTxt = $st['rasio'] !== null ? "{$st['rasio']}:1" : 'Belum tersedia di Dapodik (0 guru per-NPSN; kewenangan EMIS Kemenag)';
            $lines[] = "- {$j}: {$st['sekolah']} sekolah ({$st['negeri']} Negeri, {$st['swasta']} Swasta), "
                . number_format($st['siswa'], 0, ',', '.') . " siswa, "
                . number_format($st['guru_dapodik'], 0, ',', '.') . " guru Dapodik, Rasio: {$rasioTxt}.";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $schools
     */
    private function formatSchoolsChunk(array $schools): string
    {
        $lines = ["[DATA SATUAN PENDIDIKAN TERKAIT]"];
        foreach ($schools as $s) {
            $lines[] = "- {$s['nama_sekolah']} (NPSN: {$s['npsn']}, {$s['jenjang']} {$s['status']}, Kec. {$s['kecamatan']}): "
                . "Akreditasi {$s['akreditasi']}, Siswa: {$s['siswa']}, Rombel: {$s['rombel']}, Guru Dapodik: {$s['guru']} (Bersertifikat: {$s['sertifikasi']}), Telp: {$s['telepon']}.";
        }

        return implode("\n", $lines);
    }

    private function formatBpsChunk(array $c): string
    {
        return "[INDIKATOR MAKRO BPS KABUPATEN BANJARNEGARA (2024)]\n"
            . "- Harapan Lama Sekolah (HLS): 11,71 tahun | Rata-rata Lama Sekolah (RLS): 6,88 tahun (selisih 4,83 tahun).\n"
            . "- Indeks Pembangunan Manusia (IPM): 69,65 | Persentase Penduduk Miskin: 14,71% (138,1 ribu jiwa).\n"
            . "- Usia Harapan Hidup (UHH): 74,74 tahun | Indeks Gini: 0,331.";
    }

    /**
     * Panggil Gemini API (Online RAG) dengan fallback antar-model cepat.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     */
    private function callGemini(string $question, string $contextText, array $history, string $apiKey): ?string
    {
        $systemPrompt = "Anda adalah Asisten Analitik Data Pendidikan Kabupaten Banjarnegara pada aplikasi Dasbor Pendidikan (pendidikan.sistemdata.id).\n"
            . "ATURAN WAJIB:\n"
            . "1. Jawab HANYA berdasarkan [FAKTA] yang diberikan di bawah ini dalam Bahasa Indonesia yang lugas, jelas, dan berbasis angka pasti.\n"
            . "2. Jika ditanya mengenai halaman /rekomendasi, rasio guru, madrasah, MI, atau MTs, JELASKAN secara transparan bahwa analisis gabungan di /rekomendasi dipengaruhi oleh kekurangan data guru MI (210 sekolah, 23.699 siswa) dan MTs (41 sekolah, 10.957 siswa) yang masih bernilai 0 di tabel per-NPSN Dapodik (teacher_stats), serta duplikasi CSV MI dengan MTs pada 7 dari 9 tahun ajaran di Satu Data Kemenag. Sertakan perbandingan rasio gabungan (19,6:1) vs rasio riil sekolah umum non-madrasah (15,8:1) vs estimasi jika guru agregat Kemenag digabungkan (15,6:1).\n"
            . "3. Jangan mengarang angka di luar konteks. Gunakan poin-poin ringkas agar mudah dibaca oleh Kepala Dinas atau analis kebijakan.\n\n"
            . "=== KONTEKS FAKTUAL RAG DARI DATABASE APLIKASI ===\n"
            . $contextText;

        $contents = [];
        foreach (array_slice($history, -4) as $msg) {
            $role = ($msg['role'] ?? '') === 'model' ? 'model' : 'user';
            $text = trim((string) ($msg['text'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]],
                ];
            }
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $question]],
        ];

        $configuredModel = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');
        $models = array_values(array_unique([$configuredModel, 'gemini-3.5-flash-lite', 'gemini-3.1-flash-lite']));

        foreach ($models as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(10)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 700,
                        ],
                    ]);

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Mesin penyintesis jawaban Offline RAG (deterministik & instan dari database lokal).
     *
     * @param  array<string, mixed>  $retrieval
     */
    private function synthesizeOffline(string $question, array $retrieval): string
    {
        $c = $retrieval['core'];
        $topics = $retrieval['topics'];
        $kecs = $retrieval['matched_kecamatan'];
        $jenjangs = $retrieval['matched_jenjang'];
        $schools = $retrieval['matched_schools'];

        $sections = [];

        if ($schools !== []) {
            $lines = ["**Data Satuan Pendidikan Ditemukan:**"];
            foreach ($schools as $s) {
                $lines[] = "- **{$s['nama_sekolah']}** (NPSN `{$s['npsn']}`, {$s['jenjang']} {$s['status']}, Kec. {$s['kecamatan']}): "
                    . "**{$s['siswa']}** siswa, **{$s['rombel']}** rombel, **{$s['guru']}** guru Dapodik ({$s['sertifikasi']} bersertifikat), Akreditasi **{$s['akreditasi']}**, Telp: `{$s['telepon']}`.";
            }
            $sections[] = implode("\n", $lines);
        }

        if ($kecs !== []) {
            foreach ($kecs as $kecName) {
                $k = $c['kecamatan'][$kecName];
                $sections[] = "**Profil Pendidikan Kecamatan {$k['nama']}:**\n"
                    . "- **Satuan Pendidikan:** {$k['sekolah']} sekolah (termasuk **{$k['mi_sekolah']} MI** dan **{$k['mts_sekolah']} MTs**).\n"
                    . "- **Peserta Didik:** " . number_format($k['siswa_total'], 0, ',', '.') . " siswa (Sekolah Umum: **" . number_format($k['siswa_umum'], 0, ',', '.') . "**, MI: **" . number_format($k['siswa_mi'], 0, ',', '.') . "**, MTs: **" . number_format($k['siswa_mts'], 0, ',', '.') . "**, MA: **" . number_format($k['siswa_ma'], 0, ',', '.') . "**).\n"
                    . "- **Guru Dapodik:** " . number_format($k['guru_dapodik'], 0, ',', '.') . " guru (Sertifikasi: **{$k['sertifikasi_pct']}%**).\n"
                    . "- **Perbandingan Rasio Siswa/Guru:** Rasio gabungan tercatat **{$k['rasio_gabungan']}:1** (karena " . number_format($k['siswa_mi'] + $k['siswa_mts'] + $k['siswa_ma'], 0, ',', '.') . " siswa madrasah masuk pembilang tanpa data guru di Dapodik). Jika dihitung khusus **sekolah umum non-madrasah**, rasio riilnya adalah **{$k['rasio_umum']}:1**.\n"
                    . "- **Siswa di Luar Usia (Overage):** {$k['overage_pct']}%.";
            }
        }

        if (in_array('madrasah_gap', $topics, true) || in_array('guru_rasio', $topics, true) || in_array('rekomendasi', $topics, true)) {
            $sections[] = "**Penjelasan Kekurangan Data Guru MI & MTs pada Analisis `/rekomendasi`:**\n"
                . "1. **Kekosongan Data Guru Per-NPSN di Dapodik (`teacher_stats`):**\n"
                . "   - **MI:** Terdapat **{$c['mi']['sekolah']} MI** dengan **" . number_format($c['mi']['siswa'], 0, ',', '.') . " siswa**, tetapi jumlah guru tercatat di tabel `teacher_stats` adalah **0 guru**.\n"
                . "   - **MTs:** Terdapat **{$c['mts']['sekolah']} MTs** dengan **" . number_format($c['mts']['siswa'], 0, ',', '.') . " siswa**, tetapi jumlah guru tercatat di tabel `teacher_stats` juga **0 guru**.\n"
                . "   - **MA:** Terdapat **{$c['ma']['sekolah']} MA** dengan **" . number_format($c['ma']['siswa'], 0, ',', '.') . " siswa** (**0 guru** di `teacher_stats`).\n"
                . "2. **Kendala Data Agregat Satu Data Kemenag (`storage/app/data/kemenag/`):**\n"
                . "   - Untuk **MTs**, data agregat kecamatan TA 2024/2025 mencatat **{$c['mts']['guru_kemenag_2024']} guru** (165 negeri + 580 swasta), namun belum dirinci per NPSN maupun status sertifikasi.\n"
                . "   - Untuk **MI**, berkas CSV sumber mengalami **duplikasi dengan data MTs pada 7 dari 9 tahun ajaran** (`2017/2018` dan `2019/2020`–`2024/2025`). Data agregat guru MI yang valid terakhir berasal dari TA `2018/2019` sebanyak **" . number_format($c['mi']['guru_kemenag_2018'], 0, ',', '.') . " guru** (58 negeri + 1.505 swasta).\n"
                . "3. **Koreksi Dampak Analisis di `/rekomendasi`:**\n"
                . "   - **Rasio Siswa/Guru:** Rasio kasar gabungan tampak sebesar **{$c['rasio_gabungan']}:1** (" . count($c['kec_rasio_gabungan_tinggi']) . " kecamatan > 20:1) karena **" . number_format($c['mi']['siswa'] + $c['mts']['siswa'], 0, ',', '.') . " siswa MI & MTs** (plus " . number_format($c['ma']['siswa'], 0, ',', '.') . " siswa MA) ikut dibagi hanya dengan **" . number_format($c['total_guru'], 0, ',', '.') . " guru** sekolah umum. Jika dihitung khusus **sekolah umum non-madrasah**, rasio riil kabupaten adalah **{$c['rasio_umum']}:1** (dan bila estimasi **" . number_format($c['estimasi_guru_madrasah'], 0, ',', '.') . " guru** agregat Kemenag disertakan, rasionya menjadi **{$c['rasio_estimasi_lengkap']}:1**).\n"
                . "   - **Kepadatan Rombel:** Dari **{$c['rombel_padat_total']}** sekolah yang terdeteksi >32 siswa/rombel, sebanyak **{$c['rombel_padat_madrasah_placeholder']}** adalah MI/MTs/MA yang jumlah rombelnya masih tercatat placeholder `1` rombel per sekolah. Pada sekolah umum yang datanya lengkap, hanya terdapat **{$c['rombel_padat_umum']} sekolah** dengan rombel padat.";
        }

        if (in_array('rekomendasi', $topics, true)) {
            $sections[] = "**Ringkasan Prioritas pada Halaman `/rekomendasi`:**\n"
                . "- **Kritis (3 Rekomendasi):**\n"
                . "  1. **Rehabilitasi Sarana:** Penanganan **534 ruang kelas rusak sedang/berat** dan defisit **342 ruang** (SMA/SMK) dengan estimasi kebutuhan **Rp 366,55 miliar**.\n"
                . "  2. **Integrasi Data EMIS Kemenag & Dapodik:** Menutup kekosongan data guru per-NPSN pada **{$c['mi']['sekolah']} MI** dan **{$c['mts']['sekolah']} MTs** (total 270 madrasah) serta memverifikasi **{$c['tanpa_data_siswa']} sekolah** tanpa data murid.\n"
                . "  3. **Redistribusi Guru:** Pemerataan guru dengan mengacu pada rasio riil sekolah umum (**{$c['rasio_umum']}:1**, sertifikasi **{$c['pct_sertifikasi']}%**) agar tidak bias oleh absennya data guru MI/MTs.\n"
                . "- **Tinggi (3 Rekomendasi):** Regrouping **114 SD kecil** (<60 siswa, 18 pasangan terpilih jarak <1 km), pemecahan rombel padat di **{$c['rombel_padat_umum']} sekolah umum**, dan pendampingan **{$c['akreditasi_rendah']} sekolah** akreditasi C/belum.\n"
                . "- **Sedang (2 Rekomendasi):** Penurunan siswa di luar usia jenjang (**{$c['pct_overage']}%** / " . number_format($c['siswa_overage'], 0, ',', '.') . " siswa) dan peningkatan RLS (**6,88 tahun**) terhadap HLS (**11,71 tahun**).";
        }

        if (in_array('sarana', $topics, true)) {
            $sections[] = "**Analisis Sarana & Anggaran (`/analisis/sarana`):**\n"
                . "- Dari **6.235 ruang kelas** tercatat di Walidata, sebanyak **1.187 ruang (19,0%)** mengalami kerusakan (534 di antaranya rusak sedang/berat).\n"
                . "- Defisit ruang kelas menurut rasio ideal 28 siswa/ruang tercatat **342 ruang** (terkonsentrasi pada jenjang SMA dan SMK), dengan estimasi total kebutuhan anggaran **Rp 366,55 miliar**.";
        }

        if (in_array('regrouping', $topics, true)) {
            $sections[] = "**Analisis Kandidat Regrouping SD (`/analisis/regrouping`):**\n"
                . "- Terdapat **114 SD** dengan jumlah peserta didik di bawah 60 siswa.\n"
                . "- Ditemukan **24 pasangan SD** berjarak lurus di bawah 1 km, dan **18 pasangan non-overlapping** direkomendasikan untuk verifikasi lapangan (potensi efisiensi **58 rombel** dan **72 formasi guru**).";
        }

        if (in_array('bps', $topics, true)) {
            $sections[] = $this->formatBpsChunk($c);
        }

        if ($jenjangs !== [] && $sections === []) {
            $sections[] = $this->formatJenjangChunk($c, $jenjangs);
        }

        if ($sections === []) {
            $sections[] = "**Ringkasan Data Pendidikan Kabupaten Banjarnegara (Semester {$c['semester']}):**\n"
                . "- **Satuan Pendidikan:** " . number_format($c['total_sekolah'], 0, ',', '.') . " sekolah di 20 kecamatan (`656 PAUD`, `649 SD`, `310 TK`, `210 MI`, `101 SMP`, `41 MTs`, `29 PKBM`, `27 SMK`, `19 MA`, `12 SMA`).\n"
                . "- **Peserta Didik:** " . number_format($c['total_siswa'], 0, ',', '.') . " siswa (" . number_format($c['total_siswa_umum'], 0, ',', '.') . " siswa sekolah umum + " . number_format($c['total_siswa_madrasah'], 0, ',', '.') . " siswa madrasah).\n"
                . "- **Pendidik (Dapodik):** " . number_format($c['total_guru'], 0, ',', '.') . " guru sekolah umum (**{$c['pct_sertifikasi']}%** bersertifikat). Data guru per-NPSN untuk **210 MI** dan **41 MTs** belum tersedia di tabel Dapodik sehingga rasio sekolah umum dihitung terpisah (**{$c['rasio_umum']}:1** vs rasio gabungan **{$c['rasio_gabungan']}:1**).";
        }

        return implode("\n\n", $sections);
    }

    /**
     * @param  array<int, string>  $topics
     * @param  array<int, string>  $kecs
     * @param  array<int, string>  $jenjangs
     * @return array<int, string>
     */
    private function suggestFollowUps(array $topics, array $kecs, array $jenjangs): array
    {
        if (in_array('madrasah_gap', $topics, true)) {
            return [
                'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                'Mengapa berkas CSV MI dari Kemenag hanya valid pada tahun ajaran 2018/2019?',
                'Bagaimana dampak placeholder 1 rombel pada MI dan MTs terhadap analisis rombel padat?',
            ];
        }

        if ($kecs !== []) {
            $k = $kecs[0];

            return [
                "Mengapa rasio gabungan di Kecamatan {$k} berbeda dengan rasio sekolah umumnya?",
                "Apa saja rekomendasi kritis untuk Kepala Dinas tahun ini?",
                "Kecamatan mana yang memiliki rasio siswa per guru tertinggi?",
            ];
        }

        return [
            'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
            'Berapa rasio siswa per guru riil sekolah umum jika MI dan MTs dipisahkan?',
            'Apa 3 keputusan kritis untuk Kepala Dinas pada tahun ini?',
        ];
    }
}
