<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Halaman rekomendasi kebijakan untuk Kepala Dinas Pendidikan.
 *
 * Seluruh angka diturunkan dari tabel yang sudah ada (Dapodik, Walidata,
 * BPS) lewat perhitungan yang sama dengan halaman analisis terkait, supaya
 * tidak ada dua versi kebenaran di aplikasi ini.
 */
class RekomendasiController extends AdvancedAnalysisController
{
    public function index(Request $request)
    {
        $fakta = $this->kumpulkanFakta();
        $rekomendasi = $this->susunRekomendasi($fakta);

        return view('dashboard.rekomendasi', [
            'fakta' => $fakta,
            'rekomendasi' => $rekomendasi,
            'sorotan' => array_slice($rekomendasi, 0, 3),
            'semester' => self::BASELINE_SEMESTER,
        ]);
    }

    // =====================================================================
    // 1. Fakta — semua angka yang dipakai rekomendasi
    // =====================================================================
    private function kumpulkanFakta(): array
    {
        $siswa = DB::table('student_stats')->where('semester', self::BASELINE_SEMESTER)->get();
        $guru = DB::table('teacher_stats')->where('semester', self::BASELINE_SEMESTER)->get();

        $totalSiswa = (int) $siswa->sum('total_siswa');
        $totalGuru = (int) $guru->sum('jumlah_guru');
        $totalSertifikasi = (int) $guru->sum('jumlah_sertifikasi');
        $totalUsiaSesuai = (int) $siswa->sum('usia_sesuai');

        $madrasahJenjang = ['MI', 'MTS', 'MA'];
        $siswaUmumRows = $siswa->reject(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true));
        $totalSiswaUmum = (int) $siswaUmumRows->sum('total_siswa');

        $miSiswa = (int) $siswa->where('jenjang', 'MI')->sum('total_siswa');
        $mtsSiswa = (int) $siswa->where('jenjang', 'MTS')->sum('total_siswa');
        $maSiswa = (int) $siswa->where('jenjang', 'MA')->sum('total_siswa');

        $miSekolah = (int) DB::table('schools')->where('jenjang', 'MI')->count();
        $mtsSekolah = (int) DB::table('schools')->where('jenjang', 'MTS')->count();
        $maSekolah = (int) DB::table('schools')->where('jenjang', 'MA')->count();

        $miGuruDapodik = (int) $guru->where('jenjang', 'MI')->sum('jumlah_guru');
        $mtsGuruDapodik = (int) $guru->where('jenjang', 'MTS')->sum('jumlah_guru');
        $maGuruDapodik = (int) $guru->where('jenjang', 'MA')->sum('jumlah_guru');

        // Data agregat guru Kemenag dari berkas Satu Data Kemenag (bukan per-NPSN):
        // - MI: hanya tahun ajaran 2018/2019 yang valid (1.563 guru di 202 MI); 7 TA lain duplikat dengan MTs.
        // - MTs: tahun ajaran 2024/2025 mencatat 745 guru (165 negeri + 580 swasta di 41 MTs).
        // - MA: tahun ajaran 2024/2025 mencatat 363 guru (138 negeri + 225 swasta di 19 MA).
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

