# Spesifikasi Endpoint & Controller — Dasbor Pendidikan Kabupaten Banjarnegara v2

Aplikasi ini menggunakan arsitektur **Server-Side Rendered (SSR) Blade Controllers** di mana seluruh rute mengembalikan respons HTML terautentikasi atau pengalihan sesi (*redirect*), dilengkapi parameter query filter standar.

---

## 1. Parameter Query Global (Filter Analitik)
Diterima oleh rute `/`, `/demografi`, `/infrastruktur`, `/guru`, dan `/peta` melalui `App\Support\Filters::fromRequest(Request $request)`:

| Parameter | Tipe | Nilai Default | Contoh Nilai | Keterangan |
|---|---|---|---|---|
| `semester` | `string` | `2025/2026 Ganjil` | `2024/2025 Genap` | Periode semester Dapodik (`YYYY/YYYY Ganjil/Genap`) |
| `kecamatan` | `string` | `""` (Semua) | `Banjarnegara`, `Bawang` | Filter nama kecamatan (20 kecamatan) |
| `jenjang` | `string` | `""` (Semua) | `SD`, `SMP`, `SMA`, `SMK`, `TK`, `PAUD` | Filter jenjang satuan pendidikan |

---

## 2. Daftar Endpoint HTTP (`routes/web.php`)

### A. Autentikasi
| Method | URI | Route Name | Middleware | Controller & Action | Deskripsi Respons |
|---|---|---|---|---|---|
| `GET` | `/login` | `login` | `web`, `guest` | `AuthController@showLogin` | Menampilkan halaman formulir masuk (`200 OK`) |
| `POST` | `/login` | — | `web`, `guest` | `AuthController@login` | Memvalidasi `email` & `password`, regenerasi session ID, redirect ke `/` (`302 Found`) |
| `POST` | `/logout` | `logout` | `web`, `auth` | `AuthController@logout` | Menghapus sesi aktif, invalidate token CSRF, redirect ke `/login` (`302 Found`) |

### B. Modul Utama Dasbor
| Method | URI | Route Name | Middleware | Controller & Action | Data Utama yang Dikirim ke View |
|---|---|---|---|---|---|
| `GET` | `/` | `dashboard` | `web`, `auth` | `DashboardController@index` | KPI agregat sekolah, siswa, guru, rombel, rasio murid/guru, distribusi per jenjang & kecamatan, tren antar-semester |
| `GET` | `/rekomendasi` | `rekomendasi` | `web`, `auth` | `RekomendasiController@index` | Ringkasan rekomendasi prioritas untuk Kepala Dinas berdasarkan sintesis `AdvancedAnalysisController` |
| `GET` | `/demografi` | `demografi` | `web`, `auth` | `DemografiController@index` | Distribusi siswa per kelompok usia (`5-6`, `7-12`, `13-15`, `16-18`), perbandingan jenis kelamin (`L`/`P`), ketepatan usia sekolah |
| `GET` | `/infrastruktur` | `infrastruktur` | `web`, `auth` | `InfrastrukturController@index` | Rekap sekolah negeri vs swasta, komposisi akreditasi (`A`, `B`, `C`, `Belum`), daftar detail satuan pendidikan |
| `GET` | `/guru` | `guru` | `web`, `auth` | `GuruController@index` | Statistik status kepegawaian (`PNS`, `PPPK`, `GTY`, `GTT`, `Honor`), proporsi sertifikasi, direktori pendidik (`teachers`) |
| `GET` | `/peta` | `peta` | `web`, `auth` | `PetaController@index` | Koleksi titik koordinat sekolah (`$sekolah` beserta bobot siswa untuk heatmap), rekap per kecamatan (`$kecamatanStats`), dan poligon GeoJSON (`$geojson`) |

### C. Modul Analisis Kebijakan (`/analisis/*`)
| Method | URI | Route Name | Middleware | Controller & Action | Layanan Pendukung (`App\Services` / `Controllers`) |
|---|---|---|---|---|---|
| `GET` | `/analisis/gap` | `gap` | `web`, `auth` | `AnalysisController@gap` | `AnalitikIndikator`, `DindikporaParser` — Kesenjangan daya tampung & rasio pendidik |
| `GET` | `/analisis/angka-keluar` | `keluar` | `web`, `auth` | `AnalysisController@dropout` | `AnalitikIndikator` — Indikasi putus sekolah & selisih partisipasi usia |
| `GET` | `/analisis/literasi` | `literasi` | `web`, `auth` | `AnalysisController@literacy` | `DisarpusParser` — Kunjungan perpustakaan, anggota baru, dan koleksi buku |
| `GET` | `/analisis/madrasah` | `madrasah` | `web`, `auth` | `AnalysisController@madrasah` | `KemenagParser` — Statistik RA, MI, MTs, dan MA per kecamatan |
| `GET` | `/analisis/indikator` | `indikator` | `web`, `auth` | `AnalysisController@indicator` | `AnalitikIndikator` — Tren APK, APM, HLS, RLS, dan IPM Kabupaten Banjarnegara |
| `GET` | `/analisis/akreditasi-paud` | `paud` | `web`, `auth` | `AnalysisController@paud` | `AnalitikIndikator` — Profil mutu dan akreditasi PAUD/TK/RA per kecamatan |
| `GET` | `/analisis/walidata` | `walidata` | `web`, `auth` | `AnalysisController@walidata` | `WalidataPendidikan` — Katalog indikator sektoral Walidata Pendidikan |
| `GET` | `/analisis/kebijakan` | `kebijakan` | `web`, `auth` | `AnalysisController@kebijakan` | `AnalitikIndikator` — Matriks skor prioritas intervensi kebijakan per kecamatan |
| `GET` | `/analisis/data-health` | `data-health` | `web`, `auth` | `AdvancedAnalysisController@dataHealth` | Audit kelengkapan & anomali data pokok sekolah per kecamatan |
| `GET` | `/analisis/sarana` | `sarana` | `web`, `auth` | `AdvancedAnalysisController@sarana` | Analisis kondisi ruang kelas, fasilitas sekolah, dan estimasi kebutuhan anggaran |
| `GET` | `/analisis/regrouping` | `regrouping` | `web`, `auth` | `AdvancedAnalysisController@regrouping` | Analisis pasangan kandidat regrouping SD berdasarkan jarak spasial Haversine & jumlah siswa |

### D. Chatbot Analitik Pendidikan (Hybrid RAG)
| Method | URI | Route Name | Middleware | Controller & Action | Deskripsi Respons |
|---|---|---|---|---|---|
| `POST` | `/chatbot/ask` | `chatbot.ask` | `web`, `auth`, `throttle:30,1` | `ChatbotController@ask` | Menerima JSON `{ question, mode: "auto"\|"online"\|"offline", history }`, mengambil fakta dari `EducationRagService`, dan mengembalikan `{ answer, mode_used, sources, follow_ups }` (`200 OK`) |

