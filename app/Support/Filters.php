<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Filters
{
    /**
     * Nilai semester default (terbaru yang tersedia di data).
     */
    public static function defaultSemester(): string
    {
        return Cache::remember('filters.default_semester', 300, function () {
            $semester = \App\Models\StudentStat::max('semester');

            return $semester ?? '20261';
        });
    }

    /**
     * Normalisasi filter dari query string.
     *
     * @return array{semester: string, kecamatan: string, jenjang: string}
     */
    public static function fromRequest(Request $request): array
    {
        return [
            'semester' => (string) ($request->query('semester') ?: self::defaultSemester()),
            'kecamatan' => trim((string) $request->query('kecamatan', '')),
            'jenjang' => trim((string) $request->query('jenjang', '')),
        ];
    }

    /**
     * Daftar semester tersedia, urut menurun (terbaru dulu).
     *
     * @return array<string, string>
     */
    public static function semesters(): array
    {
        $values = \App\Models\StudentStat::distinct()
            ->orderByDesc('semester')
            ->pluck('semester')
            ->all();

        return array_combine($values, array_map(fn ($s) => self::semesterLabel($s), $values));
    }

    /**
     * 20261 => "Semester Ganjil 2026/2027".
     */
    public static function semesterLabel(string $semester): string
    {
        $tahun = substr($semester, 0, 4);
        $ganjil = substr($semester, 4, 1) === '1';

        return sprintf(
            '%s %s/%s',
            $ganjil ? 'Ganjil' : 'Genap',
            $tahun,
            (int) $tahun + 1,
        );
    }

    /**
     * Daftar kecamatan unik (alfabetis).
     *
     * @return array<int, string>
     */
    public static function kecamatanList(): array
    {
        return \App\Models\School::orderBy('kecamatan')
            ->distinct()
            ->pluck('kecamatan')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Daftar jenjang yang tersedia pada tabel sekolah.
     *
     * @return array<int, string>
     */
    public static function jenjangList(): array
    {
        return \App\Models\School::orderBy('jenjang')
            ->distinct()
            ->pluck('jenjang')
            ->values()
            ->all();
    }
}
