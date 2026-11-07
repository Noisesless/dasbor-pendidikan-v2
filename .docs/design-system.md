# Design System & Visual DNA — Dasbor Pendidikan Kabupaten Banjarnegara v2

## 1. Identitas Visual (Master Anchor)
- **Archetype:** *Civic Slate & Royal Blue Executive Analytics*
- **Three Dials:** `DESIGN_VARIANCE = 6` | `MOTION_INTENSITY = 3` | `VISUAL_DENSITY = 7`
- **Mode Tema:** Static Crisp Light (`bg-slate-50/80` dengan kartu `bg-white`)

---

## 2. Token Warna & Semantik (`resources/css/app.css`)
| Token / Utility Kelas | Nilai Hex / OKLCH | Peruntukan |
|---|---|---|
| `--vibe-background` (`bg-slate-50`) | `#f8fafc` | Latar kanvas utama aplikasi |
| `--vibe-surface` (`bg-white`) | `#ffffff` | Permukaan kartu KPI, grafik, tabel, dan sidebar |
| `--vibe-border` (`border-slate-200/80`) | `#e2e8f0` | Garis pemisah struktural antarpane & kartu |
| `--vibe-text-main` (`text-slate-800`) | `#1e293b` | Judul halaman, nilai angka KPI, dan teks utama |
| `--vibe-text-sub` (`text-slate-500`) | `#64748b` | Label metadata, keterangan satuan, dan navigasi pasif |
| `--vibe-accent-1` (`bg-blue-600`) | `#2563eb` | Aksen primer, menu aktif, tombol filter utama |
| `--vibe-accent-2` (`bg-indigo-600`) | `#4f46e5` | Aksen sekunder & highlight indikator |
| `--vibe-success` (`text-emerald-600`) | `#10b981` | Status capaian positif / akreditasi A |
| `--vibe-warning` (`text-amber-500`) | `#f59e0b` | Peringatan gap sedang / perhatian kebijakan |
| `--vibe-error` (`text-red-500`) | `#ef4444` | Risiko tinggi (angka putus sekolah / kekurangan guru) |

---

## 3. Palet Kategori Jenjang Pendidikan (Konsisten Lintas Grafik & Peta)
| Jenjang | Kode Hex | Peruntukan Visual |
|---|---|---|
| `PAUD` | `#f59e0b` | Marker Leaflet & seri grafik PAUD |
| `TK` | `#f97316` | Marker Leaflet & seri grafik TK |
| `SD` | `#2563eb` | Marker Leaflet & seri grafik SD |
| `SMP` | `#8b5cf6` | Marker Leaflet & seri grafik SMP |
| `SMA` | `#10b981` | Marker Leaflet & seri grafik SMA |
| `SMK` | `#ec4899` | Marker Leaflet & seri grafik SMK |
| `MI` | `#3b82f6` | Marker Leaflet & seri grafik MI |
| `MTs` | `#a855f7` | Marker Leaflet & seri grafik MTs |
| `MA` | `#14b8a6` | Marker Leaflet & seri grafik MA |
| `PKBM` | `#64748b` | Marker Leaflet & seri grafik PKBM |

---

## 4. Tipografi
- **Font Utama (`--font-sans`):** `'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif`
  - Judul Halaman: `text-xl sm:text-2xl font-bold tracking-tight text-slate-900`
  - Nilai KPI Utama: `text-2xl sm:text-3xl font-extrabold text-slate-900`
  - Label Kartu: `text-xs font-semibold uppercase tracking-wider text-slate-500`
- **Font Monospace (`.font-mono`):** `'JetBrains Mono', monospace`
  - Digunakan untuk kode NPSN, koordinat geografis, dan persentase presisi.

---

## 5. Komponen Terdaftar (§7B Component Registry)
1. **App Shell Sidebar (`layouts/app.blade.php`):** Lebar tetap `w-72` di desktop (`lg:translate-x-0`), drawer tersembunyi di mobile dengan backdrop `bg-slate-900/40 backdrop-blur-xs`.
2. **Global Filter Bar (`dashboard/partials/filters.blade.php`):** Kombinasi pemilih `Semester`, `Kecamatan`, dan `Jenjang` dengan tombol reset cepat.
3. **KPI Summary Cards:** Kartu `bg-white rounded-2xl border border-slate-200/80 p-5` dengan ikon kontekstual SVG.
4. **Interactive Map Canvas (`dashboard/peta.blade.php`):** Kontainer `#sekolahMap` dengan legenda choropleth 5 tingkat kepadatan (`#f1f5f9` s/d `#1e40af`) dan filter marker jenjang interaktif.
5. **Data Tables:** Tabel responsif `overflow-x-auto` dengan header `bg-slate-50 text-xs font-semibold uppercase text-slate-500`.

