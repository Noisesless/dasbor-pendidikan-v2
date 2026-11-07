<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Support\KecamatanGeo;
use Illuminate\Console\Command;

/**
 * Selaraskan koordinat sekolah dengan batas kecamatan di GeoJSON.
 *
 * Sekolah yang koordinatnya kosong atau berada di luar polygon kecamatannya
 * dipindahkan ke titik tengah polygon kecamatan yang bersangkutan.
 */
class FixSchoolCoordinates extends Command
{
    protected $signature = 'peta:fix-coordinates
                            {--kecamatan= : Batasi ke satu kecamatan}
                            {--dry-run : Tampilkan rencana tanpa menyimpan}';

    protected $description = 'Koreksi koordinat sekolah agar berada di dalam batas kecamatan (GeoJSON)';

    public function handle(): int
    {
        $features = KecamatanGeo::all();

        if ($features === []) {
            $this->error('GeoJSON batas kecamatan tidak ditemukan / kosong: ' . KecamatanGeo::path());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $only = $this->option('kecamatan');

        $this->syncKecamatanNames($dryRun);

        $summary = [];
        $totalFixed = 0;

        foreach (array_keys($features) as $kecamatan) {
            if ($only && KecamatanGeo::normalize($only) !== KecamatanGeo::normalize($kecamatan)) {
                continue;
            }

            $center = KecamatanGeo::center($kecamatan);
            if ($center === null) {
                continue;
            }

            $schools = School::where('kecamatan', $kecamatan)
                ->orderBy('npsn')
                ->get(['id', 'npsn', 'nama_sekolah', 'kecamatan', 'latitude', 'longitude']);

            $valid = 0;
            $outside = 0;
            $missing = 0;
            $slot = 0;

            foreach ($schools as $school) {
                $lat = (float) $school->latitude;
                $lng = (float) $school->longitude;

                $isMissing = $school->latitude === null || $school->longitude === null || ($lat === 0.0 && $lng === 0.0);
                $isInside = !$isMissing && KecamatanGeo::contains($kecamatan, $lat, $lng);

                if ($isInside) {
                    $valid++;
                    continue;
                }

                if ($isMissing) {
                    $missing++;
                } else {
                    $outside++;
                }

                [$newLat, $newLng] = KecamatanGeo::spreadPoint($kecamatan, $slot, $center[0], $center[1]);
                $slot++;

                if (!$dryRun) {
                    School::whereKey($school->id)->update([
                        'latitude' => round($newLat, 8),
                        'longitude' => round($newLng, 8),
                    ]);
                }

                $totalFixed++;
            }

            $summary[] = [
                $kecamatan,
                $schools->count(),
                $valid,
                $outside,
                $missing,
                $outside + $missing,
            ];
        }

        $this->newLine();
        $this->table(
            ['Kecamatan', 'Total', 'Valid', 'Luar Batas', 'Tanpa Koordinat', 'Dipindahkan'],
            $summary
        );

        $this->info(sprintf(
            '%s %d koordinat sekolah.',
            $dryRun ? '[DRY RUN] Akan memindahkan' : 'Selesai. Dipindahkan',
            $totalFixed
        ));

        return self::SUCCESS;
    }

    /** Samakan nama kecamatan pada tabel schools dengan nama resmi di GeoJSON. */
    private function syncKecamatanNames(bool $dryRun): void
    {
        $official = KecamatanGeo::names();
        $unknown = [];

        $distinct = School::select('kecamatan')->distinct()->pluck('kecamatan');

        foreach ($distinct as $raw) {
            $raw = trim((string) $raw);

            if (in_array($raw, $official, true)) {
                continue;
            }

            $resolved = KecamatanGeo::resolve($raw);

            if ($resolved === null) {
                $unknown[] = $raw;
                continue;
            }

            if (!$dryRun) {
                School::where('kecamatan', $raw)->update(['kecamatan' => $resolved]);
            }

            $this->warn(sprintf('Nama kecamatan "%s" diselaraskan menjadi "%s".', $raw, $resolved));
        }

        if ($unknown !== []) {
            $this->warn('Kecamatan tidak dikenal di GeoJSON: ' . implode(', ', array_map(fn ($v) => '"' . $v . '"', $unknown)));
        }
    }
}
