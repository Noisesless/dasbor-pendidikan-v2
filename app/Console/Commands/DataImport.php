<?php

namespace App\Console\Commands;

use App\Models\Population;
use App\Models\School;
use App\Models\StudentStat;
use App\Models\Teacher;
use App\Models\TeacherStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenSpout\Reader\XLSX\Reader;

class DataImport extends Command
{
    protected $signature = 'data:import';

    protected $description = 'Import semua data Dapodik (CSV per-semester + Excel) ke database';

    private array $schoolIdByNpsn = [];

    /** @var array<string, array{nama_sekolah:string, jenjang:string, kecamatan:string}> */
    private array $schoolInfoByNpsn = [];

    /** @var array<string, string> kode kecamatan (dari CSV) => nama kecamatan */
    private array $kecamatanByCode = [];

    public function handle(): int
    {
        $dataDir = storage_path('app/data');
        $semesters = ['20241', '20242', '20251', '20252', '20261'];

        if (!is_dir($dataDir)) {
            $this->error("Direktori data tidak ditemukan: {$dataDir}");

            return self::FAILURE;
        }

        $this->importSchools($dataDir);
        $this->importStudentStats($dataDir, $semesters);
        $this->importTeacherStats($dataDir, $semesters);
        $this->importTeachers($dataDir . '/data_guru_pendidik_banjarnegara.xlsx');
        $this->importPopulations($dataDir . '/data_penduduk_usia_sekolah_2026.xlsx');

        // Selaraskan koordinat sekolah dengan batas kecamatan di GeoJSON.
        $this->newLine();
        $this->call('peta:fix-coordinates');

        $this->newLine();
        $this->info('Import data selesai.');

        return self::SUCCESS;
    }

    private function importSchools(string $dir): void
    {
        $this->info('Mengimpor sekolah (master)...');
        Schema::disableForeignKeyConstraints();
        DB::table('schools')->truncate();
        Schema::enableForeignKeyConstraints();

        $rows = $this->readCsv($dir . '/20261_Dapodik_Sekolah.csv');
        $count = 0;
        $now = now();

        foreach ($rows as $r) {
            $npsn = trim((string) ($r['npsn'] ?? ''));
            if ($npsn === '') {
                continue;
            }

            $kodeKec = trim((string) ($r['kode_kecamatan_simak'] ?? ''));
            $namaKec = trim((string) ($r['nama_kecamatan'] ?? ''));
            if ($kodeKec !== '' && $namaKec !== '') {
                $this->kecamatanByCode[$kodeKec] = $namaKec;
            }

            School::create([
                'npsn' => $npsn,
                'nama_sekolah' => trim((string) ($r['nama_sekolah'] ?? '')),
                'jenjang' => trim((string) ($r['jenjang'] ?? '')),
                'status' => trim((string) ($r['status'] ?? '')),
                'akreditasi' => $this->nullable($r['akreditasi'] ?? null),
                'kecamatan' => $namaKec,
                'alamat' => $this->nullable($r['alamat'] ?? null),
                'kepala_sekolah' => $this->nullable($r['kepala_sekolah'] ?? null),
                'telepon' => $this->nullable($r['telepon'] ?? null),
                'latitude' => is_numeric($r['latitude'] ?? null) ? (float) $r['latitude'] : null,
                'longitude' => is_numeric($r['longitude'] ?? null) ? (float) $r['longitude'] : null,
            ]);
            $count++;
        }

        $this->schoolIdByNpsn = School::pluck('id', 'npsn')->all();
        $this->schoolInfoByNpsn = School::query()
            ->get(['npsn', 'nama_sekolah', 'jenjang', 'kecamatan'])
            ->mapWithKeys(fn ($s) => [$s->npsn => [
                'nama_sekolah' => $s->nama_sekolah,
                'jenjang' => $s->jenjang,
                'kecamatan' => $s->kecamatan,
            ]])
            ->all();
        $this->line("  -> {$count} sekolah");
    }

