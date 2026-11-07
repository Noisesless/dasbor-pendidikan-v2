# Panduan Deployment Produksi — Dasbor Pendidikan Kabupaten Banjarnegara v2

> **Peringatan Keamanan (SP-032 & SP-033):** Dokumen ini tidak memuat nilai kredensial, kata sandi, kunci rahasia, maupun informasi sensitif server produksi. Seluruh nilai rahasia wajib dikonfigurasi langsung pada berkas `.env` di server target.

---

## 1. Spesifikasi Lingkungan Server
- **Web Server:** Nginx (dengan dukungan HTTP/2 & SSL/TLS Let's Encrypt)
- **Runtime PHP:** PHP 8.3 / 8.4 / 8.5 (`php-fpm`, `pdo_sqlite` / `pdo_mysql`, `mbstring`, `xml`, `zip`, `bcmath`, `intl`)
- **Database:** SQLite (`database/database.sqlite` dengan izin tulis bagi user PHP-FPM) atau MariaDB/MySQL
- **Build Tool Frontend:** Node.js >= 20.x & Vite (`npm run build` menghasilkan `public/build/manifest.json`)

---

## 2. Daftar Variabel Environment
| Variabel | Wajib | Sumber Nilai | Keterangan |
|---|---|---|---|
| `APP_NAME` | Ya | Konfigurasi aplikasi | Nama aplikasi pada header & sesi |
| `APP_ENV` | Ya | Konfigurasi server | Wajib diset ke `production` di server produksi |
| `APP_KEY` | Ya | `php artisan key:generate` | Kunci enkripsi 32-byte (`base64:...`) |
| `APP_DEBUG` | Ya | Konfigurasi server | Wajib diset ke `false` di server produksi |
| `APP_URL` | Ya | Domain resmi | URL HTTPS penuh aplikasi produksi |
| `DB_CONNECTION` | Ya | Konfigurasi database | `sqlite` atau `mysql` |
| `DB_DATABASE` | Ya | Konfigurasi database | Path berkas SQLite atau nama database MySQL/MariaDB |
| `SESSION_DRIVER` | Ya | Konfigurasi sesi | `database` atau `file` |
| `SEED_ADMIN_EMAIL` | Opsional | Administrator sistem | Email akun awal untuk `db:seed` |
| `SEED_ADMIN_PASSWORD` | Opsional | Administrator sistem | Kata sandi akun awal untuk `db:seed` |
| `GEMINI_API_KEY` | Opsional | Google AI Studio | Kunci API Gemini untuk mode Online RAG pada Chatbot |
| `GEMINI_MODEL` | Opsional | Konfigurasi AI | Model Gemini untuk Chatbot (default: `gemini-3.5-flash-lite`) |

---

## 3. Langkah Deployment Standar
1. **Kompilasi Aset Frontend:**
   ```bash
   npm install --ignore-scripts
   npm run build
   ```
2. **Instalasi Dependensi PHP Produksi:**
   ```bash
   composer install --no-dev --optimize-autoloader --no-interaction
   ```
3. **Konfigurasi Environment & Kunci Aplikasi:**
   ```bash
   cp .env.example .env
   php artisan key:generate --force
   ```
4. **Migrasi Skema & Ingesti Data:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   php artisan data:import
   php artisan masterdata:import
   php artisan simudik:import
   ```
5. **Optimasi Cache Produksi:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

6. **Pengaturan Izin Berkas Direktori & Aset Statis (POSIX DAC):**
   ```bash
   # Pastikan kepemilikan user dan group web server (misalnya siste563476kan:siste563476kan)
   chown -R siste563476kan:siste563476kan public/build storage bootstrap/cache
   # Direktori wajib memiliki permission 775 agar www-data dapat melakukan traversal
   find public/build storage bootstrap/cache -type d -exec chmod 775 {} +
   # Berkas aset wajib memiliki permission 664 agar dapat dibaca oleh Nginx
   find public/build -type f -exec chmod 664 {} +
   ```

---

## 4. Hak Akses & Izin Berkas Produksi (Mencegah HTTP 403 Forbidden)
Pada lingkungan Debian / CloudPanel di mana pekerja Nginx berjalan sebagai user `www-data` (anggota grup pemilik direktori situs), evaluasi izin Linux POSIX DAC memprioritaskan grup file sebelum bit *others*.
Jika izin direktori adalah `drwx---rwx` atau berkas `----------`, Nginx akan menolak akses dengan respons `403 Forbidden`.

Pedoman izin yang wajib dipenuhi:
- Seluruh subdirektori di bawah `public/build/` wajib bermode `775` (`drwxrwxr-x`).
- Seluruh berkas aset JavaScript dan CSS di bawah `public/build/assets/` wajib bermode `664` (`-rw-rw-r--`).
- Berkas database SQLite (`database/database.sqlite`) serta direktorinya wajib memiliki izin baca-tulis penuh bagi pekerja PHP-FPM (`775` untuk direktori, `664` untuk berkas).

---

## 5. Konfigurasi Header Nginx untuk Peta OpenStreetMap (`/peta`)
Server tile resmi **OpenStreetMap Contributors** (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`) mewajibkan browser mengirimkan HTTP `Referer` header. Pastikan konfigurasi Nginx menggunakan kebijakan berikut (bukan `same-origin` atau `no-referrer`):

```nginx
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

Selain itu, layout utama [`resources/views/layouts/app.blade.php`](../resources/views/layouts/app.blade.php#L6) telah menyertakan:
```html
<meta name="referrer" content="strict-origin-when-cross-origin">
```

