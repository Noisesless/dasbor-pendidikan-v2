<?php

namespace App\Console\Commands;

use App\Support\KecamatanGeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor data pengaya dari folder "master data" Satu Data Banjarnegara:
 *  - Dindikpora: jumlah sekolah/guru/murid per kecamatan (TK, SD, SMP, SMA, SMK), 2016/2017-2024/2025
 *  - Kemenag: MI, MTs, MA, RA per kecamatan
 *  - BPS: HLS, RLS, IPM, kemiskinan, gini, pengeluaran perkapita, UHH
 */
class ImportMasterData extends Command
{
    protected $signature = 'masterdata:import
                            {--path= : Root folder master data}
                            {--fresh : Kosongkan tabel pengaya sebelum impor}';

    protected $description = 'Impor data pengaya (Dindikpora, Kemenag, BPS) dari folder master data';

    /** @var array<string, array<string, string>> */
    private array $map = [
        'dindikpora' => [
            'TK' => 'Data Dindikpora TK Th.2016-2025/t_Jumlah_sekolah_tk_CSV.csv',
            'SD' => 'Data Dindikpora SD Th.2016-2025/t_Jumlah_sekolah_sd_CSV.csv',
            'SMP' => 'Data Dindikpora SMP Th.2016-2025/t_Jumlah_sekolah_smp_CSV.csv',
            'SMA' => 'Data Dindikpora SMA Th.2021-2025/t_Jumlah_sekolah_SMA_CSV.csv',
            'SMK' => 'Data Dindikpora SMK Th.2016-2025/t_Jumlah_sekolah_SMK_CSV.csv',
        ],
        'kemenag' => [
            'MI' => 'Jumlah Sekolah, Guru, dan Murid MI/Jumlah Sekolah Guru dan Murid MI CSV.csv',
            'MTS' => 'Jumlah Sekolah, Guru, dan MTs/Jumlah Sekolah Guru MTs CSV.csv',
            'MA' => 'Jumlah Sekolah, Guru, dan Murid MA/Jumlah Sekolah, Guru, dan Murid MA CSV.csv',
            'RA' => 'Jumlah Sekolah, Guru, dan Murid RA/Jumlah Sekolah, Guru, dan Murid RA CSV.csv',
        ],
    ];

    /** @var array<string, array{0: string, 1: string}> indikator => [folder/file, satuan] */
    private array $bps = [
        'hls' => ['Perbandingan Harapan Lama Sekolah/Data Harapan Lama Sekolah CSV.csv', 'tahun'],
        'rls' => ['Perbandingan Rata Rata Lama Sekolah/Data Rata-rata Lama Sekolah CSV.csv', 'tahun'],
        'ipm' => ['Perbandingan Indeks Pembangunan Manusia/Nilai Indeks Pembangunan Manusia CSV.csv', 'indeks'],
        'miskin_pct' => ['Perbandingan Prosentase Penduduk Miskin/Data Penduduk Miskin CSV.csv', '%'],
        'gini' => ['Perbandingan Indeks Gini/Nilai Indeks Gini CSV.csv', 'indeks'],
        'pengeluaran' => ['Perbandingan Pengeluaran Perkapita/Pengeluaran Perkapita CSV.csv', 'Rp/kapita'],
        'uhh' => ['Perbandingan Usia Harapan Hidup/Usia Harapan Hidup CSV.csv', 'tahun'],
        'garis_kemiskinan' => ['Perbandingan Garis Kemiskinan/Data Garis Kemiskinan CSV.csv', 'Rp/kapita/bulan'],
    ];

