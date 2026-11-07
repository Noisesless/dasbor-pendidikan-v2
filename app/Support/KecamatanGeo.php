<?php

namespace App\Support;

/**
 * Helper geometri untuk batas kecamatan dari storage/app/data/peta_kecamatan.geojson.
 * Dipakai untuk memvalidasi & mengoreksi koordinat sekolah agar konsisten dengan peta.
 */
class KecamatanGeo
{
    /** @var array<string, array{rings: array<int, array<int, array{0: float, 1: float}>>, center: array{0: float, 1: float}}>|null */
    protected static ?array $cache = null;

    protected static ?string $sourcePath = null;

    public static function path(): string
    {
        return self::$sourcePath ??= storage_path('app/data/peta_kecamatan.geojson');
    }

    /**
     * @return array<string, array{rings: array, center: array{0: float, 1: float}}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $raw = @file_get_contents(self::path());
        if ($raw === false) {
            return self::$cache = [];
        }

        $geo = json_decode($raw, true);
        $features = [];

        foreach ($geo['features'] ?? [] as $feature) {
            $name = trim((string) ($feature['properties']['WADMKC'] ?? ''));
            if ($name === '') {
                continue;
            }

            $rings = self::ringsFromGeometry($feature['geometry'] ?? null);
            if ($rings === []) {
                continue;
            }

            $features[$name] = [
                'rings' => $rings,
                'center' => self::labelPoint($rings),
            ];
        }

        return self::$cache = $features;
    }

    /** @return string[] */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $kecamatan): bool
    {
        return isset(self::all()[$kecamatan]);
    }

    /**
     * Titik tengah (lat, lng) yang dijamin berada di dalam polygon kecamatan.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function center(string $kecamatan): ?array
    {
        return self::all()[$kecamatan]['center'] ?? null;
    }

    public static function contains(string $kecamatan, float $lat, float $lng): bool
    {
        $rings = self::all()[$kecamatan]['rings'] ?? null;
        if ($rings === null) {
            return false;
        }

        return self::pointInRings([$lng, $lat], $rings);
    }

    /** Kecamatan yang memuat titik tersebut, null bila di luar semua polygon. */
    public static function ownerOf(float $lat, float $lng): ?string
    {
        foreach (self::all() as $name => $meta) {
            if (self::pointInRings([$lng, $lat], $meta['rings'])) {
                return $name;
            }
        }

        return null;
    }

    /** Kecamatan terdekat berdasarkan jarak ke titik tengah. */
    public static function nearest(float $lat, float $lng): ?string
    {
        $best = null;
        $bestD = INF;

        foreach (self::all() as $name => $meta) {
            [$cLat, $cLng] = $meta['center'];
            $d = ($cLat - $lat) ** 2 + ($cLng - $lng) ** 2;
            if ($d < $bestD) {
                $bestD = $d;
                $best = $name;
            }
        }

        return $best;
    }

    /**
     * Cocokkan nama kecamatan bebas (beda spasi, kapitalisasi, dsb) ke nama resmi GeoJSON.
     */
    public static function resolve(string $kecamatan): ?string
    {
        $needle = self::normalize($kecamatan);
        if ($needle === '') {
            return null;
        }

        foreach (self::names() as $name) {
            if (self::normalize($name) === $needle) {
                return $name;
            }
        }

        return null;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }

    /**
     * Susun posisi ke-i di sekitar titik tengah (spiral) yang tetap di dalam polygon.
     *
     * @return array{0: float, 1: float}
     */
    public static function spreadPoint(string $kecamatan, int $index, float $lat0, float $lng0): array
    {
        if ($index <= 0) {
            return [$lat0, $lng0];
        }

        $rings = self::all()[$kecamatan]['rings'] ?? [];
        $step = 0.0011;          // ~120 m
        $goldenAngle = 2.3999632; // rad, sudut emas
        $cosLat = max(cos(deg2rad($lat0)), 0.1);

        for ($i = $index; $i < $index + 400; $i++) {
            $radius = $step * sqrt($i);
            $theta = $i * $goldenAngle;
            $lat = $lat0 + $radius * sin($theta);
            $lng = $lng0 + ($radius * cos($theta)) / $cosLat;

            if (self::pointInRings([$lng, $lat], $rings)) {
                return [$lat, $lng];
            }
        }

        return [$lat0, $lng0];
    }

    /** @return array<int, array<int, array{0: float, 1: float}>> */
    protected static function ringsFromGeometry(?array $geometry): array
    {
        $rings = [];

        $gather = function (?array $g) use (&$rings, &$gather): void {
            if (!$g || !isset($g['type'])) {
                return;
            }

            switch ($g['type']) {
                case 'Polygon':
                    if (isset($g['coordinates'][0])) {
                        $rings[] = self::toPoints($g['coordinates'][0]);
                    }
                    break;
                case 'MultiPolygon':
                    foreach ($g['coordinates'] ?? [] as $polygon) {
                        if (isset($polygon[0])) {
                            $rings[] = self::toPoints($polygon[0]);
                        }
                    }
                    break;
                case 'GeometryCollection':
                    foreach ($g['geometries'] ?? [] as $sub) {
                        $gather($sub);
                    }
                    break;
            }
        };

        $gather($geometry);

        return array_values(array_filter($rings, fn ($ring) => count($ring) >= 3));
    }

    /** @return array<int, array{0: float, 1: float}> */
    protected static function toPoints(array $coords): array
    {
        $points = [];
        foreach ($coords as $point) {
            if (is_array($point) && isset($point[0], $point[1]) && is_numeric($point[0])) {
                $points[] = [(float) $point[0], (float) $point[1]];
            }
        }

        return $points;
    }

    /**
     * Titik "label" terbaik: kandidat di dalam polygon dengan jarak terjauh ke batas.
     * Untuk polygon cekung, centroid biasa bisa jatuh di luar area.
     *
     * @param  array<int, array<int, array{0: float, 1: float}>>  $rings
     * @return array{0: float, 1: float}  [lat, lng]
     */
    protected static function labelPoint(array $rings): array
    {
        $outer = $rings[0];
        foreach ($rings as $ring) {
            if (count($ring) > count($outer)) {
                $outer = $ring;
            }
        }

        $xs = array_column($outer, 0);
        $ys = array_column($outer, 1);
        $minX = min($xs);
        $maxX = max($xs);
        $minY = min($ys);
        $maxY = max($ys);

        // Subsample vertex supaya pencarian cepat namun tetap representatif.
        $samples = self::sampleRing($outer, 600);
        $best = [$minX, $minY];
        $bestScore = -1.0;

        $x0 = $minX; $x1 = $maxX;
        $y0 = $minY; $y1 = $maxY;

        for ($pass = 0; $pass < 4; $pass++) {
            $steps = $pass === 0 ? 24 : 14;
            $found = false;

            for ($i = 0; $i <= $steps; $i++) {
                for ($j = 0; $j <= $steps; $j++) {
                    $x = $x0 + ($x1 - $x0) * $i / $steps;
                    $y = $y0 + ($y1 - $y0) * $j / $steps;

                    if (!self::pointInRings([$x, $y], $rings)) {
                        continue;
                    }

                    $score = INF;
                    foreach ($samples as $p) {
                        $d = ($p[0] - $x) ** 2 + ($p[1] - $y) ** 2;
                        if ($d < $score) {
                            $score = $d;
                            if ($score <= $bestScore) {
                                break;
                            }
                        }
                    }

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $best = [$x, $y];
                        $found = true;
                    }
                }
            }

            if (!$found) {
                break;
            }

            $spanX = ($x1 - $x0) / $steps;
            $spanY = ($y1 - $y0) / $steps;
            $x0 = $best[0] - $spanX;
            $x1 = $best[0] + $spanX;
            $y0 = $best[1] - $spanY;
            $y1 = $best[1] + $spanY;
        }

        return [$best[1], $best[0]];
    }

    /** @return array<int, array{0: float, 1: float}> */
    protected static function sampleRing(array $ring, int $max): array
    {
        $count = count($ring);
        if ($count <= $max) {
            return $ring;
        }

        $step = (int) ceil($count / $max);
        $out = [];
        for ($i = 0; $i < $count; $i += $step) {
            $out[] = $ring[$i];
        }

        return $out;
    }

    /**
     * @param  array{0: float, 1: float}  $point  [lng, lat]
     * @param  array<int, array<int, array{0: float, 1: float}>>  $rings
     */
    protected static function pointInRings(array $point, array $rings): bool
    {
        foreach ($rings as $ring) {
            if (self::pointInRing($point, $ring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ray casting.
     *
     * @param  array{0: float, 1: float}  $point  [lng, lat]
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    protected static function pointInRing(array $point, array $ring): bool
    {
        [$x, $y] = $point;
        $inside = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
