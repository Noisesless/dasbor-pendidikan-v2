<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Parser untuk data Disarpus (Dinas Perpustakaan).
 *
 * Port dari src/utils/libraryParser.ts pada aplikasi dashboard-pendidikan.
 * Semua file CSV menggunakan pemisah koma.
 */
class DisarpusParser
{
    /**
     * Urutan bulan sama seperti libraryParser.ts.
     */
    public const MONTH_ORDER = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    /**
     * Daftar kategori koleksi (kolom selain Bulan/Tahun pada
     * koleksi_dipinjam_perpustakaan.csv) — urutan sesuai file CSV.
     */
    public const COLLECTION_CATEGORIES = [
        'Karya Umum',
        'Filsafat & Psikologi',
        'Agama',
        'Ilmu Sosial',
        'Bahasa',
        'Ilmu Murni',
        'Ilmu Terapan & Teknologi',
        'OR & Kesenian',
        'Sastra',
        'Sejarah & Geologi',
        'Fiksi',
        'Majalah',
    ];

    public function fetchVisitors(): array
    {
        return $this->fetchVisitorFile('disarpus/pengunjung_perpustakaan.csv');
    }

    public function fetchNewMembers(): array
    {
        return $this->fetchVisitorFile('disarpus/anggota_baru_perpustakaan.csv');
    }

    /**
     * Baris data pengunjung/anggota per bulan.
     */
    private function fetchVisitorFile(string $relativePath): array
    {
        $lines = $this->readLines($relativePath);

        if (count($lines) < 2) {
            return [];
        }

        $header = $this->parseCsvLine($lines[0]);

        $bulanIdx = $this->findCol($header, 'bulan');
        $tahunIdx = $this->findCol($header, 'tahun');

        // Indeks kolom: Laki-laki dan Perempuan untuk tiap klasifikasi.
        $pelajarLIdx = $this->findCol($header, 'pelajar', 'laki');
        $pelajarPIdx = $this->findCol($header, 'pelajar', 'perempuan');
        $mahasiswaLIdx = $this->findCol($header, 'mahasiswa', 'laki');
        $mahasiswaPIdx = $this->findCol($header, 'mahasiswa', 'perempuan');
        $karyawanLIdx = $this->findCol($header, 'karyawan', 'laki');
        $karyawanPIdx = $this->findCol($header, 'karyawan', 'perempuan');
        $umumLIdx = $this->findCol($header, 'umum', 'laki');
        $umumPIdx = $this->findCol($header, 'umum', 'perempuan');

        $rows = [];

        for ($i = 1; $i < count($lines); $i++) {
            $parts = $this->parseCsvLine($lines[$i]);

            if (count($parts) < count($header)) {
                continue;
            }

            $bulan = $this->cleanValue($parts[$bulanIdx] ?? '');

            if (!in_array($bulan, self::MONTH_ORDER, true)) {
                continue;
            }

            $get = function (int $idx) use ($parts): int {
                return $this->parseNumber($parts[$idx] ?? '');
            };

            $pelajar = $get($pelajarLIdx) + $get($pelajarPIdx);
            $mahasiswa = $get($mahasiswaLIdx) + $get($mahasiswaPIdx);
            $karyawan = $get($karyawanLIdx) + $get($karyawanPIdx);
            $umum = $get($umumLIdx) + $get($umumPIdx);

            $rows[] = [
                'bulan' => $bulan,
                'tahun' => $this->cleanValue($parts[$tahunIdx] ?? ''),
                'pelajar' => $pelajar,
                'mahasiswa' => $mahasiswa,
                'karyawan' => $karyawan,
                'umum' => $umum,
                'total' => $pelajar + $mahasiswa + $karyawan + $umum,
            ];
        }

        return $rows;
    }

    /**
     * Baris koleksi dipinjam per bulan. Semua kolom selain Bulan/Tahun
     * diperlakukan sebagai kategori koleksi.
     */
    public function fetchCollections(): array
    {
        $lines = $this->readLines('disarpus/koleksi_dipinjam_perpustakaan.csv');

        if (count($lines) < 2) {
            return [];
        }
        $header = $this->parseCsvLine($lines[0]);

        $bulanIdx = $this->findCol($header, 'bulan');
        $tahunIdx = $this->findCol($header, 'tahun');

        // Kategori = kolom selain bulan/tahun.
        $categories = [];
        foreach ($header as $idx => $name) {
            $clean = mb_strtolower($this->cleanValue($name));
            if ($clean === '' || $clean === 'bulan' || $clean === 'tahun') {
                continue;
            }
            $categories[] = ['idx' => $idx, 'name' => $this->cleanValue($name)];
        }

        $rows = [];

        for ($i = 1; $i < count($lines); $i++) {
            $parts = $this->parseCsvLine($lines[$i]);

            if (count($parts) < count($header)) {
                continue;
            }

            $bulan = $this->cleanValue($parts[$bulanIdx] ?? '');

            if (!in_array($bulan, self::MONTH_ORDER, true)) {
                continue;
            }

            $values = [];
            foreach ($categories as $cat) {
                $values[$cat['name']] = $this->parseNumber($parts[$cat['idx']] ?? '');
            }

            $rows[] = [
                'bulan' => $bulan,
                'tahun' => $this->cleanValue($parts[$tahunIdx] ?? ''),
                'values' => $values,
            ];
        }

        return $rows;
    }

    /**
     * Parse satu baris CSV dengan pemisah koma (menghormati tanda kutip ganda).
     */
    private function parseCsvLine(string $line): array
    {
        $result = [];
        $current = '';
        $inQuotes = false;

        for ($i = 0; $i < mb_strlen($line); $i++) {
            $char = $line[$i];

            if ($char === '"') {
                $inQuotes = !$inQuotes;
            } elseif ($char === ',' && !$inQuotes) {
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
     * Hapus tanda kutip dan spasi di awal/akhir nilai.
     */
    private function cleanValue(string $value): string
    {
        return trim((string) preg_replace('/^["\'\s]+|["\'\s]+$/u', '', $value));
    }

    /**
     * Konversi nilai menjadi integer.
     */
    private function parseNumber(string $value): int
    {
        $cleaned = $this->cleanValue($value);

        if ($cleaned === '-' || $cleaned === '') {
            return 0;
        }

        $cleaned = str_replace([',', ' '], ['', ''], $cleaned);

        if (!is_numeric($cleaned)) {
            return 0;
        }

        return (int) $cleaned;
    }

    /**
     * Cari indeks kolom yang mengandung SEMUA kata kunci.
     */
    private function findCol(array $header, string ...$keywords): int
    {
        foreach ($header as $idx => $name) {
            $lower = ' ' . mb_strtolower($name) . ' ';
            $allMatch = true;

            foreach ($keywords as $keyword) {
                if (!str_contains($lower, $keyword)) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                return $idx;
            }
        }

        return -1;
    }

    /**
     * Baca file CSV dan kembalikan baris-barisnya.
     */
    private function readLines(string $relativePath): array
    {
        $path = storage_path('app/data/' . $relativePath);

        if (!is_file($path)) {
            return [];
        }

        $content = file_get_contents($path);
        $content = str_replace("\xEF\xBB\xBF", '', $content);

        $lines = explode("\n", $content);

        return array_values(array_filter($lines, function ($line) {
            return trim($line) !== '';
        }));
    }
}