    public function handle(): int
    {
        $root = (string) ($this->option('path') ?: 'I:\analiotik\master data\EXCEL + CSV\EXCEL + CSV');

        $dindikpora = $root . '\02 DINDIKPORA 2018-2024';
        $kemenag = $root . '\25. KEMENAG';
        $bps = $root . '\47. BPS';

        foreach ([$dindikpora, $kemenag, $bps] as $dir) {
            if (!is_dir($dir)) {
                $this->error('Folder tidak ditemukan: ' . $dir);

                return self::FAILURE;
            }
        }

        if ($this->option('fresh')) {
            DB::table('regional_education_stats')->delete();
            DB::table('regional_indicators')->delete();
            $this->warn('Tabel pengaya dikosongkan.');
        }

        $stats = $this->importEducation($dindikpora, $kemenag);
        $indicators = $this->importBps($bps);

        $this->newLine();
        $this->table(['Sumber', 'Data'], [
            ['Dindikpora + Kemenag', $stats . ' baris regional_education_stats'],
            ['BPS', $indicators . ' baris regional_indicators'],
        ]);

        return self::SUCCESS;
    }

    private function importEducation(string $dindikpora, string $kemenag): int
    {
        $rows = [];

        // Tahun ajaran 2024/2025 pada berkas Dindikpora tidak dipakai: kolom "Murid Swasta"
        // pada konversi CSV bergeser (mis. Kec. Banjarnegara 7 SD swasta = 10.627 murid).
        // Data 2024/2025 ke atas sudah tersedia dari Dapodik (student_stats/teacher_stats).
        $maxTahun = '2023/2024';

        foreach ($this->map['dindikpora'] as $jenjang => $rel) {
            $path = $dindikpora . '\\' . $rel;
            foreach ($this->readDelimited($path) as $r) {
                $kec = $this->canonicalKecamatan($r['Kecamatan'] ?? '');
                $ta = $this->tahunAjaran($r['Tahun Ajaran'] ?? '');
                if ($kec === null || $ta === '' || strcmp($ta, $maxTahun) > 0) {
                    continue;
                }

                $rows[] = [
                    'kecamatan' => $kec,
                    'jenjang' => $jenjang,
                    'tahun_ajaran' => $ta,
                    'sekolah_negeri' => $this->num($r['Sekolah Negeri'] ?? null),
                    'sekolah_swasta' => $this->num($r['Sekolah Swasta'] ?? null),
                    'guru_negeri' => $this->num($r['Guru Negeri'] ?? null),
                    'guru_swasta' => $this->num($r['Guru Swasta'] ?? null),
                    'murid_negeri' => $this->num($r['Murid Negeri'] ?? null),
                    'murid_swasta' => $this->num($this->firstKey($r, ['Murid  Swasta', 'Murid Swasta']) ?? null),
                    'sumber' => 'dindikpora',
                ];
            }
        }

        // Berkas MTs dipakai sebagai pembanding: berkas MI dari Kemenag rusak pada 7 dari
        // 9 tahun ajaran (isinya duplikat MTs). Baris MI yang identik dengan MTs dilewati.
        $mtsRows = [];
        $skipped = 0;
        foreach ($this->readDelimited($kemenag . '\\' . $this->map['kemenag']['MTS']) as $r) {
            $kec = $this->canonicalKecamatan($r['Kecamatan'] ?? '');
            $ta = $this->tahunAjaran($r['Tahun Ajaran'] ?? '');
            if ($kec !== null && $ta !== '') {
                $mtsRows[$kec . '|' . $ta] = implode('|', [
                    $this->num($r['Sekolah Negeri'] ?? null), $this->num($r['Sekolah Swasta'] ?? null),
                    $this->num($r['Guru Negeri'] ?? null), $this->num($r['Guru Swasta'] ?? null),
                    $this->num($r['Murid Negeri'] ?? null), $this->num($r['Murid Swasta'] ?? null),
                ]);
            }
        }

        foreach ($this->map['kemenag'] as $jenjang => $rel) {
            $path = $kemenag . '\\' . $rel;
            foreach ($this->readDelimited($path) as $r) {
                $kec = $this->canonicalKecamatan($r['Kecamatan'] ?? '');
                $ta = $this->tahunAjaran($r['Tahun Ajaran'] ?? '');
                if ($kec === null || $ta === '') {
                    continue;
                }

                // Data RA dari Kemenag tidak memisahkan negeri/swasta.
                $isTotal = isset($r['Jumlah Sekolah']);

                $row = [
                    'kecamatan' => $kec,
                    'jenjang' => $jenjang,
                    'tahun_ajaran' => $ta,
                    'sekolah_negeri' => $isTotal ? 0 : $this->num($r['Sekolah Negeri'] ?? null),
                    'sekolah_swasta' => $isTotal ? $this->num($r['Jumlah Sekolah']) : $this->num($r['Sekolah Swasta'] ?? null),
                    'guru_negeri' => $isTotal ? 0 : $this->num($r['Guru Negeri'] ?? null),
                    'guru_swasta' => $isTotal ? $this->num($r['Jumlah Guru']) : $this->num($r['Guru Swasta'] ?? null),
                    'murid_negeri' => $isTotal ? 0 : $this->num($r['Murid Negeri'] ?? null),
                    'murid_swasta' => $isTotal ? $this->num($r['Jumlah Siswa']) : $this->num($r['Murid Swasta'] ?? null),
                    'sumber' => 'kemenag',
                ];

                if ($jenjang === 'MI') {
                    $signature = implode('|', [
                        $row['sekolah_negeri'], $row['sekolah_swasta'], $row['guru_negeri'],
                        $row['guru_swasta'], $row['murid_negeri'], $row['murid_swasta'],
                    ]);

                    if (($mtsRows[$kec . '|' . $ta] ?? null) === $signature) {
                        $skipped++;
                        continue;
                    }
                }

                $rows[] = $row;
            }
        }

        $rows = array_values(array_filter($rows, fn ($r) => $r['tahun_ajaran'] !== ''));
        $now = now();

        foreach (array_chunk($rows, 300) as $chunk) {
            DB::table('regional_education_stats')->upsert(
                array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk),
                ['kecamatan', 'jenjang', 'tahun_ajaran', 'sumber'],
                ['sekolah_negeri', 'sekolah_swasta', 'guru_negeri', 'guru_swasta', 'murid_negeri', 'murid_swasta', 'updated_at']
            );
        }

