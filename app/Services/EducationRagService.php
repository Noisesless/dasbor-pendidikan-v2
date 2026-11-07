<?php

namespace App\Services;

use App\Support\KecamatanGeo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Layanan Asisten Analitik Data Pendidikan Kabupaten Banjarnegara.
 *
 * Menggunakan arsitektur Hybrid RAG:
 * 1. Online RAG: Menggunakan Gemini API dengan multi-model cascade (mencoba model cadangan jika model utama gagal/timeout/kuota habis).
 * 2. Offline RAG: Jika seluruh model online atau kuota API tidak tersedia, jawaban dirumuskan secara dinamis menggunakan query basis data
 *    lokal terindeks dengan gaya narasi analitik yang luwes layaknya AI konsultan kebijakan pendidikan.
 */
class EducationRagService
{
    private const BASELINE_SEMESTER = '20252';

    /**
     * Model Gemini yang didukung untuk generateContent dengan API Key Google AI Studio.
     * Diuji secara berurutan jika terjadi kegagalan atau kehabisan kuota pada model sebelumnya.
     */
    private const CANDIDATE_MODELS = [
        'gemini-3.5-flash-lite',
        'gemini-flash-lite-latest',
        'gemini-3.1-flash-lite',
        'gemma-4-26b-a4b-it',
        'gemini-3.5-flash',
        'gemini-3.8-flash',
    ];

    /**
     * Proses pertanyaan pengguna.
     *
     * @param  string  $question
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{answer: string, sources: array<int, string>, follow_ups: array<int, string>}
     */
    public function ask(string $question, array $history = []): array
    {
        $question = trim($question);
        $retrieval = $this->retrieveContext($question);

        if ($retrieval['out_of_scope']) {
            return [
                'answer' => "Pertanyaan tersebut berada di luar lingkup data aplikasi **Dasbor Pendidikan Kabupaten Banjarnegara**.\n\nSaya hanya dapat menjawab pertanyaan seputar data satuan pendidikan, peserta didik, pendidik, sarana prasarana, peta sebaran wilayah, indikator makro (APK/APM/HLS/RLS/IPM), serta **Rekomendasi Kepala Dinas** (termasuk analisis mendalam mengenai pemisahan data guru **MI dan MTs**).",
                'sources' => ['Batasan Lingkup Aplikasi'],
                'follow_ups' => [
                    'Mengapa analisis di halaman Rekomendasi belum sempurna terkait data guru MI dan MTs?',
                    'Bagaimana perbandingan rasio guru per kecamatan jika siswa MI dan MTs dipisahkan?',
                    'Apa 3 keputusan kritis untuk Kepala Dinas pada tahun ini?',
                ],
            ];
        }

        $apiKey = (string) config('services.gemini.api_key', '');

        // 1. Coba Online RAG dengan multi-model cascade jika API key tersedia
        if ($apiKey !== '') {
            $onlineAnswer = $this->callGeminiCascade($question, $retrieval['context_text'], $history, $apiKey);
            if ($onlineAnswer !== null) {
                return [
                    'answer' => $onlineAnswer,
                    'sources' => $retrieval['sources'],
                    'follow_ups' => $retrieval['follow_ups'],
                ];
            }
        }

        // 2. Fallback mulus ke Offline RAG menggunakan query analitis dinamis
        return [
            'answer' => $this->synthesizeOffline($question, $retrieval),
            'sources' => $retrieval['sources'],
            'follow_ups' => $retrieval['follow_ups'],
        ];
    }

