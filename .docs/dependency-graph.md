# Peta Ketergantungan Berkas (Dependency Graph) — Dasbor Pendidikan Banjarnegara v2

## 1. Diagram Ketergantungan Modul
```mermaid
flowchart LR
    subgraph Routes
        W["routes/web.php"]
    end

    subgraph Controllers
        DC["DashboardController"]
        RC["RekomendasiController"]
        DMC["DemografiController"]
        IC["InfrastrukturController"]
        GC["GuruController"]
        PC["PetaController"]
        AC["AnalysisController"]
        AAC["AdvancedAnalysisController"]
        AU["AuthController"]
    end

    subgraph Support_Services["Support & Services"]
        FLT["App\\Support\\Filters"]
        GEO["App\\Support\\KecamatanGeo"]
        AI["App\\Services\\AnalitikIndikator"]
        DP["App\\Services\\DindikporaParser"]
        KP["App\\Services\\KemenagParser"]
        DSP["App\\Services\\DisarpusParser"]
        WP["App\\Services\\WalidataPendidikan"]
    end

    subgraph Models
        MS["School"]
        MSS["StudentStat"]
        MTS["TeacherStat"]
        MT["Teacher"]
        MP["Population"]
    end

    W --> DC & DMC & IC & GC & PC & AC & AU
    DC & DMC & IC & GC & PC --> FLT
    DC & IC & PC --> MS
    DC & DMC & IC & PC --> MSS
    DC & DMC & GC & PC --> MTS
    GC --> MT
    AC --> AI & DP & KP & DSP & WP
    AI --> MP & MS & MSS & MTS
```

---

## 2. Klasifikasi Dampak Berkas

### 🔴 Critical Files (Perubahan berdampak ke seluruh aplikasi)
| Berkas | Konsumen Langsung | Risiko Perubahan |
|---|---|---|
| `resources/views/layouts/app.blade.php` | Seluruh 13 halaman dasbor & analisis | Perubahan struktur navigasi, header `Referrer-Policy`, atau CDN Chart.js/Leaflet berdampak ke seluruh halaman |
| `app/Support/Filters.php` | `DashboardController`, `DemografiController`, `InfrastrukturController`, `GuruController`, `PetaController`, `partials/filters.blade.php` | Mengatur daftar semester, normalisasi parameter filter, dan daftar kecamatan/jenjang |
| `routes/web.php` | Seluruh tautan sidebar (`route('...')`) di `layouts/app.blade.php` | Mengubah nama rute akan memutus navigasi sidebar |
| `app/Services/AnalitikIndikator.php` | `AnalysisController` (6 dari 8 halaman analisis) | Menyediakan perhitungan indikator makro, tren, dan skor rekomendasi kebijakan |

### 🟡 High-Impact Files (Perubahan berdampak pada satu modul penuh)
| Berkas | Konsumen Langsung | Fungsi Utama |
|---|---|---|
| `app/Http/Controllers/AnalysisController.php` | 8 view di `resources/views/dashboard/*` | Mengorkestrasi sub-menu analisis kebijakan `/analisis/*` |
| `app/Http/Controllers/AdvancedAnalysisController.php` | `RekomendasiController`, `data-health.blade.php`, `sarana.blade.php`, `regrouping.blade.php` | Menyediakan perhitungan bersama kualitas data, sarana, kandidat regrouping, serta konsumsi `RekomendasiController` |
| `app/Services/EducationRagService.php` | `ChatbotController` (`POST /chatbot/ask`), `partials/chatbot.blade.php` | Mesin Hybrid RAG (Online Gemini API + Offline Basis Data Lokal) untuk lingkup data pendidikan & penjelasan kekurangan data guru MI/MTs |
| `app/Support/KecamatanGeo.php` | `FixSchoolCoordinates.php`, `DataImport.php`, `ImportSimudik.php` | Validasi geometri poligon GeoJSON 20 kecamatan |
| `app/Console/Commands/DataImport.php` | Tabel `schools`, `student_stats`, `teacher_stats`, `teachers`, `populations` | Pipeline ETL data Dapodik & kependudukan |

### 🟢 Leaf Files (Perubahan terisolasi pada satu tampilan)
- `resources/views/dashboard/peta.blade.php`
- `resources/views/dashboard/rekomendasi.blade.php`
- `resources/views/dashboard/demografi.blade.php`
- `resources/views/dashboard/infrastruktur.blade.php`
- `resources/views/dashboard/guru.blade.php`
- `resources/views/dashboard/gap.blade.php`, `dropout.blade.php`, `literacy.blade.php`, `madrasah.blade.php`, `indikator.blade.php`, `paud.blade.php`, `walidata.blade.php`, `kebijakan.blade.php`, `data-health.blade.php`, `sarana.blade.php`, `regrouping.blade.php`

