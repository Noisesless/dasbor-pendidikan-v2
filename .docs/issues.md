# Issue Tracker — Dasbor Pendidikan Kabupaten Banjarnegara v2
<!-- FIFO Rolling Buffer: Max 10 RESOLVED issues -->

## Status Aktif (`OPEN` / `IN_PROGRESS`)
*Tidak ada isu aktif (`0 OPEN`, `0 IN_PROGRESS`).*

---

## Riwayat Isu Terselesaikan (`RESOLVED`)

### [ISSUE-004] UX Chatbot Terlihat "Kembali ke Awal / Tidak Menjawab", Debug Mode di UI Publik, dan Kueri RAG Statis
- **Status:** `RESOLVED`
- **Tanggal:** 2026-11-07
- **Komponen Terdampak:**
  - [`resources/views/partials/chatbot.blade.php`](../resources/views/partials/chatbot.blade.php)
  - [`app/Services/EducationRagService.php`](../app/Services/EducationRagService.php)
- **Akar Masalah:**
  1. Tombol saran pertanyaan bawaan (`follow_ups`) dirender berulang di bawah setiap balon jawaban bot (`x-for="(q, qIdx) in msg.followUps"`). Hal ini menciptakan ilusi optik seolah chatbot mengulang pesan pembuka dan menutupi teks jawaban pengguna yang berada di atasnya.
  2. Bilah horizontal `DEBUG MODE (PROD)` dan tombol toggle `< />` di header chatbot masih tampil di antarmuka produksi sehingga mengurangi kerapian visual bagi pengguna umum.
  3. Logika kueri analitik offline dan deteksi semester masih mengacu pada konstanta baseline statis (`20252`), sementara data mutakhir di tabel `student_stats` sudah mencapai semester `20261`.
- **Solusi yang Diterapkan:**
  1. Membatasi rendering tombol saran pertanyaan bawaan **hanya pada pesan pembuka awal (`idx === 0`)**. Pada giliran percakapan berikutnya (`idx > 0`), tombol template dihapus total sehingga pengguna langsung melihat jawaban bot dengan jelas.
  2. Menghapus seluruh tombol toggle debug mode, bilah status latensi server, dan badge debug dari antarmuka pengguna demi tampilan publik yang bersih (*clean user-facing UI*).
  3. Mengubah penentuan semester menjadi dinamis via method `getActiveSemester()` (`DB::table('student_stats')->max('semester') ?: self::BASELINE_SEMESTER`), serta menambahkan kueri agregasi dinamis untuk kombinasi jenjang per kecamatan, jenjang kabupaten, status kepegawaian guru, akreditasi sekolah, dan indikator makro BPS tanpa angka hardcode.

### [ISSUE-003] HTTP 403 Forbidden pada Static Asset Bundling Vite (`public/build/assets`)
- **Status:** `RESOLVED`
- **Tanggal:** 2026-11-07
- **Komponen Terdampak:**
  - `public/build/assets/app-CFKmqfuN.css`
  - `public/build/assets/app-BzDmb3Yz.js`
- **Akar Masalah:**
  File artefak kompilasi Vite yang diunggah ke server produksi mewarisi izin direktori `drwx---rwx` dengan grup `siste563476kan:siste563476kan`. Karena pekerja Nginx (`www-data`) merupakan anggota grup sekunder `siste563476kan`, evaluasi izin POSIX DAC mencocokkan grup file (`---` / tanpa izin eksekusi dan baca) sehingga Nginx memblokir akses file aset dengan status `403 Forbidden`.
- **Solusi yang Diterapkan:**
  1. Menyeragamkan kepemilikan seluruh berkas dan folder di `public/build/` ke `siste563476kan:siste563476kan`.
  2. Memberikan izin traversal `775` (`drwxrwxr-x`) pada seluruh subdirektori dan izin baca `664` (`-rw-rw-r--`) pada berkas aset statis di `public/`.
  3. Memvalidasi ketersediaan berkas via HTTP GET ke Nginx produksi (mengembalikan `HTTP 200 OK`).

### [ISSUE-002] Penyesuaian Chatbot RAG: CSRF Mismatch, Markdown Rendering, dan Pemisahan Follow-Up Sapaan
- **Status:** `RESOLVED`
- **Tanggal:** 2026-11-07
- **Komponen Terdampak:**
  - [`bootstrap/app.php`](../bootstrap/app.php)
  - [`resources/views/partials/chatbot.blade.php`](../resources/views/partials/chatbot.blade.php)
  - [`app/Services/EducationRagService.php`](../app/Services/EducationRagService.php)
  - [`app/Http/Controllers/ChatbotController.php`](../app/Http/Controllers/ChatbotController.php)
- **Akar Masalah:**
  1. Permintaan POST ke `/chatbot/ask` terkadang memicu HTTP 419 jika session kedaluwarsa atau token CSRF statis tidak tersinkronisasi.
  2. Teks jawaban Gemini mengandung tabel Markdown dan list yang belum memiliki parser aman dan fail-safe di Alpine.js.
  3. Pada pertanyaan santai/sapaan ("halo"), panel debug tebal dan jawaban panjang mendorong bubble pengguna keluar layar, sementara tombol saran bawaan mengembalikan 3 tombol yang persis sama dengan pesan pembuka sehingga pengguna merasa percakapan di-reset.
- **Solusi yang Diterapkan:**
  1. Mengecualikan `chatbot/ask` dari verifikasi CSRF statis di `bootstrap/app.php` dengan tetap mewajibkan middleware `auth` dan *rate limiter*.
  2. Menambahkan parser Markdown komprehensif (heading `###`, tabel pipe `|`, list `*` dan `1.`, horizontal rule) dengan penanganan try-catch di Alpine.js.
  3. Menambahkan label pengirim (`Anda • [HH:mm]` vs `Asisten Data • [HH:mm]`), merampingkan bar debug produksi ke 1 baris kompak, menyempurnakan auto-scroll, dan mendiversifikasi pertanyaan follow-up untuk kueri sapaan/umum.

### [ISSUE-001] Tile Peta OpenStreetMap Mengembalikan Error `403 Access blocked` pada `/peta`
- **Status:** `RESOLVED`
- **Tanggal:** 2026-11-07
- **Komponen Terdampak:**
  - [`resources/views/dashboard/peta.blade.php`](../resources/views/dashboard/peta.blade.php#L171-L175)
  - [`resources/views/layouts/app.blade.php`](../resources/views/layouts/app.blade.php#L6)
- **Akar Masalah:**
  Kebijakan penggunaan tile server resmi OpenStreetMap (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`) mewajibkan browser menyertakan header `Referer` pada setiap permintaan gambar lintas domain. Konfigurasi header `Referrer-Policy: same-origin` pada server web memotong header `Referer` untuk domain eksternal sehingga server OpenStreetMap menolak permintaan dengan status `403 Access blocked`.
- **Solusi yang Diterapkan:**
  1. Menambahkan `<meta name="referrer" content="strict-origin-when-cross-origin">` pada `<head>` di [`resources/views/layouts/app.blade.php`](../resources/views/layouts/app.blade.php#L6).
  2. Mengubah header `Referrer-Policy` pada Nginx dari `same-origin` menjadi `strict-origin-when-cross-origin`.
  3. Mempertahankan tile layer murni **OpenStreetMap Contributors** (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`) di [`resources/views/dashboard/peta.blade.php`](../resources/views/dashboard/peta.blade.php#L171-L175) tanpa ketergantungan API key pihak ketiga.