    /**
     * Kumpulkan konteks faktual (RAG Chunks) dari database sesuai entitas & topik pertanyaan.
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
            $sources[] = 'Profil Wilayah Kecamatan';
            foreach ($matchedKecamatan as $kec) {
                $chunks[] = $this->formatKecamatanChunk($core['kecamatan'][$kec]);
            }
        }

        if ($matchedJenjang !== []) {
            $sources[] = 'Statistik Jenjang Pendidikan';
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
     * Panggil Gemini API dengan mekanisme fallback beruntun (cascade).
     * Jika satu model mengalami timeout, kuota habis (429), atau error, otomatis beralih ke model berikutnya.
     *
     * @param  array<int, array{role: string, text: string}>  $history
     */
    private function callGeminiCascade(string $question, string $contextText, array $history, string $apiKey): ?string
    {
        $systemPrompt = "Anda adalah Asisten Analitik Data Pendidikan Kabupaten Banjarnegara pada aplikasi Dasbor Pendidikan (pendidikan.sistemdata.id).\n"
            . "PANDUAN MENJAWAB:\n"
            . "1. Jawab HANYA berdasarkan [FAKTA] yang diberikan di bawah ini dalam Bahasa Indonesia yang lugas, profesional, mengalir alami, dan berbasis data angka pasti.\n"
            . "2. Jika pengguna menanyakan tentang halaman Rekomendasi, rasio guru per kecamatan, data madrasah, MI, atau MTs:\n"
            . "   - Uraikan secara transparan bahwa analisis gabungan dipengaruhi oleh belum tersedianya rekaman guru per-NPSN pada 210 MI (23.335 siswa) dan 41 MTs (10.099 siswa) di tabel Dapodik (teacher_stats), serta duplikasi berkas CSV MI dengan MTs pada 7 dari 9 tahun ajaran di Satu Data Kemenag.\n"
            . "   - Sajikan perbandingan konkret antara Rasio Gabungan (19,5:1) vs Rasio Riil Sekolah Umum Non-Madrasah (15,8:1) dan sebutkan kecamatan yang mengalami distorsi rasio terbesar (seperti Pagedongan, Rakit, Banjarnegara, Bawang, Wanayasa, Mandiraja).\n"
            . "3. Jangan menyebutkan istilah teknis backend seperti 'mode online', 'mode offline', 'prompt', 'RAG', 'database query', atau nama model AI. Bersikaplah layaknya asisten eksekutif cerdas yang menguasai data daerah.\n\n"
            . "=== BASIS DATA FAKTUAL RESMI APLIKASI ===\n"
            . $contextText;

        $contents = [];
        foreach (array_slice($history, -4) as $msg) {
            $role = ($msg['role'] ?? '') === 'model' ? 'model' : 'user';
            $text = trim((string) ($msg['text'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => mb_substr($text, 0, 1000)]],
                ];
            }
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $question]],
        ];

        $preferredModel = (string) config('services.gemini.model', 'gemini-3.5-flash-lite');
        $modelList = array_values(array_unique(array_merge([$preferredModel], self::CANDIDATE_MODELS)));

        foreach ($modelList as $model) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $response = Http::timeout(6)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 850,
                        ],
                    ]);

                if ($response->successful()) {
                    $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (is_string($text) && trim($text) !== '') {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                // Lanjut ke model kandidat berikutnya jika ada timeout atau error
                continue;
            }
        }

        return null;
    }

    /**
     * Mesin penyintesis jawaban Offline RAG.
     * Menggunakan query data riil dengan susunan bahasa analitis yang luwes dan alami (human-like AI).
     *
     * @param  array<string, mixed>  $retrieval
     */
    private function synthesizeOffline(string $question, array $retrieval): string
    {
        $qLower = mb_strtolower($question);
        $c = $retrieval['core'];
        $topics = $retrieval['topics'];
        $kecs = $retrieval['matched_kecamatan'];
        $schools = $retrieval['matched_schools'];

        // Skenario 1: Pencarian sekolah atau NPSN tertentu
        if ($schools !== []) {
            $s = $schools[0];

            return "Berdasarkan basis data pokok pendidikan Kabupaten Banjarnegara, berikut profil dari **{$s['nama_sekolah']}**:\n\n"
                . "- **Nomor Pokok Sekolah Nasional (NPSN):** `{$s['npsn']}`\n"
                . "- **Jenjang & Status:** {$s['jenjang']} ({$s['status']})\n"
                . "- **Wilayah Kecamatan:** {$s['kecamatan']}\n"
                . "- **Akreditasi:** **{$s['akreditasi']}**\n"
                . "- **Peserta Didik:** **{$s['siswa']}** siswa terbagi dalam **{$s['rombel']}** rombongan belajar\n"
                . "- **Tenaga Pendidik (Dapodik):** **{$s['guru']}** guru (" . ($s['sertifikasi'] > 0 ? "{$s['sertifikasi']} bersertifikat pendidik" : 'belum ada sertifikasi tercatat') . ")\n"
                . "- **Kontak Resmi:** `{$s['telepon']}`";
        }

        // Skenario 2: Pertanyaan spesifik tentang perbandingan rasio guru per kecamatan jika MI dan MTs dipisahkan
        $isCompareKecamatan = str_contains($qLower, 'perbandingan rasio')
            || (str_contains($qLower, 'rasio') && str_contains($qLower, 'kecamatan') && (str_contains($qLower, 'pisah') || str_contains($qLower, 'madrasah') || str_contains($qLower, 'mi') || str_contains($qLower, 'mts')));

        if ($isCompareKecamatan) {
            return $this->synthesizeKecamatanComparison($c);
        }

        // Skenario 3: Penjelasan mengapa berkas CSV MI dari Kemenag hanya valid pada TA 2018/2019
        if (str_contains($qLower, '2018/2019') || (str_contains($qLower, 'csv') && str_contains($qLower, 'kemenag')) || (str_contains($qLower, 'valid') && str_contains($qLower, 'kemenag'))) {
            return "Berkas CSV agregat Madrasah Ibtidaiyah (MI) dari portal Satu Data Kemenag hanya valid pada tahun ajaran **2018/2019** karena temuan teknis berikut:\n\n"
                . "1. **Anomali Duplikasi Berkas (Identical Content):** Pada 7 dari 9 tahun ajaran (yaitu TA `2017/2018` serta TA `2019/2020` sampai dengan `2024/2025`), berkas CSV unduhan resmi untuk jenjang MI ternyata memiliki isi baris data angka yang persis sama (duplikat) dengan berkas CSV jenjang MTs.\n"
                . "2. **Tahun Ajaran 2018/2019 sebagai Satu-satunya Data Unik:** Hanya pada TA `2018/2019` berkas CSV MI memiliki nilai yang independen dan valid, mencatat **" . number_format($c['mi']['guru_kemenag_2018'], 0, ',', '.') . " guru** di 20 kecamatan.\n"
                . "3. **Dampak pada Kebijakan Daerah:** Karena data mutakhir 2024/2025 mengalami anomali duplikasi, perhitungan rasio kebutuhan guru daerah tidak dapat memakai data agregat CSV Kemenag, dan Pemerintah Kabupaten Banjarnegara perlu melakukan integrasi API langsung (EMIS Kemenag) per-NPSN.";
        }

        // Skenario 4: Dampak placeholder 1 rombel pada madrasah terhadap analisis rombel padat
        if (str_contains($qLower, 'placeholder') || (str_contains($qLower, 'rombel') && (str_contains($qLower, 'padat') || str_contains($qLower, 'overload') || str_contains($qLower, '32')))) {
            return "Dampak nilai placeholder `1` rombel pada madrasah (MI, MTs, MA) terhadap analisis kepadatan rombongan belajar:\n\n"
                . "1. **Distorsi Statistik Rombel Padat:** Di dalam basis data awal, terdapat **{$c['rombel_padat_madrasah_placeholder']} madrasah** yang jumlah rombelnya tercatat default `1` rombel padahal jumlah siswanya puluhan hingga ratusan. Hal ini memicu deteksi semu seolah-olah seluruh madrasah mengalami kepadatan ekstrem (>32 siswa/rombel).\n"
                . "2. **Kondisi Riil Sekolah Umum:** Setelah anomali placeholder madrasah dipisahkan, hanya ditemukan **{$c['rombel_padat_umum']} sekolah umum** di Banjarnegara yang benar-benar mengalami kepadatan rombel riil (>32 siswa/rombel).\n"
                . "3. **Arah Kebijakan:** Penambahan ruang kelas baru (RKB) atau penambahan guru diarahkan secara tepat sasaran pada **{$c['rombel_padat_umum']} sekolah umum** tersebut, sementara data madrasah dikoordinasikan lebih lanjut dengan Kantor Kemenag.";
        }

        // Skenario 5: Penjelasan keterbatasan data guru MI/MTs pada halaman /rekomendasi
        if (in_array('madrasah_gap', $topics, true) || (str_contains($qLower, 'rekomendasi') && (str_contains($qLower, 'belum sempurna') || str_contains($qLower, 'kekurangan data') || str_contains($qLower, 'guru')))) {
            return "Analisis di halaman **Rekomendasi Kepala Dinas** (`/rekomendasi`) belum sempurna terutama karena **kekosongan rekaman guru MI dan MTs pada level satuan pendidikan (per-NPSN)** di tabel operasional Dapodik, serta kendala teknis pada berkas Satu Data Kemenag:\n\n"
                . "1. **Kekosongan Guru Per-NPSN di Tabel Dapodik (`teacher_stats`):**\n"
                . "   - Terdapat **{$c['mi']['sekolah']} Madrasah Ibtidaiyah (MI)** dengan **" . number_format($c['mi']['siswa'], 0, ',', '.') . " siswa** dan **{$c['mts']['sekolah']} Madrasah Tsanawiyah (MTs)** dengan **" . number_format($c['mts']['siswa'], 0, ',', '.') . " siswa** (total **" . number_format($c['mi']['siswa'] + $c['mts']['siswa'], 0, ',', '.') . " siswa**).\n"
                . "   - Seluruh siswa madrasah tersebut masuk ke dalam pembilang perhitungan peserta didik daerah, namun jumlah guru di tabel `teacher_stats` masih **0 guru** karena kewenangan pendataan madrasah berada di bawah sistem EMIS Kementerian Agama, bukan Dapodik Kemendikdasmen.\n\n"
                . "2. **Anomali Berkas Agregat Kemenag:**\n"
                . "   - Pada berkas CSV Kemenag tingkat kecamatan, data MTs mencatat **{$c['mts']['guru_kemenag_2024']} guru** (TA 2024/2025), namun tanpa rincian per-sekolah.\n"
                . "   - Berkas CSV MI mengalami **kerusakan duplikasi** (isinya identik dengan MTs) pada 7 dari 9 tahun ajaran (`2017/2018` dan `2019/2020` s/d `2024/2025`). Data agregat guru MI yang valid terakhir hanya pada TA `2018/2019` sebanyak **" . number_format($c['mi']['guru_kemenag_2018'], 0, ',', '.') . " guru**.\n\n"
                . "3. **Distorsi pada Indikator Kebijakan:**\n"
                . "   - **Rasio Siswa/Guru:** Rasio gabungan kabupaten tampak tinggi di angka **{$c['rasio_gabungan']}:1** (seolah-olah 9 kecamatan defisit guru di atas 20:1). Padahal jika dihitung khusus **sekolah umum non-madrasah**, rasio riilnya hanya **{$c['rasio_umum']}:1** (hanya Kecamatan Batur yang riil di atas 20:1).\n"
                . "   - **Kepadatan Rombel:** Sebanyak **{$c['rombel_padat_madrasah_placeholder']} madrasah** terdeteksi overload karena di database masih terisi nilai placeholder `1` rombel per madrasah.\n\n"
                . "**Langkah Rekomendasi:** Dinas Pendidikan perlu menjalin MoU integrasi data EMIS dengan Kantor Kemenag Banjarnegara serta memisahkan analisis sekolah umum dan madrasah dalam pengambilan keputusan formasi guru.";
        }

        // Skenario 6: Tiga keputusan kritis untuk Kepala Dinas
        if (in_array('rekomendasi', $topics, true) && (str_contains($qLower, '3') || str_contains($qLower, 'tiga') || str_contains($qLower, 'kritis') || str_contains($qLower, 'keputusan'))) {
            return "Berdasarkan sintesis data Dapodik, Walidata, dan BPS pada halaman `/rekomendasi`, terdapat **tiga keputusan kritis** yang perlu diambil oleh Kepala Dinas pada tahun anggaran mendatang:\n\n"
                . "1. **Rehabilitasi 534 Ruang Kelas Rusak Sedang/Berat (Prioritas Kritis - Skor 96):**\n"
                . "   - Terdapat **1.187 dari 6.235 ruang kelas (19,0%)** yang tidak layak pakai, dengan 534 ruang mengalami kerusakan sedang hingga berat serta defisit **342 ruang kelas** di jenjang SMA dan SMK.\n"
                . "   - Dibutuhkan alokasi anggaran sekitar **Rp 366,55 miliar** yang diusulkan melalui DAK Fisik dan DAU Pendidikan 2027 secara bertahap.\n\n"
                . "2. **Integrasi Data EMIS Kemenag & Verifikasi Blank Spot (Prioritas Kritis - Skor 92):**\n"
                . "   - Menutup kekosongan data guru pada **210 MI** dan **41 MTs** dengan melakukan kerja sama resmi serah data EMIS per-NPSN bersama Kankemenag Banjarnegara, serta memverifikasi **{$c['tanpa_data_siswa']} sekolah** yang belum memiliki rekaman data siswa aktif.\n\n"
                . "3. **Redistribusi dan Formasi Guru Berbasis Rasio Riil (Prioritas Kritis - Skor 88):**\n"
                . "   - Menggunakan rasio sekolah umum (**{$c['rasio_umum']}:1**) sebagai dasar pemerataan guru PNS/PPPK, bukan rasio gabungan yang terdistorsi oleh data madrasah.\n"
                . "   - Memfokuskan penempatan formasi guru baru pada kecamatan dengan rasio sekolah umum tertinggi (seperti Batur di 23,8:1) dan mendorong sertifikasi guru yang saat ini baru mencapai **{$c['pct_sertifikasi']}%**.";
        }

        // Skenario 7: Analisis Sarana Prasarana & Kerusakan Ruang Kelas
        if (in_array('sarana', $topics, true) || str_contains($qLower, 'ruang kelas') || str_contains($qLower, 'rusak') || str_contains($qLower, 'kondisi sarana') || str_contains($qLower, 'anggaran')) {
            return "Berdasarkan data audit sarana prasarana pendidikan Kabupaten Banjarnegara (`/analisis/sarana`):\n\n"
                . "- **Total Ruang Kelas:** Tercatat **6.235 ruang kelas** di seluruh satuan pendidikan.\n"
                . "- **Kondisi Kerusakan:** Sebanyak **1.187 ruang (19,0%)** mengalami kerusakan, dengan **534 ruang kelas** berada pada kondisi **rusak sedang hingga berat**.\n"
                . "- **Defisit Ruang Kelas:** Terdapat defisit sebanyak **342 ruang kelas** terhadap rasio ideal 28 siswa/ruang, paling banyak dialami jenjang SMA dan SMK.\n"
                . "- **Estimasi Kebutuhan Biaya:** Diperkirakan membutuhkan anggaran **Rp 366,55 miliar** yang dapat dialokasikan bertahap melalui DAK Fisik dan DAU Pendidikan 2027.";
        }

        // Skenario 8: Analisis Regrouping SD
        if (in_array('regrouping', $topics, true) || str_contains($qLower, 'regrouping') || str_contains($qLower, 'sd kecil') || str_contains($qLower, 'kurang dari 60')) {
            return "Berdasarkan analisis kandidat regrouping sekolah dasar (`/analisis/regrouping`):\n\n"
                . "- **Kandidat SD Kecil:** Teridentifikasi **114 SD Negeri** yang memiliki jumlah peserta didik **di bawah 60 siswa** (rata-rata di bawah 10 murid per kelas).\n"
                . "- **Kedekatan Geografis:** Ditemukan **24 pasangan SD** berjarak lurus di bawah 1 km, dan **18 pasangan non-overlapping** direkomendasikan untuk verifikasi lapangan teknis.\n"
                . "- **Potensi Efisiensi:** Regrouping ini berpotensi menghemat **58 rombongan belajar** dan mengefisienkan alokasi **72 formasi guru** untuk dipindahkan ke sekolah yang kekurangan pendidik.";
        }

        // Skenario 9: Indikator BPS (IPM, RLS, HLS, Partisipasi Sekolah)
        if (in_array('bps', $topics, true) || str_contains($qLower, 'ipm') || str_contains($qLower, 'rls') || str_contains($qLower, 'hls') || str_contains($qLower, 'partisipasi')) {
            return "Berdasarkan rilis resmi Badan Pusat Statistik (BPS) Kabupaten Banjarnegara (2018-2024):\n\n"
                . "- **Indeks Pembangunan Manusia (IPM):** Berada di angka **69,57** (kategori sedang, meningkat konsisten dari 67,43 pada 2018).\n"
                . "- **Rata-rata Lama Sekolah (RLS):** Berada di angka **6,88 tahun** (setara kelas 7 SMP), menunjukkan tantangan penuntasan wajib belajar 9 tahun.\n"
                . "- **Harapan Lama Sekolah (HLS):** Mencapai **11,71 tahun** (mendekati lulus pendidikan menengah atas).\n"
                . "- **Angka Partisipasi Sekolah (APS):**\n"
                . "  - Usia 7-12 tahun (SD/MI): **99,42%**\n"
                . "  - Usia 13-15 tahun (SMP/MTs): **96,15%**\n"
                . "  - Usia 16-18 tahun (SMA/SMK/MA): **71,84%**";
        }

        // Skenario 10: Pertanyaan tentang profil kecamatan tertentu
        if ($kecs !== []) {
            $k = $c['kecamatan'][$kecs[0]];

            return "Berikut ringkasan profil analitik pendidikan untuk **Kecamatan {$k['nama']}**:\n\n"
                . "- **Jumlah Satuan Pendidikan:** {$k['sekolah']} sekolah (termasuk **{$k['mi_sekolah']} MI** dan **{$k['mts_sekolah']} MTs**)\n"
                . "- **Total Peserta Didik:** " . number_format($k['siswa_total'], 0, ',', '.') . " siswa\n"
                . "  - Siswa Sekolah Umum: **" . number_format($k['siswa_umum'], 0, ',', '.') . "** siswa\n"
                . "  - Siswa Madrasah (MI, MTs, MA): **" . number_format($k['siswa_mi'] + $k['siswa_mts'] + $k['siswa_ma'], 0, ',', '.') . "** siswa\n"
                . "- **Pendidik Terdata di Dapodik:** " . number_format($k['guru_dapodik'], 0, ',', '.') . " guru (" . ($k['sertifikasi_pct'] > 0 ? "{$k['sertifikasi_pct']}% bersertifikat" : 'belum ada sertifikasi') . ")\n"
                . "- **Rasio Siswa per Guru:**\n"
                . "  - **Rasio Gabungan:** **{$k['rasio_gabungan']}:1** (terhitung dengan siswa madrasah)\n"
                . "  - **Rasio Riil Sekolah Umum:** **{$k['rasio_umum']}:1** (tanpa madrasah)\n"
                . "- **Siswa Belajar di Luar Usia Standar:** {$k['overage_pct']}%";
        }

        // Skenario 11: Ringkasan menyeluruh eksekutif
        return "Berdasarkan basis data analitik pendidikan Kabupaten Banjarnegara (Semester {$c['semester']}):\n\n"
            . "- **Satuan Pendidikan:** Tersebar **" . number_format($c['total_sekolah'], 0, ',', '.') . " sekolah** di 20 kecamatan (`656 PAUD`, `649 SD`, `310 TK`, `210 MI`, `101 SMP`, `41 MTs`, `29 PKBM`, `27 SMK`, `19 MA`, `12 SMA`).\n"
            . "- **Peserta Didik:** Total **" . number_format($c['total_siswa'], 0, ',', '.') . " siswa** (" . number_format($c['total_siswa_umum'], 0, ',', '.') . " di sekolah umum dan " . number_format($c['total_siswa_madrasah'], 0, ',', '.') . " di madrasah).\n"
            . "- **Pendidik & Rasio:** Terdapat **" . number_format($c['total_guru'], 0, ',', '.') . " guru** di Dapodik dengan tingkat sertifikasi **{$c['pct_sertifikasi']}%**. Rasio sekolah umum berada di angka **{$c['rasio_umum']}:1**, sedangkan rasio gabungan berada di angka **{$c['rasio_gabungan']}:1** karena data guru madrasah belum masuk ke Dapodik.\n"
            . "- **Sarana & Prasarana:** Sebanyak **1.187 ruang kelas (19,0%)** memerlukan perbaikan dengan defisit 342 ruang di jenjang menengah.\n\n"
            . "Ada bagian data atau wilayah kecamatan tertentu yang ingin Anda telusuri lebih mendalam?";
    }

    /**
     * Sintesis tabel komparasi 20 kecamatan saat siswa madrasah dipisahkan dari perhitungan rasio guru.
     */
    private function synthesizeKecamatanComparison(array $c): string
    {
        $kecRows = $c['kecamatan'];

        // Urutkan berdasarkan selisih penurunan terbesar
        uasort($kecRows, function ($a, $b) {
            $diffA = $a['rasio_gabungan'] - $a['rasio_umum'];
            $diffB = $b['rasio_gabungan'] - $b['rasio_umum'];

            return $diffB <=> $diffA;
        });

        $topLines = [];
        $no = 1;
        foreach (array_slice($kecRows, 0, 10, true) as $k) {
            $selisih = round($k['rasio_gabungan'] - $k['rasio_umum'], 1);
            $madrasah = $k['siswa_mi'] + $k['siswa_mts'] + $k['siswa_ma'];
            $topLines[] = "| {$no} | **{$k['nama']}** | " . number_format($madrasah, 0, ',', '.') . " | **{$k['rasio_gabungan']}:1** | **{$k['rasio_umum']}:1** | -{$selisih} poin |";
            $no++;
        }

        return "Ketika peserta didik **MI dan MTs (beserta MA)** dipisahkan dari perhitungan pembagi guru Dapodik, terlihat jelas bahwa **tingginya rasio siswa per guru di sebagian besar kecamatan merupakan dampak semu** akibat ketiadaan data guru madrasah di Dapodik:\n\n"
            . "| No | Kecamatan | Siswa Madrasah | Rasio Gabungan | Rasio Sekolah Umum | Dampak Selisih |\n"
            . "|:--:|:---|:---:|:---:|:---:|:---:|\n"
            . implode("\n", $topLines) . "\n\n"
            . "**Temuan Analitik Penting bagi Kebijakan:**\n"
            . "1. **Distorsi Terbesar:** Terjadi di **Kecamatan Pagedongan** (rasio gabungan 23,0:1 turun menjadi **14,8:1**, selisih -8,2 poin) dan **Kecamatan Rakit** (21,8:1 turun menjadi **14,6:1**, selisih -7,2 poin) karena jumlah siswa madrasah yang besar tidak memiliki rekaman guru di Dapodik.\n"
            . "2. **Kecamatan Melebihi Batas 20:1:** Pada rasio gabungan, tampak ada **9 kecamatan** yang melebihi batas 20 siswa/guru. Namun setelah siswa madrasah dipisahkan, **hanya 1 kecamatan yang riil melebihi 20:1**, yaitu **Kecamatan Batur (23,8:1)** yang membutuhkan formasi guru tambahan.\n"
            . "3. **Rasio Kabupaten:** Secara keseluruhan daerah, rasio gabungan **{$c['rasio_gabungan']}:1** terkoreksi menjadi **{$c['rasio_umum']}:1** untuk sekolah umum di bawah binaan Dinas Pendidikan.";
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
            'tanpa_data_siswa' => $tanpaDataSiswa,
            'tanpa_telepon' => $tanpaTelepon,
            'akreditasi_rendah' => $akreditasiRendah,
            'kecamatan' => $kecamatan,
            'jenjang_stats' => $jenjangStats,
        ];
    }

    private function detectTopics(string $q): array
    {
        $topics = [];
        $rules = [
            'madrasah_gap' => ['mts', 'mi ', ' mi', 'madrasah', 'kemenag', 'emis', 'kekurangan data', 'belum sempurna', 'blank spot', 'duplikat', 'pisah'],
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
            . "- Rasio Sekolah Umum Non-Madrasah: {$c['rasio_umum']}:1 | Rasio Kasar Gabungan: {$c['rasio_gabungan']}:1 | Estimasi Lengkap dengan Kemenag: {$c['rasio_estimasi_lengkap']}:1.\n"
            . "- Siswa di Luar Usia Jenjang (Overage): " . number_format($c['siswa_overage'], 0, ',', '.') . " siswa ({$c['pct_overage']}%).\n"
            . "- Sekolah Akreditasi C/Belum: " . number_format($c['akreditasi_rendah'], 0, ',', '.') . " sekolah.\n"
            . "- Sekolah Tanpa Data Siswa: {$c['tanpa_data_siswa']} sekolah | Tanpa Nomor Telepon: {$c['tanpa_telepon']} sekolah.";
    }

    private function formatMadrasahGapChunk(array $c): string
    {
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
            . "   - Distorsi Rasio Siswa/Guru: Rasio gabungan tampak sebesar {$c['rasio_gabungan']}:1. Padahal jika dihitung khusus sekolah umum non-madrasah (PAUD, TK, SD, SMP, SMA, SMK, PKBM), rasionya adalah {$c['rasio_umum']}:1. Jika estimasi " . number_format($c['estimasi_guru_madrasah'], 0, ',', '.') . " guru agregat Kemenag digabungkan, rasio kabupaten adalah {$c['rasio_estimasi_lengkap']}:1.\n"
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
            . "8. [SEDANG - Pemerataan & Kualitas Data] Peningkatan Rata-rata Lama Sekolah (RLS 6,88 tahun vs HLS 11,71 tahun) lewat PKBM Paket B/C dan kelengkapan nomor telepon ({$c['tanpa_telepon']} sekolah belum terisi).";
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
