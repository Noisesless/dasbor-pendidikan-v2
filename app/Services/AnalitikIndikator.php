<?php

namespace App\Services;

/**
 * Loader untuk storage/app/data/analitik-indikator.json.
 *
 * Port dari data indikator pendidikan pada aplikasi dashboard-pendidikan
 * (halaman indikator.tsx dan paud.tsx).
 */
class AnalitikIndikator
{
    /**
     * Cache hasil json_decode.
     */
    private ?array $data = null;

    /**
     * Muat seluruh isi file JSON.
     */
    public function all(): array
    {
        if ($this->data === null) {
            $path = storage_path('app/data/analitik-indikator.json');

            if (!is_file($path)) {
                return [];
            }

            $content = (string) file_get_contents($path);
            $content = str_replace("\xEF\xBB\xBF", '', $content);

            $this->data = json_decode($content, true) ?? [];
        }

        return $this->data;
    }

    /**
     * Ringkasan utama: sekolah, siswa, guru, penduduk, tahun, dll.
     */
    public function ringkasan(): array
    {
        return $this->all()['ringkasan'] ?? [];
    }

    /**
     * Ringkasan per tahun.
     */
    public function ringkasanPerTahun(): array
    {
        return $this->all()['ringkasan_per_tahun'] ?? [];
    }

    /**
     * Rekap per kecamatan (sekolah, siswa, apk, apm, aps, guru, dll).
     */
    public function rekapKecamatan(): array
    {
        return $this->all()['rekap_kecamatan'] ?? [];
    }

    /**
     * Indikator per kecamatan (versi lengkap).
     */
    public function indikatorPerKecamatan(): array
    {
        return $this->all()['indikator_per_kecamatan'] ?? [];
    }

    /**
     * Tren siswa: {tahun: {jenjang: jumlah}}.
     */
    public function trenSiswa(): array
    {
        return $this->all()['tren_siswa'] ?? [];
    }

    /**
     * Penduduk per kelompok usia: {label: jumlah}.
     */
    public function pendudukPerUsia(): array
    {
        return $this->all()['penduduk_per_usia'] ?? [];
    }

    /**
     * Jumlah sekolah per jenjang: {jenjang: jumlah}.
     */
    public function sekolahPerJenjang(): array
    {
        return $this->all()['sekolah_per_jenjang'] ?? [];
    }

    /**
     * Jumlah siswa per jenjang: {jenjang: jumlah}.
     */
    public function siswaPerJenjang(): array
    {
        return $this->all()['siswa_per_jenjang'] ?? [];
    }

    /**
     * Jumlah guru per status: {status: jumlah}.
     */
    public function guruPerStatus(): array
    {
        return $this->all()['guru_per_status'] ?? [];
    }

    /**
     * Prestasi: total, per_bidang, per_tingkat, per_peringkat.
     */
    public function prestasi(): array
    {
        return $this->all()['prestasi'] ?? [];
    }

    /**
     * Akreditasi PAUD: total_sekolah, total_a, total_b, total_c,
     * total_belum, per_kecamatan.
     */
    public function akreditasiPaud(): array
    {
        return $this->all()['akreditasi_paud'] ?? [];
    }
}
