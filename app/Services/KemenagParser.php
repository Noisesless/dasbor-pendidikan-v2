<?php

namespace App\Services;

/**
 * Parser untuk data CSV KEMENAG (RA, MI, MTs, MA, nikah/cerai, tempat ibadah).
 *
 * Port dari src/utils/madrasahParser.ts pada aplikasi dashboard-pendidikan.
 * Format: semicolon-separated. Nilai bisa mengandung strip (mis. "-") yang
 * dianggap 0. Pemisahan kolom Negeri/Swasta dibaca lengkap (perbaikan
 * terhadap parser asli yang hanya membaca kolom pertama).
 */
class KemenagParser
{
    public const LEVELS = ['ra', 'mi', 'mts', 'ma'];

    /**
     * Ambil semua data madrasah untuk semua jenjang.
     *
     * @return array<int, array{kecamatan: string, sekolah_negeri: int, sekolah_swasta: int, guru_negeri: int, guru_swasta: int, murid_negeri: int, murid_swasta: int, tahun_ajaran: string, level: string}>
     */
    public function fetchMadrasahAll(): array
    {
        $result = [];
        foreach (self::LEVELS as $level) {
            foreach ($this->fetchMadrasahLevel($level) as $row) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * Ambil data madrasah untuk satu jenjang.
     *
     * @return array<int, array{kecamatan: string, sekolah_negeri: int, sekolah_swasta: int, guru_negeri: int, guru_swasta: int, murid_negeri: int, murid_swasta: int, tahun_ajaran: string, level: string}>
     */
    public function fetchMadrasahLevel(string $level): array
    {
        $path = storage_path("app/data/kemenag/{$level}.csv");
        if (!is_file($path)) {
            return [];
        }

        $lines = $this->readLines($path);
        if (count($lines) <= 1) {
            return [];
        }

        $header = array_map(
            fn ($h) => mb_strtolower($this->cleanValue($h)),
            $this->parseCsvLine($lines[0])
        );

        $kecIdx = $this->findIndex($header, ['kecamatan']);
        $snIdx = $this->findIndex($header, ['sekolah', 'negeri']);
        $ssIdx = $this->findIndex($header, ['sekolah', 'swasta']);
        $gnIdx = $this->findIndex($header, ['guru', 'negeri']);
        $gsIdx = $this->findIndex($header, ['guru', 'swasta']);
        $mnIdx = $this->findIndex($header, ['murid', 'negeri']);
        $msIdx = $this->findIndex($header, ['murid', 'swasta']);
        $taIdx = $this->findIndex($header, ['tahun']) !== -1
            ? $this->findIndex($header, ['tahun'])
            : $this->findIndex($header, ['ajaran']);

        $rows = [];
        $count = count($lines);
        for ($i = 1; $i < $count; $i++) {
            $parts = $this->parseCsvLine($lines[$i]);
            if (count($parts) < count($header)) {
                continue;
            }

            $kecamatan = $this->cleanValue($parts[$kecIdx] ?? '');
            if ($kecamatan === '' || mb_strtolower($kecamatan) === 'kecamatan') {
                continue;
            }
            if (mb_stripos($kecamatan, 'total') !== false) {
                continue;
            }

            $rows[] = [
                'kecamatan' => $kecamatan,
                'sekolah_negeri' => $snIdx !== -1 ? $this->parseNumber($parts[$snIdx]) : 0,
                'sekolah_swasta' => $ssIdx !== -1 ? $this->parseNumber($parts[$ssIdx]) : 0,
                'guru_negeri' => $gnIdx !== -1 ? $this->parseNumber($parts[$gnIdx]) : 0,
                'guru_swasta' => $gsIdx !== -1 ? $this->parseNumber($parts[$gsIdx]) : 0,
                'murid_negeri' => $mnIdx !== -1 ? $this->parseNumber($parts[$mnIdx]) : 0,
                'murid_swasta' => $msIdx !== -1 ? $this->parseNumber($parts[$msIdx]) : 0,
                'tahun_ajaran' => $taIdx !== -1 ? preg_replace('/\s+/u', '', $this->cleanValue($parts[$taIdx])) : '',
                'level' => strtoupper($level),
            ];
        }

        return $rows;
    }

    /**
     * Ambil data nikah/cerai per kecamatan per tahun.
     *
     * @return array<int, array{kecamatan: string, nikah: int, talak: int, cerai: int, rujuk: int, tahun: int}>
     */
    public function fetchNikahCerai(): array
    {
        $path = storage_path('app/data/kemenag/nikah_cerai.csv');
        if (!is_file($path)) {
            return [];
        }

        $lines = $this->readLines($path);
        if (count($lines) <= 1) {
            return [];
        }

        $header = array_map(
            fn ($h) => mb_strtolower($this->cleanValue($h)),
            $this->parseCsvLine($lines[0])
        );

        $kecIdx = $this->findIndex($header, ['kecamatan']);
        $nikahIdx = $this->findIndex($header, ['nikah']);
        $talakIdx = $this->findIndex($header, ['talak']);
        $ceraiIdx = $this->findIndex($header, ['cerai']);
        $rujukIdx = $this->findIndex($header, ['rujuk']);
        $tahunIdx = $this->findIndex($header, ['tahun']);

        $result = [];
        $count = count($lines);
        for ($i = 1; $i < $count; $i++) {
            $parts = $this->parseCsvLine($lines[$i]);
            if (count($parts) < count($header)) {
                continue;
            }
            $kec = $this->cleanValue($parts[$kecIdx] ?? '');
            if ($kec === '' || mb_strtolower($kec) === 'kecamatan') {
                continue;
            }
            $result[] = [
                'kecamatan' => $kec,
                'nikah' => $nikahIdx !== -1 ? $this->parseNumber($parts[$nikahIdx]) : 0,
                'talak' => $talakIdx !== -1 ? $this->parseNumber($parts[$talakIdx]) : 0,
                'cerai' => $ceraiIdx !== -1 ? $this->parseNumber($parts[$ceraiIdx]) : 0,
                'rujuk' => $rujukIdx !== -1 ? $this->parseNumber($parts[$rujukIdx]) : 0,
                'tahun' => $tahunIdx !== -1 ? $this->parseNumber($parts[$tahunIdx]) : 0,
            ];
        }

        return $result;
    }

    /**
     * Ambil data tempat ibadah per kecamatan per tahun.
     *
     * @return array<int, array{kecamatan: string, tahun: int, masjid: int, mushola: int, gereja: int, pura: int, vihara: int, klenteng: int}>
     */
    public function fetchTempatIbadah(): array
    {
        $path = storage_path('app/data/kemenag/tempat_ibadah.csv');
        if (!is_file($path)) {
            return [];
        }

        $lines = $this->readLines($path);
        if (count($lines) <= 1) {
            return [];
        }

        $header = array_map(
            fn ($h) => mb_strtolower($this->cleanValue($h)),
            $this->parseCsvLine($lines[0])
        );

        $kecIdx = $this->findIndex($header, ['kecamatan']);
        $tahunIdx = $this->findIndex($header, ['tahun']);
        $masjidIdx = $this->findIndex($header, ['masjid']);
        $musholaIdx = $this->findIndex($header, ['mushola']);
        if ($musholaIdx === -1) {
            $musholaIdx = $this->findIndex($header, ['langgar']);
        }
        $protestanIdx = $this->findIndex($header, ['gereja', 'protestan']);
        $katholikIdx = $this->findGerejaKatholik($header);
        $puraIdx = $this->findIndex($header, ['pura']);
        $viharaIdx = $this->findIndex($header, ['vihara']);

        $result = [];
        $count = count($lines);
        for ($i = 1; $i < $count; $i++) {
            $parts = $this->parseCsvLine($lines[$i]);
            if (count($parts) < count($header)) {
                continue;
            }
            $kec = $this->normalizeKecamatan($this->cleanValue($parts[$kecIdx] ?? ''));
            if ($kec === '' || mb_strtolower($kec) === 'kecamatan') {
                continue;
            }

            $protestan = $protestanIdx !== -1 ? $this->parseNumber($parts[$protestanIdx]) : 0;
            $katholik = $katholikIdx !== -1 ? $this->parseNumber($parts[$katholikIdx]) : 0;

            $result[] = [
                'kecamatan' => $kec,
                'tahun' => $tahunIdx !== -1 ? $this->parseNumber($parts[$tahunIdx]) : 0,
                'masjid' => $masjidIdx !== -1 ? $this->parseNumber($parts[$masjidIdx]) : 0,
                'mushola' => $musholaIdx !== -1 ? $this->parseNumber($parts[$musholaIdx]) : 0,
                'gereja' => $protestan + $katholik,
                'pura' => $puraIdx !== -1 ? $this->parseNumber($parts[$puraIdx]) : 0,
                'vihara' => $viharaIdx !== -1 ? $this->parseNumber($parts[$viharaIdx]) : 0,
                'klenteng' => 0,
            ];
        }

        return $result;
    }

    /**
     * Cari kolom gereja katholik (ejaan "katholik" atau "katolik").
     *
     * @param array<int, string> $header
     */
    private function findGerejaKatholik(array $header): int
    {
        foreach ($header as $idx => $col) {
            if (mb_stripos($col, 'gereja') !== false
                && (mb_stripos($col, 'katholik') !== false || mb_stripos($col, 'katolik') !== false)) {
                return $idx;
            }
        }

        return -1;
    }

    /**
     * Normalisasi nama kecamatan, termasuk nama terpisah-spasi
     * seperti "B a w a n g" -> "Bawang".
     */
    private function normalizeKecamatan(string $kec): string
    {
        $normalized = trim($kec);
        if (str_contains($normalized, ' ') && mb_strlen($normalized) > 10) {
            $withoutSpaces = preg_replace('/\s+/u', '', $normalized);
            $knownNames = ['Bawang', 'Sigaluh', 'Rakit', 'Batur'];
            foreach ($knownNames as $name) {
                if (mb_strtolower($withoutSpaces) === mb_strtolower($name)) {
                    return $name;
                }
            }
        }

        return trim((string) preg_replace('/\s{2,}/u', ' ', $normalized));
    }

    /**
     * Parse satu baris CSV semicolon dengan dukungan quoted values.
     *
     * @return array<int, string>
     */
    private function parseCsvLine(string $line): array
    {
        $result = [];
        $current = '';
        $inQuotes = false;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            if ($char === '"') {
                $inQuotes = ! $inQuotes;
            } elseif ($char === ';' && ! $inQuotes) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }
        $result[] = $current;

        return $result;
    }

    /**
     * Bersihkan nilai: hapus ", ', spasi, dan strip di awal/akhir.
     */
    private function cleanValue(?string $val): string
    {
        return trim((string) preg_replace('/^["\'\s-]+|["\'\s-]+$/u', '', (string) $val));
    }

    /**
     * Parse angka: '-' / '' / '0' => 0, hapus koma & spasi, NaN => 0.
     */
    private function parseNumber(?string $val): int
    {
        $cleaned = $this->cleanValue((string) $val);
        if ($cleaned === '-' || $cleaned === '' || $cleaned === '0') {
            return 0;
        }
        $stripped = str_replace([',', ' '], '', $cleaned);

        return (int) $stripped;
    }

    /**
     * Baca file ke array baris, buang baris kosong, strip BOM.
     *
     * @return array<int, string>
     */
    private function readLines(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        $raw = str_replace("\xEF\xBB\xBF", '', $raw);
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter($lines, fn ($l) => trim($l) !== ''));
    }

    /**
     * Cari index kolom yang mengandung semua keyword.
     *
     * @param array<int, string> $header
     * @param array<int, string> $keywords
     */
    private function findIndex(array $header, array $keywords): int
    {
        foreach ($header as $idx => $col) {
            $match = true;
            foreach ($keywords as $kw) {
                if (mb_stripos($col, $kw) === false) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return $idx;
            }
        }

        return -1;
    }
}