    private function importStudentStats(string $dir, array $semesters): void
    {
        $this->info('Mengimpor statistik siswa per semester...');
        Schema::disableForeignKeyConstraints();
        DB::table('student_stats')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $buffer = [];

        foreach ($semesters as $sem) {
            $path = "{$dir}/{$sem}_Dapodik_Siswa_Rekap.csv";
            if (!is_file($path)) {
                $this->warn("  Lewati (tidak ada): {$sem}");
                continue;
            }

            foreach ($this->readCsv($path) as $r) {
                $npsn = trim((string) ($r['npsn'] ?? ''));
                if ($npsn === '') {
                    continue;
                }

                $info = $this->schoolInfoByNpsn[$npsn] ?? null;
                $kodeKec = trim((string) ($r['kode_kecamatan_simak'] ?? ''));

                $buffer[] = [
                    'school_id' => $this->schoolIdByNpsn[$npsn] ?? null,
                    'semester' => $sem,
                    'npsn' => $npsn,
                    'nama_sekolah' => trim((string) ($r['nama_sekolah'] ?? '')),
                    'jenjang' => $info['jenjang'] ?? trim((string) ($r['jenjang'] ?? '')),
                    'kecamatan' => $info['kecamatan'] ?? ($this->kecamatanByCode[$kodeKec] ?? ''),
                    'total_siswa' => (int) ($r['total_siswa_semua_usia'] ?? $r['total_siswa'] ?? 0),
                    'total_laki' => (int) ($r['siswa_l'] ?? $r['total_laki'] ?? 0),
                    'total_perempuan' => (int) ($r['siswa_p'] ?? $r['total_perempuan'] ?? 0),
                    'usia_sesuai' => (int) ($r['siswa_usia_sesuai'] ?? $r['usia_sesuai'] ?? 0),
                    'usia_sesuai_laki' => (int) ($r['siswa_usia_sesuai_l'] ?? $r['usia_sesuai_laki'] ?? 0),
                    'usia_sesuai_perempuan' => (int) ($r['siswa_usia_sesuai_p'] ?? $r['usia_sesuai_perempuan'] ?? 0),
                    'usia_5_6' => (int) ($r['siswa_usia_5_6'] ?? $r['usia_5_6'] ?? 0),
                    'usia_7_12' => (int) ($r['siswa_usia_7_12'] ?? $r['usia_7_12'] ?? 0),
                    'usia_13_15' => (int) ($r['usia_13_15'] ?? 0),
                    'usia_16_18' => (int) ($r['usia_16_18'] ?? 0),
                    'jumlah_rombel' => (int) ($r['jumlah_rombel'] ?? 0),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->line("  -> semester {$sem}: ".count($buffer).' baris kumulatif');
        }

        foreach (array_chunk($buffer, 500) as $chunk) {
            DB::table('student_stats')->insert($chunk);
        }

        $this->line("  -> total: <info>".count($buffer).'</info> baris statistik siswa');
    }

    private function importTeacherStats(string $dir, array $semesters): void
    {
        $this->info('Mengimpor statistik guru per semester...');
        Schema::disableForeignKeyConstraints();
        DB::table('teacher_stats')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $buffer = [];

        // Cache info sekolah (nama/jenjang/kecamatan) berdasarkan NPSN,
        // karena CSV Guru_PTK berisi satu baris per guru tanpa kolom jenjang/kecamatan.
        $schools = DB::table('schools')->get(['npsn', 'nama_sekolah', 'jenjang', 'kecamatan'])
            ->keyBy(fn ($s) => trim((string) $s->npsn));

        foreach ($semesters as $sem) {
            $path = "{$dir}/{$sem}_Dapodik_Guru_PTK.csv";
            if (!is_file($path)) {
                $this->warn("  Lewati (tidak ada): {$sem}");
                continue;
            }

            // CSV Guru_PTK = satu baris per guru -> agregasi dulu per NPSN.
            $perSchool = [];

            foreach ($this->readCsv($path) as $r) {
                $npsn = trim((string) ($r['npsn'] ?? ''));
                if ($npsn === '') {
                    continue;
                }

                if (!isset($perSchool[$npsn])) {
                    $perSchool[$npsn] = [
                        'nama_sekolah' => trim((string) ($r['nama_sekolah'] ?? '')),
                        'kode_kecamatan' => trim((string) ($r['kode_kecamatan_simak'] ?? '')),
                        'jumlah_guru' => 0,
                        'jumlah_sertifikasi' => 0,
                        'jumlah_pns' => 0,
                        'jumlah_pppk' => 0,
                        'jumlah_gty' => 0,
                        'jumlah_gtt' => 0,
                        'jumlah_honor' => 0,
                    ];
                }

                $perSchool[$npsn]['jumlah_guru']++;

                if ((int) ($r['sudah_sertifikasi'] ?? 0) === 1) {
                    $perSchool[$npsn]['jumlah_sertifikasi']++;
                }

                $status = strtoupper(trim((string) ($r['status_kepegawaian'] ?? '')));

                match (true) {
                    $status === 'PNS' => $perSchool[$npsn]['jumlah_pns']++,
                    $status === 'PPPK' => $perSchool[$npsn]['jumlah_pppk']++,
                    $status === 'GTY' => $perSchool[$npsn]['jumlah_gty']++,
                    $status === 'GTT' => $perSchool[$npsn]['jumlah_gtt']++,
                    $status === 'HONOR' => $perSchool[$npsn]['jumlah_honor']++,
                    default => null,
                };
            }

            foreach ($perSchool as $npsn => $c) {
                $school = $schools->get($npsn);
                $kecamatan = $school->kecamatan
                    ?? ($this->kecamatanByCode[$c['kode_kecamatan']] ?? '');

                $buffer[] = [
                    'school_id' => $this->schoolIdByNpsn[$npsn] ?? null,
                    'semester' => $sem,
                    'npsn' => $npsn,
                    'nama_sekolah' => $school->nama_sekolah ?? $c['nama_sekolah'],
                    'jenjang' => $school->jenjang ?? '',
                    'kecamatan' => $kecamatan,
                    'jumlah_guru' => $c['jumlah_guru'],
                    'jumlah_sertifikasi' => $c['jumlah_sertifikasi'],
                    'jumlah_pns' => $c['jumlah_pns'],
                    'jumlah_pppk' => $c['jumlah_pppk'],
                    'jumlah_gty' => $c['jumlah_gty'],
                    'jumlah_gtt' => $c['jumlah_gtt'],
                    'jumlah_honor' => $c['jumlah_honor'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->line("  -> semester {$sem}: ".count($perSchool).' sekolah');
        }

        foreach (array_chunk($buffer, 500) as $chunk) {
            DB::table('teacher_stats')->insert($chunk);
        }

        $this->line("  -> total: <info>".count($buffer).'</info> baris statistik guru');
    }

    private function importTeachers(string $path): void
    {
        $this->info('Mengimpor data guru (individu)...');
        Schema::disableForeignKeyConstraints();
        DB::table('teachers')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $buffer = [];

        foreach ($this->readXlsx($path) as $i => $r) {
            if ($i === 0) {
                continue; // header
            }

            $npsn = trim((string) ($r[6] ?? ''));
            $nama = trim((string) ($r[1] ?? ''));
            $status = trim((string) ($r[9] ?? ''));

            if ($nama === '' || $nama === 'Nama Lengkap' || $status === 'Status Kepegawaian') {
                continue; // baris kosong atau header duplikat
            }

            $buffer[] = [
                'school_id' => $this->schoolIdByNpsn[$npsn] ?? null,
                'nama_lengkap' => $nama,
                'nip' => $this->nullable($r[2] ?? null),
                'nuptk' => $this->nullable($r[3] ?? null),
                'jenis_kelamin' => strtoupper(trim((string) ($r[4] ?? ''))) ?: null,
                'satuan_pendidikan' => $this->nullable($r[5] ?? null),
                'npsn' => $npsn ?: null,
                'jenjang' => trim((string) ($r[7] ?? '')),
                'kecamatan' => trim((string) ($r[8] ?? '')),
                'status_kepegawaian' => trim((string) ($r[9] ?? '')),
                'sertifikasi' => $this->nullable($r[10] ?? null),
                'bidang_studi' => $this->nullable($r[11] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($buffer, 500) as $chunk) {
            DB::table('teachers')->insert($chunk);
        }

        $this->line("  -> total: <info>".count($buffer).'</info> guru');
    }

    private function importPopulations(string $path): void
    {
        $this->info('Mengimpor data penduduk usia sekolah...');
        Schema::disableForeignKeyConstraints();
        DB::table('populations')->truncate();
        Schema::enableForeignKeyConstraints();

        $now = now();
        $buffer = [];

        foreach ($this->readXlsx($path) as $i => $r) {
            if ($i === 0) {
                continue; // header
            }

            $kecamatan = trim((string) ($r[2] ?? ''));
            if ($kecamatan === '' || stripos($kecamatan, 'kecamatan') === 0) {
                continue; // baris header duplikat
            }

            $tahun = (int) ($r[3] ?? 0);
            if ($tahun <= 0) {
                continue; // tahun tidak valid (header/blank row)
            }

            $buffer[] = [
                'kecamatan' => $kecamatan,
                'tahun' => $tahun,
                'u04_l' => (int) ($r[4] ?? 0),
                'u04_p' => (int) ($r[5] ?? 0),
                'u04_t' => (int) ($r[6] ?? 0),
                'u56_l' => (int) ($r[7] ?? 0),
                'u56_p' => (int) ($r[8] ?? 0),
                'u56_t' => (int) ($r[9] ?? 0),
                'u712_l' => (int) ($r[10] ?? 0),
                'u712_p' => (int) ($r[11] ?? 0),
                'u712_t' => (int) ($r[12] ?? 0),
                'u1315_l' => (int) ($r[13] ?? 0),
                'u1315_p' => (int) ($r[14] ?? 0),
                'u1315_t' => (int) ($r[15] ?? 0),
                'u1618_l' => (int) ($r[16] ?? 0),
                'u1618_p' => (int) ($r[17] ?? 0),
                'u1618_t' => (int) ($r[18] ?? 0),
                'u18_t' => (int) ($r[19] ?? 0),
                'total_penduduk' => (int) ($r[20] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('populations')->insert($buffer);
        $this->line("  -> total: <info>".count($buffer).'</info> kecamatan');
    }

    private function readCsv(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            return;
        }

        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return;
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $row);
            yield array_combine($headers, array_pad($row, count($headers), null));
        }

        fclose($handle);
    }

    private function readXlsx(string $path): \Generator
    {
        $reader = new Reader();
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                yield $row->toArray();
            }
            break; // only first sheet
        }

        $reader->close();
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
