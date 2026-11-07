<?php

namespace App\Services;

/**
 * Loader untuk storage/app/data/walidad-pendidikan.json.
 *
 * Port dari data Walidata Pendidikan pada aplikasi dashboard-pendidikan
 * (halaman walidata.tsx). File berisi array JSON datar dengan skema:
 * {kode, uraian, satuan, "2017".."2026"} — nilai tahun bertipe string.
 */
class WalidataPendidikan
{
    public const YEARS = ['2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025', '2026'];

    /**
     * Cache hasil json_decode.
     */
    private ?array $data = null;

    /**
     * Muat seluruh baris walidata. Nilai tahun dikonversi ke float
     * agar konsisten untuk kalkulasi.
     */
    public function all(): array
    {
        if ($this->data === null) {
            $path = storage_path('app/data/walidata-pendidikan.json');

            if (!is_file($path)) {
                // Nama file sebelumnya mengandung typo ("walidad").
                $alt = storage_path('app/data/walidad-pendidikan.json');
                $path = is_file($alt) ? $alt : $path;
            }

            $content = (string) file_get_contents($path);

            // File memiliki UTF-8 BOM — harus dibuang sebelum json_decode.
            $content = str_replace("\xEF\xBB\xBF", '', $content);

            $rows = json_decode($content, true);

            if (!is_array($rows)) {
                return [];
            }

            $this->data = array_map(function (array $row): array {
                $clean = [
                    'kode' => $row['kode'] ?? '',
                    'uraian' => $row['uraian'] ?? '',
                    'satuan' => $row['satuan'] ?? '',
                ];

                foreach (self::YEARS as $year) {
                    $value = $row[$year] ?? null;

                    if ($value === null || $value === '' || !is_numeric($value)) {
                        $clean[$year] = null;
                    } else {
                        $clean[$year] = (float) $value;
                    }
                }

                return $clean;
            }, $rows);
        }

        return $this->data;
    }

    /**
     * Filter baris berdasarkan kata kunci pencarian pada uraian.
     */
    public function search(string $query): array
    {
        $query = trim(mb_strtolower($query));

        if ($query === '') {
            return $this->all();
        }

        return array_values(array_filter($this->all(), function (array $row) use ($query): bool {
            return str_contains(mb_strtolower($row['uraian']), $query)
                || str_contains(mb_strtolower($row['satuan']), $query);
        }));
    }
}
