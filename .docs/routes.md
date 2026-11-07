# Peta Rute (Routes Matrix) — Dasbor Pendidikan Kabupaten Banjarnegara v2

Seluruh rute didefinisikan di [`routes/web.php`](../routes/web.php).

| No | Method | Path URI | Nama Rute | Middleware | Controller & Method | Berkas View Blade |
|---|---|---|---|---|---|---|
| 1 | `GET` | `/login` | `login` | `guest` | `AuthController@showLogin` | `resources/views/auth/login.blade.php` |
| 2 | `POST` | `/login` | — | `guest` | `AuthController@login` | *(Redirect ke `/`)* |
| 3 | `POST` | `/logout` | `logout` | `auth` | `AuthController@logout` | *(Redirect ke `/login`)* |
| 4 | `GET` | `/` | `dashboard` | `auth` | `DashboardController@index` | `resources/views/dashboard/index.blade.php` |
| 5 | `GET` | `/rekomendasi` | `rekomendasi` | `auth` | `RekomendasiController@index` | `resources/views/dashboard/rekomendasi.blade.php` |
| 6 | `GET` | `/demografi` | `demografi` | `auth` | `DemografiController@index` | `resources/views/dashboard/demografi.blade.php` |
| 7 | `GET` | `/infrastruktur` | `infrastruktur` | `auth` | `InfrastrukturController@index` | `resources/views/dashboard/infrastruktur.blade.php` |
| 8 | `GET` | `/guru` | `guru` | `auth` | `GuruController@index` | `resources/views/dashboard/guru.blade.php` |
| 9 | `GET` | `/peta` | `peta` | `auth` | `PetaController@index` | `resources/views/dashboard/peta.blade.php` |
| 10 | `GET` | `/analisis/gap` | `gap` | `auth` | `AnalysisController@gap` | `resources/views/dashboard/gap.blade.php` |
| 11 | `GET` | `/analisis/angka-keluar` | `keluar` | `auth` | `AnalysisController@dropout` | `resources/views/dashboard/dropout.blade.php` |
| 12 | `GET` | `/analisis/literasi` | `literasi` | `auth` | `AnalysisController@literacy` | `resources/views/dashboard/literacy.blade.php` |
| 13 | `GET` | `/analisis/madrasah` | `madrasah` | `auth` | `AnalysisController@madrasah` | `resources/views/dashboard/madrasah.blade.php` |
| 14 | `GET` | `/analisis/indikator` | `indikator` | `auth` | `AnalysisController@indicator` | `resources/views/dashboard/indikator.blade.php` |
| 15 | `GET` | `/analisis/akreditasi-paud` | `paud` | `auth` | `AnalysisController@paud` | `resources/views/dashboard/paud.blade.php` |
| 16 | `GET` | `/analisis/walidata` | `walidata` | `auth` | `AnalysisController@walidata` | `resources/views/dashboard/walidata.blade.php` |
| 17 | `GET` | `/analisis/kebijakan` | `kebijakan` | `auth` | `AnalysisController@kebijakan` | `resources/views/dashboard/kebijakan.blade.php` |
| 18 | `GET` | `/analisis/data-health` | `data-health` | `auth` | `AdvancedAnalysisController@dataHealth` | `resources/views/dashboard/data-health.blade.php` |
| 19 | `GET` | `/analisis/sarana` | `sarana` | `auth` | `AdvancedAnalysisController@sarana` | `resources/views/dashboard/sarana.blade.php` |
| 20 | `GET` | `/analisis/regrouping` | `regrouping` | `auth` | `AdvancedAnalysisController@regrouping` | `resources/views/dashboard/regrouping.blade.php` |
| 21 | `POST` | `/chatbot/ask` | `chatbot.ask` | `auth`, `throttle:30,1` | `ChatbotController@ask` | *(JSON Response - Hybrid Online/Offline RAG)* |

