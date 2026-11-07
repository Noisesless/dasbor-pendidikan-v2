<?php

namespace App\Services;

/**
 * Parser untuk data CSV DINDIKPORA (TK, SD, SMP, SMA, SMK).
 *
 * Port dari src/utils/csvParser.ts pada aplikasi dashboard-pendidikan.
 * Format: comma-separated dengan quoted values seperti " 5,469 ".
 */
class DindikporaParser
{
    public const LEVELS = ['tk', 'sd', 'smp', 'sma', 'smk'];

    /**
     * Ambil semua data untuk semua jenjang.
     *
     * @return array<string, array<int, array{kecamatan: string, sekolah_negeri: int, sekolah_swasta: int, guru_negeri: int, guru_swasta: int, murid_negeri: int, murid_swasta: int, tahun_ajaran: string}>>
     */
    public function fetchAll(): array
    {
        $result = [];
        foreach (self::LEVELS as $level) {
            $result[$level] = $this->fetchLevel($level);
        }

        return $result;
    }

    /**
     * Ambil data untuk satu jenjang.
     *
     * @return array<int, array{kecamatan: string, sekolah_negeri: int, sekolah_swasta: int, guru_negeri: int, guru_swasta: int, murid_negeri: int, murid_swasta: int, tahun_ajaran: string}>
     */
    public function fetchLevel(string $level): array
    {
        $path = storage_path("app/data/dindikpora/{$level}.csv");
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || count($lines) === 0) {
            return [];
        }

        // Strip BOM pada baris pertama.
        $lines[0] = str_replace("\xEF\xBB\xBF", '', $lines[0]);

        $header = $this->parseCsvLine($lines[0]);
        $header = array_map(fn ($h) => mb_strtolower(trim($h)), $header);

        $kecIdx = $this->findIndex($header, ['kecamatan']);
        $snIdx = $this->findIndex($header, ['sekolah', 'negeri']);
        $ssIdx = $this->findIndex($header, ['sekolah', 'swasta']);
        $gnIdx = $this->findIndex($header, ['guru', 'negeri']);
        $gsIdx = $this->findIndex($header, ['guru', 'swasta']);
        $mnIdx = $this->findIndex($header, ['murid', 'negeri']);
        $msIdx = $this->findIndex($header, ['murid', 'swasta']) !== -1
            ? $this->findIndex($header, ['murid', 'swasta'])
            : $this->findIndex($header, ['murid', 'swasya']);
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
                'sekolah_negeri' => $this->parseNumber($parts[$snIdx] ?? ''),
                'sekolah_swasta' => $this->parseNumber($parts[$ssIdx] ?? ''),
                'guru_negeri' => $this->parseNumber($parts[$gnIdx] ?? ''),
                'guru_swasta' => $this->parseNumber($parts[$gsIdx] ?? ''),
                'murid_negeri' => $this->parseNumber($parts[$mnIdx] ?? ''),
                'murid_swasta' => $this->parseNumber($parts[$msIdx] ?? ''),
                'tahun_ajaran' => $this->normalizeYear($parts[$taIdx] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * Parse satu baris CSV dengan dukungan quoted values.
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
            } elseif ($char === ',' && ! $inQuotes) {
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
     * Bersihkan nilai: hapus ", ' dan spasi di awal/akhir.
     */
    private function cleanValue(?string $val): string
    {
        return trim((string) preg_replace('/^["\'\s]+|["\'\s]+$/u', '', (string) $val));
    }

    /**
     * Parse angka: '-' / '' / '0' => 0, hapus koma & spasi, parseInt, NaN => 0.
     */
    private function parseNumber(?string $val): int
    {
        $cleaned = $this->cleanValue((string) $val);
        if ($cleaned === '-' || $cleaned === '' || $cleaned === '0') {
            return 0;
        }
        $stripped = str_replace([',', ' '], '', $cleaned);
        $parsed = (int) $stripped;

        return $parsed;
    }

    /**
     * Normalisasi tahun ajaran: hapus semua whitespace.
     */
    private function normalizeYear(?string $val): string
    {
        return (string) preg_replace('/\s+/u', '', (string) $val);
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