        $padat = $siswa->filter(fn ($r) => (int) $r->jumlah_rombel > 0
            && (int) $r->total_siswa / (int) $r->jumlah_rombel > 32);
        $padatUmum = $siswaUmumRows->filter(fn ($r) => (int) $r->jumlah_rombel > 0
            && (int) $r->total_siswa / (int) $r->jumlah_rombel > 32);
        $padatMadrasahPlaceholder = $siswa->filter(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true)
            && (int) $r->jumlah_rombel <= 1 && (int) $r->total_siswa > 32);
        $kecil = $siswa->filter(fn ($r) => (int) $r->total_siswa > 0 && (int) $r->total_siswa < 60);

        $akreditasiRendah = DB::table('schools')
            ->where(fn ($q) => $q->whereNull('akreditasi')->orWhere('akreditasi', '')->orWhere('akreditasi', 'C'));

        $sekolahPerKec = DB::table('schools')->selectRaw('kecamatan, count(*) as n')
            ->groupBy('kecamatan')->pluck('n', 'kecamatan');

        $siswaPerKec = $siswa->groupBy('kecamatan');
        $guruPerKec = $guru->groupBy('kecamatan');
        $padatPerKec = $padat->groupBy('kecamatan')->map->count();
        $kecilPerKec = $kecil->groupBy('kecamatan')->map->count();
        $akreditasiPerKec = (clone $akreditasiRendah)->selectRaw('kecamatan, count(*) as n')
            ->groupBy('kecamatan')->pluck('n', 'kecamatan');

        $kecamatan = [];
        foreach ($siswaPerKec as $kec => $rows) {
            $s = (int) $rows->sum('total_siswa');
            $sUmum = (int) $rows->reject(fn ($r) => in_array($r->jenjang, $madrasahJenjang, true))->sum('total_siswa');
            $sMiMts = (int) $rows->whereIn('jenjang', ['MI', 'MTS'])->sum('total_siswa');
            $sesuai = (int) $rows->sum('usia_sesuai');
            $g = isset($guruPerKec[$kec]) ? (int) $guruPerKec[$kec]->sum('jumlah_guru') : 0;
            $sert = isset($guruPerKec[$kec]) ? (int) $guruPerKec[$kec]->sum('jumlah_sertifikasi') : 0;

            $kecamatan[$kec] = [
                'nama' => $kec,
                'siswa' => $s,
                'siswa_umum' => $sUmum,
                'siswa_mi_mts' => $sMiMts,
                'guru' => $g,
                'rasio' => $g > 0 ? round($s / $g, 1) : null,
                'rasio_umum' => $g > 0 ? round($sUmum / $g, 1) : null,
                'sertifikasi' => $g > 0 ? round($sert / max(1, $g) * 100, 1) : null,
                'overage' => $s > 0 ? round((1 - $sesuai / $s) * 100, 1) : null,
                'rombel_padat' => (int) ($padatPerKec[$kec] ?? 0),
                'sekolah_kecil' => (int) ($kecilPerKec[$kec] ?? 0),
                'akreditasi_rendah' => (int) ($akreditasiPerKec[$kec] ?? 0),
                'sekolah' => (int) ($sekolahPerKec[$kec] ?? 0),
            ];
        }

        // Tandai ambang batas yang terlampaui per kecamatan.
        foreach ($kecamatan as &$k) {
            $faktor = [];
            if ($k['rasio'] !== null && $k['rasio'] > 20) {
                $faktor['Rasio siswa/guru > 20'] = true;
            }
            if ($k['overage'] !== null && $k['overage'] > 20) {
                $faktor['> 20% siswa di luar usia'] = true;
            }
            if ($k['sertifikasi'] !== null && $k['sertifikasi'] < 60) {
                $faktor['Guru bersertifikat < 60%'] = true;
            }
            if ($k['sekolah'] > 0 && $k['akreditasi_rendah'] / $k['sekolah'] > 0.25) {
                $faktor['> 25% sekolah akreditasi C/belum'] = true;
            }
            if ($k['rombel_padat'] > 10) {
                $faktor['Rombel padat > 32 siswa'] = true;
            }
            if ($k['sekolah_kecil'] > 10) {
                $faktor['Sekolah < 60 siswa'] = true;
            }
            $k['faktor'] = array_keys($faktor);
            $k['jumlah_faktor'] = count($k['faktor']);
        }
        unset($k);

        $peringkat = $kecamatan;
        uasort($peringkat, fn ($a, $b) => $b['jumlah_faktor'] <=> $a['jumlah_faktor']);
        $peringkat = array_filter($peringkat, fn ($k) => $k['jumlah_faktor'] >= 2);

        // Tren murid SD dari data Dindikpora (hanya tahun yang lengkap 20 kecamatan)
        $trenSd = DB::table('regional_education_stats')
            ->where('sumber', 'dindikpora')->where('jenjang', 'SD')
            ->selectRaw('tahun_ajaran, count(distinct kecamatan) as kec,
                sum(coalesce(murid_negeri, 0) + coalesce(murid_swasta, 0)) as murid')
            ->groupBy('tahun_ajaran')->orderBy('tahun_ajaran')->get()
            ->filter(fn ($r) => (int) $r->kec >= 20 && (int) $r->murid > 0)
            ->values();

        $sarana = $this->saranaComputation(
            ['ringan' => 150, 'sedang' => 250, 'berat' => 400, 'baru' => 500],
            28
        );

        $regrouping = $this->regroupingComputation('SD', 1.0);

        $jenjangSalah = 0;
        foreach (DB::table('schools')->select('jenjang', 'nama_sekolah')->distinct()->get() as $s) {
            if ($this->jenjangTidakSesuai((string) $s->jenjang, (string) $s->nama_sekolah)) {
                $jenjangSalah++;
            }
        }

        $madrasah = DB::table('schools')->whereIn('jenjang', ['MI', 'MTS', 'MA'])->pluck('npsn');
        $punyaGuru = DB::table('teacher_stats')->where('semester', self::BASELINE_SEMESTER)
            ->distinct()->pluck('npsn');

        $punyaSiswa = $siswa->pluck('npsn')->unique();

        $bps = [];
        foreach (['hls', 'rls', 'ipm', 'miskin_pct', 'gini', 'uhh'] as $indikator) {
            $bps[$indikator] = DB::table('regional_indicators')
                ->where('wilayah', 'Banjarnegara')->where('indikator', $indikator)
                ->orderBy('tahun')->pluck('nilai', 'tahun')->toArray();
        }

        return [
            'semester' => self::BASELINE_SEMESTER,
            'semesterLabel' => $this->labelSemester(),
            'totalSiswa' => $totalSiswa,
            'totalSiswaUmum' => $totalSiswaUmum,
            'totalGuru' => $totalGuru,
            'rasioKabupaten' => $totalGuru > 0 ? round($totalSiswa / $totalGuru, 1) : 0,
            'rasioKabupatenUmum' => $totalGuru > 0 ? round($totalSiswaUmum / $totalGuru, 1) : 0,
            'rasioEstimasiLengkap' => ($totalGuru + $estimasiGuruMadrasah) > 0 ? round($totalSiswa / ($totalGuru + $estimasiGuruMadrasah), 1) : 0,
            'pctSertifikasi' => $totalGuru > 0 ? round($totalSertifikasi / $totalGuru * 100, 1) : 0,
            'pctOverage' => $totalSiswa > 0 ? round((1 - $totalUsiaSesuai / $totalSiswa) * 100, 1) : 0,
            'siswaOverage' => max(0, $totalSiswa - $totalUsiaSesuai),
            'rombelPadat' => $padat->count(),
            'rombelPadatUmum' => $padatUmum->count(),
            'rombelPadatMadrasahPlaceholder' => $padatMadrasahPlaceholder->count(),
            'rombelPadatPerJenjang' => $padat->groupBy('jenjang')->map->count()->sortDesc()->all(),
            'rombelPadatUmumPerJenjang' => $padatUmum->groupBy('jenjang')->map->count()->sortDesc()->all(),
            'sekolahKecilSd' => $regrouping['sekolahKecil'],
            'akreditasiRendah' => (clone $akreditasiRendah)->count(),
            'akreditasiRendahPerJenjang' => (clone $akreditasiRendah)
                ->selectRaw('jenjang, count(*) as n')->groupBy('jenjang')
                ->orderByDesc('n')->pluck('n', 'jenjang')->all(),
            'tanpaDataSiswa' => DB::table('schools')->whereNotIn('npsn', $punyaSiswa->all())->count(),
            'madrasahTotal' => $madrasah->count(),
            'madrasahTanpaGuru' => $madrasah->diff($punyaGuru)->count(),
            'miSekolah' => $miSekolah,
            'miSiswa' => $miSiswa,
            'miGuruDapodik' => $miGuruDapodik,
            'miGuruKemenag' => $miGuruKemenag,
            'mtsSekolah' => $mtsSekolah,
            'mtsSiswa' => $mtsSiswa,
            'mtsGuruDapodik' => $mtsGuruDapodik,
            'mtsGuruKemenag' => $mtsGuruKemenag,
            'maSekolah' => $maSekolah,
            'maSiswa' => $maSiswa,
            'maGuruDapodik' => $maGuruDapodik,
            'maGuruKemenag' => $maGuruKemenag,
            'estimasiGuruMadrasah' => $estimasiGuruMadrasah,
            'tanpaTelepon' => DB::table('schools')
                ->where(fn ($q) => $q->whereNull('telepon')->orWhere('telepon', ''))->count(),
            'jenjangSalah' => $jenjangSalah,
            'kecamatan' => $kecamatan,
            'peringkatKecamatan' => $peringkat,
            'trenSd' => $trenSd,
            'sarana' => $sarana,
            'regrouping' => $regrouping,
            'bps' => $bps,
        ];
    }

    // =====================================================================
    // 2. Rekomendasi
    // =====================================================================
    private function susunRekomendasi(array $f): array
    {
        $label = $f['semesterLabel'];
        $sarana = $f['sarana']['sarana'];
        $biaya = $f['sarana']['biaya'];

        $ruangTotal = array_sum(array_column($sarana, 'total'));
        $ruangRusak = array_sum(array_column($sarana, 'ringan'))
            + array_sum(array_column($sarana, 'sedang'))
            + array_sum(array_column($sarana, 'berat'));
        $rusakSb = array_sum(array_column($sarana, 'rusak_sedang_berat'));
        $defisit = array_sum(array_column($sarana, 'defisit'));

        $pctTidakLayak = $ruangTotal > 0 ? round($ruangRusak / $ruangTotal * 100, 1) : 0;
        $totalBiaya = $f['sarana']['totalBiaya'];

        $reg = $f['regrouping'];
        $kecKandidat = array_key_first($reg['pairsPerKecamatan'] ?? []) ?: '-';

        $kec = $f['kecamatan'];
        uasort($kec, fn ($a, $b) => ($b['rasio'] ?? 0) <=> ($a['rasio'] ?? 0));
        $rasioUrut = array_slice($kec, 0, 3, true);
        $kecTerburuk = array_key_first($rasioUrut);
        $rasioTerburuk = $rasioUrut[$kecTerburuk]['rasio'] ?? 0;
        $guruKurang = $rasioTerburuk > 0
            ? max(0, (int) ceil(($rasioUrut[$kecTerburuk]['siswa'] ?? 0) / 20) - (int) ($rasioUrut[$kecTerburuk]['guru'] ?? 0))
            : 0;
        $kecRasioTinggi = count(array_filter($f['kecamatan'], fn ($k) => ($k['rasio'] ?? 0) > 20));

        $trenSd = $f['trenSd'];
        $sdAwal = $trenSd->first();
        $sdAkhir = $trenSd->last();
        $pctTrenSd = $sdAwal && $sdAkhir && (int) $sdAwal->murid > 0
            ? round(((int) $sdAkhir->murid - (int) $sdAwal->murid) / (int) $sdAwal->murid * 100, 1)
            : 0;

        $hlsAkhir = $this->nilaiTerakhir($f['bps']['hls'] ?? []);
        $rlsAkhir = $this->nilaiTerakhir($f['bps']['rls'] ?? []);
        $miskinAkhir = $this->nilaiTerakhir($f['bps']['miskin_pct'] ?? []);
        $selisihHlsRls = $hlsAkhir > 0 && $rlsAkhir > 0 ? round($hlsAkhir - $rlsAkhir, 2) : 0;

        $padatUtama = array_key_first($f['rombelPadatPerJenjang']) ?: '-';
        $padatUtamaJumlah = (int) ($f['rombelPadatPerJenjang'][$padatUtama] ?? 0);
        $akreditasiUtama = array_key_first($f['akreditasiRendahPerJenjang']) ?: '-';
        $akreditasiUtamaJumlah = (int) ($f['akreditasiRendahPerJenjang'][$akreditasiUtama] ?? 0);
        $akreditasiKedua = '';
        foreach (array_slice($f['akreditasiRendahPerJenjang'], 1, 2, true) as $jenjang => $jumlah) {
            $akreditasiKedua .= ($akreditasiKedua !== '' ? ' dan ' : '') . $jenjang . ' (' . $this->n($jumlah) . ')';
        }

        return [
            [
                'id' => 'sarana',
                'kategori' => 'Sarana & Prasarana',
                'prioritas' => 'Kritis',
                'skor' => 96,
                'judul' => 'Rehabilitasi ' . $this->n($rusakSb) . ' ruang kelas rusak sedang/berat',
                'temuan' => [
                    $this->n($ruangRusak) . ' dari ' . $this->n($ruangTotal) . ' ruang kelas (' . $this->n($pctTidakLayak, 1) . '%) tidak layak pakai; ' . $this->n($rusakSb) . ' di antaranya rusak sedang atau berat.',
                    'Dengan target rasio 28 siswa per rombel, kebutuhan ' . $this->n(array_sum(array_column($sarana, 'kebutuhan_ruang'))) . ' ruang kelas sementara tersedia ' . $this->n($ruangTotal) . '. Secara total cukup, tetapi ' . $this->n($defisit) . ' ruang masih kurang karena sebarannya tidak merata: SMA kurang ' . $this->n(array_sum(array_column(array_filter($sarana, fn ($x) => $x['jenjang'] === 'SMA'), 'defisit'))) . ' ruang dan SMK kurang ' . $this->n(array_sum(array_column(array_filter($sarana, fn ($x) => $x['jenjang'] === 'SMK'), 'defisit'))) . ' ruang.',
                    'Estimasi kebutuhan anggaran ' . $this->rupiah($totalBiaya) . ' (rehab ringan ' . $this->rupiah($biaya['ringan']) . ', sedang ' . $this->rupiah($biaya['sedang']) . ', berat ' . $this->rupiah($biaya['berat']) . ', ruang baru ' . $this->rupiah($biaya['baru']) . ').',
                ],
                'tindakan' => [
                    'Ajukan rehabilitasi ke DAK Fisik dan DAU bidang pendidikan 2027 dengan lampiran daftar sekolah per kecamatan dari halaman Sarana & Anggaran.',
                    'Terbitkan instruksi larangan memakai ruang berkategori rusak berat untuk kegiatan belajar; relokasi ke ruang terdekat sampai perbaikan selesai.',
                    'Pecah kebutuhan menjadi tiga tahap tahunan sekitar ' . $this->rupiah($totalBiaya / 3) . ' dan kunci ke dalam RENJA serta RKA perangkat daerah.',
                ],
                'dampak' => 'Menghapus seluruh ruang tidak layak dalam tiga tahun dan menutup defisit ' . $this->n($defisit) . ' ruang kelas.',
                'biaya' => $this->rupiah($totalBiaya),
                'tenggat' => 'APBD 2027 - tahap I',
                'unit' => 'Bidang Sarana Prasarana',
                'dasar' => 'Walidata Pendidikan 2020–2024, Dapodik ' . $label,
                'tautan' => route('sarana'),
                'labelTautan' => 'Sarana & Anggaran',
            ],
            [
                'id' => 'data-madrasah',
                'kategori' => 'Data & Perencanaan',
                'prioritas' => 'Kritis',
                'skor' => 92,
                'judul' => 'Integrasi data guru ' . $this->n($f['miSekolah']) . ' MI dan ' . $this->n($f['mtsSekolah']) . ' MTs (total ' . $this->n($f['madrasahTotal']) . ' madrasah) serta ' . $this->n($f['tanpaDataSiswa']) . ' sekolah tanpa murid',
                'temuan' => [
                    'Kekurangan data guru MI dan MTs pada basis data satuan pendidikan: ' . $this->n($f['miSekolah']) . ' MI (' . $this->n($f['miSiswa']) . ' siswa) dan ' . $this->n($f['mtsSekolah']) . ' MTs (' . $this->n($f['mtsSiswa']) . ' siswa), ditambah ' . $this->n($f['maSekolah']) . ' MA (' . $this->n($f['maSiswa']) . ' siswa), memiliki 0 rekaman guru per NPSN di tabel Dapodik (teacher_stats).',
                    'Pada data agregat Satu Data Kemenag, MTs mencatat ' . $this->n($f['mtsGuruKemenag']) . ' guru (TA 2024/2025) namun hanya tersedia per kecamatan tanpa rincian NPSN/sertifikasi, sedangkan berkas CSV MI mengalami duplikasi dengan MTs pada 7 dari 9 tahun ajaran (data valid terakhir TA 2018/2019: ' . $this->n($f['miGuruKemenag']) . ' guru MI).',
                    'Akibat masuknya ' . $this->n($f['miSiswa'] + $f['mtsSiswa']) . ' siswa MI/MTs (total ' . $this->n($f['miSiswa'] + $f['mtsSiswa'] + $f['maSiswa']) . ' siswa madrasah) tanpa pembagi guru di Dapodik, rasio siswa/guru kabupaten terdistorsi menjadi ' . $this->n($f['rasioKabupaten'], 1) . ':1 (padahal rasio riil sekolah umum non-madrasah adalah ' . $this->n($f['rasioKabupatenUmum'], 1) . ':1). Selain itu, ' . $this->n($f['tanpaDataSiswa']) . ' sekolah belum memiliki data murid dan ' . $this->n($f['jenjangSalah']) . ' sekolah tidak sinkron nama/jenjangnya.',
                ],
                'tindakan' => [
                    'Teken kerja sama pertukaran data berkala dengan Kantor Kemenag Kabupaten Banjarnegara untuk menarik data EMIS per NPSN (khususnya rincian guru, sertifikasi, dan rombel pada ' . $this->n($f['miSekolah']) . ' MI dan ' . $this->n($f['mtsSekolah']) . ' MTs) serta perbaiki berkas CSV MI yang terduplikasi dengan MTs.',
                    'Pisahkan indikator rasio siswa/guru antara sekolah umum kewenangan Dinas (' . $this->n($f['rasioKabupatenUmum'], 1) . ':1) dan rasio gabungan lintas instansi sampai data guru MI/MTs per NPSN masuk ke tabel teacher_stats.',
                    'Bentuk tim verifikasi per kecamatan untuk membetulkan ' . $this->n($f['jenjangSalah']) . ' entri jenjang dan melengkapi ' . $this->n($f['tanpaDataSiswa']) . ' sekolah tanpa data murid sebagai syarat pencairan BOSP.',
                ],
                'dampak' => 'Menghilangkan bias perhitungan rasio guru dan kepadatan rombel pada ' . $this->n($f['miSekolah']) . ' MI dan ' . $this->n($f['mtsSekolah']) . ' MTs sehingga analisis perencanaan kabupaten akurat 100%.',
                'biaya' => null,
                'tenggat' => 'Triwulan I 2027',
                'unit' => 'Bidang Perencanaan, Sekretariat & Kankemenag',
                'dasar' => 'Tabel schools, teacher_stats, student_stats (' . $label . ') & CSV Kemenag',
                'tautan' => route('data-health'),
                'labelTautan' => 'Kualitas Data',
            ],
            [
                'id' => 'guru',
                'kategori' => 'Guru & Tenaga Kependidikan',
                'prioritas' => 'Kritis',
                'skor' => 88,
                'judul' => 'Redistribusi guru: ' . $this->n($kecRasioTinggi) . ' kecamatan di atas rasio 20:1 (terpengaruh kekosongan data guru MI & MTs)',
                'temuan' => [
                    'Rasio gabungan tercatat ' . $this->n($f['rasioKabupaten'], 1) . ' siswa per guru (' . $this->n($f['totalSiswa']) . ' siswa / ' . $this->n($f['totalGuru']) . ' guru Dapodik). Namun angka ini belum sempurna karena ' . $this->n($f['miSiswa']) . ' siswa MI dan ' . $this->n($f['mtsSiswa']) . ' siswa MTs (serta ' . $this->n($f['maSiswa']) . ' siswa MA) ikut dihitung di pembilang sementara data gurunya di Dapodik masih 0.',
                    'Jika dihitung khusus sekolah umum non-madrasah (' . $this->n($f['totalSiswaUmum']) . ' siswa / ' . $this->n($f['totalGuru']) . ' guru), rasio riil kabupaten adalah ' . $this->n($f['rasioKabupatenUmum'], 1) . ':1. Bila estimasi agregat guru Kemenag (' . $this->n($f['miGuruKemenag']) . ' guru MI + ' . $this->n($f['mtsGuruKemenag']) . ' guru MTs + ' . $this->n($f['maGuruKemenag']) . ' guru MA = ' . $this->n($f['estimasiGuruMadrasah']) . ' guru) digabungkan, rasio keseluruhan menjadi ' . $this->n($f['rasioEstimasiLengkap'], 1) . ':1.',
                    'Pada perhitungan gabungan saat ini, ' . $this->n($kecRasioTinggi) . ' dari 20 kecamatan melampaui 20:1 dengan tertinggi di ' . $kecTerburuk . ' (' . $this->n($rasioTerburuk, 1) . ':1; rasio sekolah umumnya ' . $this->n($rasioUrut[$kecTerburuk]['rasio_umum'] ?? 0, 1) . ':1). Sementara itu, baru ' . $this->n($f['pctSertifikasi'], 1) . '% guru Dapodik yang bersertifikat pendidik.',
                ],
                'tindakan' => [
                    'Gunakan rasio sekolah umum non-madrasah (' . $this->n($f['rasioKabupatenUmum'], 1) . ':1) sebagai dasar mutasi guru PNS/PPPK di bawah Dindikpora sembari menunggu integrasi data guru MI dan MTs dari EMIS Kemenag.',
                    'Susun rencana pemerataan guru bersertifikat ke kecamatan dengan rasio sekolah umum tertinggi dan koordinasikan kebutuhan guru MI/MTs bersama Seksi Pendidikan Madrasah Kemenag.',
                    'Arahkan formasi PPPK 2027 ke mata pelajaran dan sekolah negeri yang riil mengalami kekurangan guru per rombel.',
                ],
                'dampak' => 'Penempatan dan redistribusi guru tepat sasaran pada sekolah yang benar-benar kekurangan pendidik tanpa terdistorsi kekosongan data guru MI dan MTs.',
                'biaya' => null,
                'tenggat' => 'Formasi 2027',
                'unit' => 'Bidang GTK & Kankemenag',
                'dasar' => 'Dapodik ' . $label . ' & Agregat Kemenag',
                'tautan' => route('kebijakan'),
                'labelTautan' => 'Analisis Kebijakan',
            ],
            [
                'id' => 'regrouping',
                'kategori' => 'Efisiensi Satuan Pendidikan',
                'prioritas' => 'Tinggi',
                'skor' => 82,
                'judul' => 'Regrouping ' . $this->n($f['sekolahKecilSd']) . ' SD bermurid di bawah 60 siswa',
                'temuan' => [
                    $this->n($f['sekolahKecilSd']) . ' SD memiliki kurang dari 60 murid; jumlah murid SD turun ' . $this->n(abs($pctTrenSd), 1) . '% dari ' . $this->n((int) ($sdAwal->murid ?? 0)) . ' (' . ($sdAwal->tahun_ajaran ?? '-') . ') menjadi ' . $this->n((int) ($sdAkhir->murid ?? 0)) . ' (' . ($sdAkhir->tahun_ajaran ?? '-') . ').',
                    $this->n($reg['totalPairs']) . ' pasangan SD berjarak di bawah 1 km; ' . $this->n($reg['pasanganTerpilih']) . ' pasangan lolos seleksi tanpa tumpang tindih.',
                    'Potensi penghematan ' . $this->n($reg['hematRombelRencana']) . ' rombel dan ' . $this->n($reg['hematGuruRencana']) . ' formasi guru.',
                ],
                'tindakan' => [
                    'Verifikasi lapangan tahap pertama di ' . $kecKandidat . ' sebagai kecamatan dengan pasangan terdekat terbanyak.',
                    'Tetapkan kriteria baku: hanya untuk pasangan dengan jarak tempuh di bawah 3 km dan tidak memutus akses anak di wilayah terpencil.',
                    'Jadikan hasilnya bahan revisi rencana penataan sekolah 2027–2031 sebelum ada usulan penutupan atau penggabungan.',
                ],
                'dampak' => 'Efisiensi ' . $this->n($reg['hematRombelRencana']) . ' rombel tanpa menambah beban perjalanan siswa.',
                'biaya' => null,
                'tenggat' => 'Verifikasi 2027, keputusan 2028',
                'unit' => 'Bidang Pembinaan SD',
                'dasar' => 'Dapodik ' . $label . ', Dindikpora 2018/2019–2023/2024',
                'tautan' => route('regrouping'),
                'labelTautan' => 'Kandidat Regrouping',
            ],
            [
                'id' => 'rombel',
                'kategori' => 'Mutu Pembelajaran',
                'prioritas' => 'Tinggi',
                'skor' => 74,
                'judul' => 'Pecah rombel gemuk di ' . $this->n($f['rombelPadatUmum']) . ' sekolah umum (serta sinkronisasi rombel ' . $this->n($f['rombelPadatMadrasahPlaceholder']) . ' madrasah)',
                'temuan' => [
                    'Tercatat ' . $this->n($f['rombelPadat']) . ' satuan pendidikan dengan rasio > 32 siswa/rombel, namun ' . $this->n($f['rombelPadatMadrasahPlaceholder']) . ' di antaranya adalah MI (' . $this->n($f['rombelPadatPerJenjang']['MI'] ?? 0) . '), MTs (' . $this->n($f['rombelPadatPerJenjang']['MTS'] ?? 0) . '), dan MA (' . $this->n($f['rombelPadatPerJenjang']['MA'] ?? 0) . ') yang jumlah rombelnya masih tercatat placeholder 1 rombel di basis data.',
                    'Pada sekolah umum yang data rombelnya lengkap, terdapat ' . $this->n($f['rombelPadatUmum']) . ' sekolah dengan rombel padat: ' . implode(', ', array_map(
                        fn ($k, $v) => $k . ' (' . $this->n($v) . ' sekolah)',
                        array_keys($f['rombelPadatUmumPerJenjang']),
                        array_values($f['rombelPadatUmumPerJenjang'])
                    )) . '.',
                    'Rombel gemuk menurunkan kualitas interaksi belajar dan menyulitkan pemenuhan standar proses.',
                ],
                'tindakan' => [
                    'Wajibkan penambahan rombel di sekolah dengan rata-rata di atas 32 siswa per rombel pada tahun ajaran berikutnya.',
                    'Untuk MI dan MTs yang berada di bawah Kemenag, ajukan penambahan ruang lewat anggaran madrasah dan usulkan ke Kankemenag.',
                    'Pantau rasio rombel tiap semester sebagai indikator kinerja bidang pembinaan.',
                ],
                'dampak' => 'Tidak ada lagi rombel di atas 32 siswa di sekolah di bawah dinas dalam dua tahun ajaran.',
                'biaya' => null,
                'tenggat' => 'Tahun ajaran 2027/2028',
                'unit' => 'Bidang Pembinaan',
                'dasar' => 'Dapodik ' . $label,
                'tautan' => route('kebijakan'),
                'labelTautan' => 'Analisis Kebijakan',
            ],
            [
                'id' => 'akreditasi',
                'kategori' => 'Mutu & Akuntabilitas',
                'prioritas' => 'Tinggi',
                'skor' => 70,
                'judul' => 'Pendampingan akreditasi ' . $this->n($f['akreditasiRendah']) . ' sekolah berpredikat C atau belum terakreditasi',
                'temuan' => [
                    $this->n($f['akreditasiRendah']) . ' sekolah belum berakreditasi atau baru C.',
                    'Sebaran terbesar: ' . $akreditasiUtama . ' (' . $this->n($akreditasiUtamaJumlah) . ' sekolah)' . ($akreditasiKedua !== '' ? ', lalu ' . $akreditasiKedua : '') . '.',
                    'Akreditasi C membatasi akses bantuan pemerintah dan menurunkan kepercayaan orang tua.',
                ],
                'tindakan' => [
                    'Bentuk gugus pendampingan akreditasi dengan target minimal B untuk sekolah negeri dalam dua siklus.',
                    'Prioritaskan bantuan operasional dan sarana ke sekolah yang sedang menuju reakreditasi.',
                    'Sediakan data pokok yang dibutuhkan asesor (rasio, sarana, kelulusan) dalam satu dasbor agar sekolah tidak menyusun manual.',
                ],
                'dampak' => 'PAUD dan TK yang belum terakreditasi turun signifikan, dan seluruh SD serta SMP negeri minimal B pada siklus berikutnya.',
                'biaya' => null,
                'tenggat' => 'Siklus akreditasi 2027',
                'unit' => 'Bidang Pembinaan',
                'dasar' => 'Tabel schools (kolom akreditasi)',
                'tautan' => route('data-health'),
                'labelTautan' => 'Kualitas Data',
            ],
            [
                'id' => 'overage',
                'kategori' => 'Pemerataan Akses',
                'prioritas' => 'Sedang',
                'skor' => 64,
                'judul' => 'Turunkan ' . $this->n($f['pctOverage'], 1) . '% siswa di luar usia jenjang',
                'temuan' => [
                    $this->n($f['siswaOverage']) . ' siswa berada di luar rentang usia standar untuk jenjangnya (' . $this->n($f['pctOverage'], 1) . '% dari ' . $this->n($f['totalSiswa']) . ' siswa).',
                    'Siswa di luar usia menandakan keterlambatan masuk atau tinggal kelas yang tidak tertangani.',
                    'Kecamatan dengan proporsi tertinggi dapat dilihat pada tabel prioritas kecamatan di bawah.',
                ],
                'tindakan' => [
                    'Verifikasi NISN dan tanggal lahir pada penerimaan murid baru; tolak pendaftaran di luar rentang usia tanpa rekomendasi.',
                    'Tangani siswa tinggal kelas dengan program remedial dan komunikasi orang tua, bukan dibiarkan berulang.',
                    'Arahkan siswa di luar usia yang sudah bekerja ke program kesetaraan PKBM agar tetap mendapat ijazah.',
                ],
                'dampak' => 'Proporsi siswa di luar usia turun di bawah 10% dalam tiga tahun ajaran.',
                'biaya' => null,
                'tenggat' => 'Tahun ajaran 2027/2028',
                'unit' => 'Bidang Pembinaan',
                'dasar' => 'Dapodik ' . $label . ' (kolom usia_sesuai)',
                'tautan' => route('kebijakan'),
                'labelTautan' => 'Analisis Kebijakan',
            ],
            [
                'id' => 'kesetaraan',
                'kategori' => 'Pemerataan Akses',
                'prioritas' => 'Sedang',
                'skor' => 58,
                'judul' => 'Kejar rata-rata lama sekolah: selisih ' . $this->n($selisihHlsRls, 2) . ' tahun dengan harapan lama sekolah',
                'temuan' => [
                    'Harapan lama sekolah ' . $this->n($hlsAkhir, 2) . ' tahun, tetapi rata-rata lama sekolah penduduk hanya ' . $this->n($rlsAkhir, 2) . ' tahun (selisih ' . $this->n($selisihHlsRls, 2) . ' tahun).',
                    'Artinya anak yang sekarang bersekolah diharapkan tamat SMA, sementara penduduk dewasa rata-rata hanya tamat kelas VII.',
                    'Kemiskinan ' . $this->n($miskinAkhir, 2) . '% menahan anak bekerja lebih awal.',
                ],
                'tindakan' => [
                    'Perluas layanan PKBM dan program kesetaraan Paket B/C dengan kelas sore atau akhir pekan.',
                    'Petakan anak putus sekolah per desa bersama pemerintah desa dan padukan dengan bantuan sosial bersyarat.',
                    'Sediakan transportasi atau asrama untuk wilayah dengan jarak tempuh terjauh agar tidak putus di jenjang menengah.',
                ],
                'dampak' => 'Rata-rata lama sekolah naik menuju ' . $this->n($rlsAkhir + 0.5, 2) . ' tahun dalam lima tahun.',
                'biaya' => null,
                'tenggat' => 'RPJMD 2027–2031',
                'unit' => 'Bidang PAUD Dikmas',
                'dasar' => 'BPS Kabupaten Banjarnegara 2018–2024',
                'tautan' => route('kebijakan'),
                'labelTautan' => 'Analisis Kebijakan',
            ],
            [
                'id' => 'kualitas-data',
                'kategori' => 'Data & Perencanaan',
                'prioritas' => 'Sedang',
                'skor' => 54,
                'judul' => 'Audit Dapodik berkala: ' . $this->n($f['tanpaTelepon']) . ' sekolah tanpa nomor telepon',
                'temuan' => [
                    $this->n($f['tanpaTelepon']) . ' sekolah belum punya nomor telepon yang bisa dihubungi.',
                    'Sebagian koordinat sekolah sebelumnya berada di luar batas kecamatan dan sudah dikoreksi lewat penyelarasan peta.',
                    'Hasil rekonsiliasi dengan direktori SIMU-DIK menunjukkan sejumlah SD negeri belum masuk basis data perencanaan.',
                ],
                'tindakan' => [
                    'Tetapkan audit Dapodik bulanan dengan indikator kelengkapan yang sama seperti halaman Kualitas Data.',
                    'Wajibkan verifikasi nomor telepon dan koordinat saat pengajuan BOSP tiap tahap.',
                    'Sinkronkan direktori SIMU-DIK dan Dapodik setiap semester agar tidak ada sekolah yang terlewat dari perencanaan.',
                ],
                'dampak' => 'Skor kelengkapan data naik dan perencanaan tidak lagi bergantung pada data yang bolong.',
                'biaya' => null,
                'tenggat' => 'Rutin tiap semester',
                'unit' => 'Operator Dapodik & Sekretariat',
                'dasar' => 'Tabel schools, perintah simudik:import',
                'tautan' => route('data-health'),
                'labelTautan' => 'Kualitas Data',
            ],
        ];
    }

    // =====================================================================
    // 3. Pemformatan
    // =====================================================================
    private function n(float|int $nilai, int $desimal = 0): string
    {
        return number_format($nilai, $desimal, ',', '.');
    }

    private function labelSemester(): string
    {
        $tahun = (int) substr(self::BASELINE_SEMESTER, 0, 4);
        $semester = (int) substr(self::BASELINE_SEMESTER, 4, 1);

        return 'TA ' . $tahun . '/' . ($tahun + 1) . ' semester ' . $semester;
    }

    /** Nilai terakhir dari deret [tahun => nilai]. */    private function nilaiTerakhir(array $deret, float $bawaan = 0.0): float
    {
        $nilai = array_slice(array_values($deret), -1);

        return (float) ($nilai[0] ?? $bawaan);
    }

    private function rupiah(float $juta): string
    {
        if ($juta >= 1_000_000) {
            return 'Rp ' . $this->n($juta / 1_000_000, 2) . ' triliun';
        }

        if ($juta >= 1_000) {
            return 'Rp ' . $this->n($juta / 1_000, 2) . ' miliar';
        }

        return 'Rp ' . $this->n($juta, 1) . ' juta';
    }
}
