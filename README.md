# Dasbor Pendidikan Kabupaten Banjarnegara

Aplikasi analitik dan pemetaan satuan pendidikan, peserta didik, pendidik, serta indikator makro pendidikan Kabupaten Banjarnegara berbasis data Dapodik, EMIS Kemenag, Disarpus, dan BPS.

## Fitur Utama
- **Ringkasan Eksekutif (`/`)**: Agregasi satuan pendidikan, peserta didik, pendidik, rombongan belajar, dan rasio murid per guru lintas semester dan kecamatan.
- **Rekomendasi untuk Kepala Dinas (`/rekomendasi`)**: Sintesis prioritas aksi eksekutif lintas indikator disertai diagnostik khusus cakupan data guru **MI dan MTs** (perbandingan rasio sekolah umum 15,8:1 vs rasio gabungan 19,6:1).
- **Chatbot Asisten Data Pendidikan (`/chatbot/ask`)**: Asisten analitik berbasis **Hybrid RAG (Online Gemini API + Offline Basis Data Lokal)** yang terbatas pada lingkup data pendidikan Kabupaten Banjarnegara dan menerangkan rincian rekomendasi serta keterbatasan data guru MI & MTs.
- **Demografi Peserta Didik (`/demografi`)**: Distribusi siswa menurut kelompok usia sekolah (5–6, 7–12, 13–15, 16–18 tahun), komposisi jenis kelamin, dan proyeksi penduduk usia sekolah.
- **Infrastruktur & Satuan Pendidikan (`/infrastruktur`)**: Sebaran sekolah negeri dan swasta per jenjang (PAUD, TK, SD, SMP, SMA, SMK, MI, MTs, MA, PKBM) dan profil akreditasi.
- **Ketenagakerjaan Guru (`/guru`)**: Komposisi status kepegawaian (PNS, PPPK, GTY, GTT, Honor), tingkat sertifikasi pendidik, dan beban mengajar per wilayah.
- **Peta Sebaran Spasial (`/peta`)**: Peta interaktif mode Choropleth & Heatmap kepadatan siswa pada batas administrasi 20 kecamatan (GeoJSON) serta titik koordinat 2.054 sekolah menggunakan Leaflet dan OpenStreetMap Contributors.
- **Modul Analisis Mendalam (`/analisis/*`)**:
  - Analisis Gap Layanan (`/analisis/gap`)
  - Risiko Angka Putus Sekolah (`/analisis/angka-keluar`)
  - Literasi & Perpustakaan Daerah (`/analisis/literasi`)
  - Pendidikan Keagamaan & Madrasah (`/analisis/madrasah`)
  - Indikator Makro Pendidikan — APK, APM, RLS, HLS, IPM (`/analisis/indikator`)
  - Akreditasi PAUD & Pendidikan Anak Usia Dini (`/analisis/akreditasi-paud`)
  - Katalog Walidata Pendidikan (`/analisis/walidata`)
  - Rekomendasi Kebijakan Daerah (`/analisis/kebijakan`)
  - Analisis Kualitas Data (`/analisis/data-health`)
  - Analisis Sarana & Anggaran (`/analisis/sarana`)
  - Analisis Kandidat Regrouping Sekolah (`/analisis/regrouping`)

## Tech Stack
- **Frontend:** Blade Templates, Tailwind CSS v4 (`@tailwindcss/vite`), Alpine.js 3.14, Chart.js 4, Leaflet 1.9.4 (OpenStreetMap Contributors)
- **Backend:** PHP 8.3+ / 8.5, Laravel 13.x (`laravel/framework ^13.17`), OpenSpout v5 (`openspout/openspout ^5.13`)
- **Database:** SQLite / MariaDB / MySQL

## Prasyarat
- PHP >= 8.3 (ekstensi `pdo_sqlite` / `pdo_mysql`, `mbstring`, `xml`, `zip`, `bcmath`)
- Composer 2.x
- Node.js >= 20.x & npm

## Instalasi Lokal
1. Salin berkas environment:
   ```bash
   cp .env.example .env
   ```
2. Isi nilai variabel di `.env` sesuai konfigurasi lokal Anda.
3. Instal dependensi, jalankan migrasi, dan impor data Dapodik/Master:
   ```bash
   composer install
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force
   php artisan data:import
   npm install --ignore-scripts
   npm run build
   ```
4. Jalankan server development:
   ```bash
   php artisan serve --port=8080
   ```

## Perintah Artisan Utama
| Perintah | Fungsi |
|---|---|
| `php artisan data:import` | Mengimpor seluruh data Dapodik (CSV multi-semester & Excel) ke tabel `schools`, `student_stats`, `teacher_stats`, `teachers`, dan `populations` |
| `php artisan masterdata:import` | Mengimpor data pengaya lintas instansi (Dindikpora, Kemenag, BPS) ke tabel `regional_education_stats` dan `regional_indicators` |
| `php artisan simudik:import` | Melengkapi direktori satuan pendidikan (termasuk nomor telepon sekolah) dari data publik SIMU-DIK Banjarnegara (`storage/app/data/simudik/*.json`) |
| `php artisan peta:fix-coordinates` | Memvalidasi dan mengoreksi titik koordinat sekolah agar presisi di dalam poligon GeoJSON masing-masing kecamatan |

## Variabel Environment
> Nilai asli hanya disimpan di berkas `.env` lokal/server (tidak di-commit). Lihat `.env.example` untuk daftar lengkap.

| Variabel | Wajib | Sumber Nilai | Keterangan |
|---|---|---|---|
| `APP_ENV` | Ya | Konfigurasi environment | Mode aplikasi (`local` atau `production`) |
| `APP_DEBUG` | Ya | Konfigurasi environment | `true` untuk lokal, `false` untuk produksi |
| `APP_URL` | Ya | Domain aplikasi | URL dasar aplikasi |
| `DB_CONNECTION` | Ya | Konfigurasi database | Driver database (`sqlite` atau `mysql`) |
| `DB_DATABASE` | Ya | Konfigurasi database | Path berkas SQLite atau nama database MySQL/MariaDB |
| `SEED_ADMIN_EMAIL` | Opsional | Administrator sistem | Email akun awal saat menjalankan database seeder |
| `SEED_ADMIN_PASSWORD` | Opsional | Administrator sistem | Kata sandi akun awal saat menjalankan database seeder |
| `GEMINI_API_KEY` | Opsional | Google AI Studio | Kunci API Gemini untuk mode Online RAG pada Chatbot (otomatis beralih ke Offline RAG jika kosong) |
| `GEMINI_MODEL` | Opsional | Konfigurasi AI | Model Gemini yang digunakan (default: `gemini-3.5-flash-lite`) |

## Akun Awal (Seeder)
Akun awal dibuat melalui database seeder menggunakan variabel `SEED_ADMIN_EMAIL` dan `SEED_ADMIN_PASSWORD` yang diatur pada berkas `.env`. Jangan gunakan kredensial development di lingkungan produksi.
