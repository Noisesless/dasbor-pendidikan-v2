<?php

namespace App\Console\Commands;

use App\Support\KecamatanGeo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menarik direktori sekolah publik dari SIMU-DIK Kabupaten Banjarnegara
 * (https://simudik.banjarnegarakab.go.id) lalu memakainya untuk melengkapi
 * nomor telepon sekolah yang masih kosong di database.
 *
 * Yang tersedia tanpa login hanya endpoint direktori:
 *   Datapool/sekolahTampil/{kecamatan}/{jenjang}
 * Data kerusakan sarana-prasarana ada di area administrator dan butuh akun.
 */
class ImportSimudik extends Command
{
    protected $signature = 'simudik:import
                            {--fetch : Ambil ulang direktori dari server SIMU-DIK}
                            {--dry-run : Hitung saja, jangan simpan perubahan}';

    protected $description = 'Lengkapi data sekolah dari direktori publik SIMU-DIK Kabupaten Banjarnegara';

    private const ENDPOINT = 'https://simudik.banjarnegarakab.go.id/public/Datapool/sekolahTampil/%s/1';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36';

    public function handle(): int
    {
        $dir = storage_path('app/data/simudik');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if ($this->option('fetch')) {
            $this->fetchAll($dir);
        }

        $rows = $this->readDirectory($dir);

        if ($rows === []) {
            $this->error('Belum ada berkas direktori. Jalankan dengan --fetch.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Direktori SIMU-DIK: ' . count($rows) . ' sekolah dari ' . count(array_unique(array_column($rows, 'kecamatan'))) . ' kecamatan');

        $this->reconcile($rows, (bool) $this->option('dry-run'));

        return self::SUCCESS;
    }

    private function fetchAll(string $dir): void
    {
        $cacert = storage_path('app/certs/cacert.pem');
        $names = KecamatanGeo::names();

        $this->components->info('Mengambil direktori sekolah dari SIMU-DIK (' . count($names) . ' kecamatan)');

        $berhasil = 0;
        $gagal = [];

        foreach ($names as $kecamatan) {
            $body = null;

            // Sebagian nama kecamatan hanya dikenali dalam bentuk slug bertanda hubung.
            foreach ([str_replace(' ', '-', $kecamatan), $kecamatan] as $slug) {
                $body = $this->download(sprintf(self::ENDPOINT, rawurlencode($slug)), $cacert);

                if (is_array(json_decode((string) $body, true))) {
                    break;
                }

                $body = null;
            }

            $rows = json_decode((string) $body, true);

            if (! is_array($rows) || $rows === []) {
                $gagal[] = $kecamatan;
                continue;
            }

            file_put_contents(
                $dir . '/' . str_replace(' ', '_', $kecamatan) . '.json',
                json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $berhasil++;
        }

        $this->line("  tersimpan: {$berhasil} kecamatan");

        if ($gagal !== []) {
            $this->warn('  tidak terjawab: ' . implode(', ', $gagal));
        }
    }

    private function download(string $url, string $cacert): ?string
    {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => self::USER_AGENT,
        ]);

        if (is_file($cacert)) {
            // PHP di Windows tidak membawa CA bundle bawaan.
            curl_setopt($curl, CURLOPT_CAINFO, $cacert);
        }

        $body = curl_exec($curl);
        curl_close($curl);

        return is_string($body) ? $body : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function readDirectory(string $dir): array
    {
        $rows = [];

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($file));
            $decoded = json_decode($raw, true);

            if (! is_array($decoded)) {
                $this->warn('  berkas rusak: ' . basename($file));
                continue;
            }

            $kecamatan = str_replace('_', ' ', basename($file, '.json'));

            foreach ($decoded as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $row['kecamatan'] = $kecamatan;
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function reconcile(array $rows, bool $dryRun): void
    {
        $sekolah = DB::table('schools')->get(['id', 'npsn', 'nama_sekolah', 'kecamatan', 'telepon', 'latitude', 'longitude']);
        $index = [];

        foreach ($sekolah as $s) {
            $index[(string) $s->npsn] = $s;
        }

        $cocok = 0;
        $tanpaPasangan = 0;
        $placeholder = 0;
        $teleponDiisi = 0;
        $teleponDitolak = 0;
        $koorBedaKecamatan = 0;
        $perKecamatan = [];

        $perubahan = [];

        foreach ($rows as $row) {
            $npsn = trim((string) ($row['npsn'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? ''));
            $kec = (string) ($row['kecamatan'] ?? '');

            // Baris cadangan: server mengembalikan NPSN sebagai nama/koordinat bila data induk kosong.
            if ($npsn === '' || $nama === $npsn || preg_match('/^\d{8}$/', $nama) === 1) {
                $placeholder++;
                continue;
            }

            $perKecamatan[$kec] = ($perKecamatan[$kec] ?? 0) + 1;

            if (! isset($index[$npsn])) {
                $tanpaPasangan++;
                continue;
            }

            $cocok++;
            $target = $index[$npsn];

            $telepon = $this->normalisasiTelepon((string) ($row['telepon'] ?? ''), $npsn);

            if ($telepon === null) {
                if (trim((string) $target->telepon) === '') {
                    $teleponDitolak++;
                }
            } elseif (trim((string) $target->telepon) === '') {
                $teleponDiisi++;
                $perubahan[] = ['id' => $target->id, 'telepon' => $telepon];
            }

            if ($this->titikDiKecamatanLain((string) ($row['koordinatpeta'] ?? ''), (string) $target->kecamatan)) {
                $koorBedaKecamatan++;
            }
        }

        if (! $dryRun && $perubahan !== []) {
            DB::transaction(function () use ($perubahan) {
                foreach ($perubahan as $p) {
                    DB::table('schools')->where('id', $p['id'])->update(['telepon' => $p['telepon']]);
                }
            });
        }

        $this->newLine();
        $this->table(
            ['Ukuran', 'Jumlah'],
            [
                ['Sekolah di direktori SIMU-DIK', number_format(array_sum($perKecamatan), 0, ',', '.')],
                ['Baris cadangan (data induk kosong)', number_format($placeholder, 0, ',', '.')],
                ['NPSN cocok dengan database', number_format($cocok, 0, ',', '.')],
                ['NPSN tidak ada di database', number_format($tanpaPasangan, 0, ',', '.')],
                ['Telepon kosong yang terisi', number_format($teleponDiisi, 0, ',', '.')],
                ['Telepon SIMU-DIK tidak valid', number_format($teleponDitolak, 0, ',', '.')],
                ['Koordinat jatuh di kecamatan lain', number_format($koorBedaKecamatan, 0, ',', '.')],
            ]
        );

        $this->line('  telepon kosong tersisa: ' . number_format(
            DB::table('schools')->where(fn ($q) => $q->whereNull('telepon')->orWhere('telepon', ''))->count(),
            0, ',', '.'
        ));

        if ($dryRun) {
            $this->warn('Mode uji: ' . count($perubahan) . ' nomor telepon tidak disimpan.');
        }

        $this->newLine();
        $this->line('  Catatan: data kerusakan sarana-prasarana SIMU-DIK berada di area administrator');
        $this->line('  dan tidak tersedia lewat endpoint publik.');
    }

    private function normalisasiTelepon(string $nilai, string $npsn): ?string
    {
        $digit = preg_replace('/\D+/', '', $nilai) ?? '';

        if ($digit === '' || $digit === $npsn) {
            return null;
        }

        if (str_starts_with($digit, '62')) {
            $digit = '0' . substr($digit, 2);
        }

        if (preg_match('/^0(\d)\1{7,}$/', $digit) === 1) {
            return null; // nomor palsu seperti 0000000000 / 0811111111
        }

        return preg_match('/^08\d{8,11}$/', $digit) === 1 ? $digit : null;
    }

    private function titikDiKecamatanLain(string $koordinat, string $kecamatan): bool
    {
        if (strpos($koordinat, ',') === false) {
            return false;
        }

        [$lat, $lng] = array_map('floatval', array_pad(explode(',', $koordinat, 2), 2, 0));

        if ($lat == 0.0 || $lng == 0.0) {
            return false;
        }

        $pemilik = KecamatanGeo::ownerOf($lat, $lng);

        return $pemilik !== null && strcasecmp($pemilik, $kecamatan) !== 0;
    }
}