        $jenjang = implode(', ', array_keys($this->map['dindikpora'] + $this->map['kemenag']));
        $tahun = DB::table('regional_education_stats')->select('tahun_ajaran')->distinct()->orderBy('tahun_ajaran')->pluck('tahun_ajaran')->implode(', ');
        $this->info("Dindikpora + Kemenag: jenjang [$jenjang]");
        $this->line("  tahun ajaran: $tahun");
        if ($skipped > 0) {
            $this->warn("  $skipped baris MI dilewati (identik dengan berkas MTs — sumber rusak).");
        }

        return count($rows);
    }

    private function importBps(string $bps): int
    {
        $rows = [];

        foreach ($this->bps as $key => [$rel, $satuan]) {
            $path = $bps . '\\' . $rel;
            if (!is_file($path)) {
                $this->warn('Lewati (tidak ada): ' . $rel);
                continue;
            }

            foreach ($this->readDelimited($path) as $r) {
                $tahun = (int) trim((string) ($r['Tahun'] ?? $r['Tahun '] ?? ''));
                if ($tahun < 2000) {
                    continue;
                }

                $nilai = $this->float($this->firstKey($r, ['Harapan Lama Sekolah ', 'Harapan Lama Sekolah', 'Rata Rata Lama Sekolah ', 'Rata Rata Lama Sekolah', 'IPM', 'Prosentase penduduk miskin (persen)', 'Indeks Gini', 'Pengeluaran Perkapita (Rp)', 'UHH', 'Garis Kemiskina', 'Nilai']) ?? null);

                if ($nilai === null) {
                    continue;
                }

                $wilayah = $this->canonicalKecamatan($r['Kabupaten'] ?? '') ?? 'Banjarnegara';

                $rows[] = [
                    'indikator' => $key,
                    'wilayah' => $wilayah,
                    'tahun' => $tahun,
                    'nilai' => $nilai,
                    'satuan' => $satuan,
                ];

                // Jumlah penduduk miskin (ribu jiwa) disimpan terpisah.
                if ($key === 'miskin_pct' && isset($r['Jumlah Penduduk miskin (ribu jiwa)'])) {
                    $rows[] = [
                        'indikator' => 'miskin_jiwa',
                        'wilayah' => $wilayah,
                        'tahun' => $tahun,
                        'nilai' => $this->float($r['Jumlah Penduduk miskin (ribu jiwa)']),
                        'satuan' => 'ribu jiwa',
                    ];
                }
            }
        }

        $now = now();
        $rows = array_values(array_filter($rows, fn ($r) => $r['nilai'] !== null));

        foreach (array_chunk($rows, 300) as $chunk) {
            DB::table('regional_indicators')->upsert(
                array_map(fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now], $chunk),
                ['indikator', 'wilayah', 'tahun'],
                ['nilai', 'satuan', 'updated_at']
            );
        }

        $this->line('BPS: ' . implode(', ', array_keys($this->bps)));

        return count($rows);
    }

    /** @return array<int, array<string, string>> */
    private function readDelimited(string $path): array
    {
        if (!is_file($path)) {
            $this->warn('File tidak ditemukan: ' . basename($path));

            return [];
        }

        $content = file_get_contents($path);
        $content = ltrim($content, "\xEF\xBB\xBF");
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

        $headerLine = '';
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $headerLine = $line;
                break;
            }
        }

        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $header = $this->parseLine($headerLine, $delimiter);
        $out = [];

        foreach ($lines as $i => $line) {
            if ($i === 0 || trim($line) === '' || $line === $headerLine) {
                continue;
            }

            $cells = $this->parseLine($line, $delimiter);
            if (count($cells) < 2) {
                continue;
            }

            $row = [];
            foreach ($header as $idx => $name) {
                $row[trim($name)] = $cells[$idx] ?? '';
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @return string[] */
    private function parseLine(string $line, string $delimiter): array
    {
        $cells = str_getcsv($line, $delimiter, '"', '\\');

        return array_map(fn ($v) => trim((string) $v), $cells);
    }

    /** @param array<string, string> $row */
    private function firstKey(array $row, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($row[$k]) && trim($row[$k]) !== '' && trim($row[$k]) !== '-') {
                return $row[$k];
            }
        }

        return null;
    }

    /** Angka gaya Indonesia: "5,469" / "4.225" = ribuan. */
    private function num(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        $v = trim($value);
        if ($v === '' || $v === '-') {
            return 0;
        }

        if (str_contains($v, ',')) {
            $v = str_replace(',', '', $v);
        }

        // "4.225" atau "1.234.567" -> pemisah ribuan
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
        }

        return (int) round((float) $v);
    }

    private function float(?string $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $v = trim($value);
        if ($v === '' || $v === '-' || $v === '...') {
            return null;
        }

        $v = str_replace(' ', '', $v);
        if (str_contains($v, ',') && str_contains($v, '.')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (str_contains($v, ',')) {
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float) $v : null;
    }

    /** "2022 /2023" -> "2022/2023" */
    private function tahunAjaran(string $value): string
    {
        $v = preg_replace('/\s*\/\s*/', '/', trim($value)) ?? '';
        $v = preg_replace('/\s+/', '', $v) ?? '';

        return preg_match('/^\d{4}\/\d{4}$/', $v) ? $v : '';
    }

    private function canonicalKecamatan(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($value === '' || $value === '-') {
            return null;
        }

        $resolved = KecamatanGeo::resolve($value);

        return $resolved ?? ucwords(strtolower($value));
    }
}
