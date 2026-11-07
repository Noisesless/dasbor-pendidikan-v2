# Issue Tracker — Dasbor Pendidikan Kabupaten Banjarnegara v2
<!-- FIFO Rolling Buffer: Max 10 RESOLVED issues -->

## Status Aktif (`OPEN` / `IN_PROGRESS`)
*Tidak ada isu aktif (`0 OPEN`, `0 IN_PROGRESS`).*

---

## Riwayat Isu Terselesaikan (`RESOLVED`)

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

