# Security Audit — Dasbor Pendidikan Kabupaten Banjarnegara v2
<!-- [ANTI-STALE] Factual Codebase & Production Scan: 2026-11-07T16:13:00+07:00 -->

| Parameter | Nilai |
|---|---|
| Tanggal | 2026-11-07 16:13 WIB |
| Git Anchor | `de8f484` |
| Files Inspected | 42 files across `app/`, `routes/`, `resources/views/`, `bootstrap/`, `.docs/` |
| Total grep_search Executed | 18 factual queries |
| Total run_command Executed | 14 verification commands (including live production server HTTP/DAC audit) |
| Scan Mode | Hybrid (Static Code Analysis + Production Environment Verification) |
| Auditor | Antigravity AI Security Auditor |

---

## Ringkasan Temuan

| Severity | Jumlah | Status |
|---|---|---|
| 🔴 CRITICAL | 0 | ✅ LULUS |
| 🟠 HIGH | 0 | ✅ LULUS |
| 🟡 MEDIUM | 1 | ⏳ CSRF Exception Terisolasi (Mitigasi Auth + SameSite=Lax Aktif) |
| 🔵 MINOR / INFO | 1 | ℹ️ SARAN PENINGKATAN (HSTS Header) |

---

## 1. Verifikasi Komponen Keamanan (SP Compliance Matrix)

| Kode SP | Komponen Keamanan | Status | Bukti Faktual (`[Evidence:]`) |
|---|---|---|---|
| **SP-001** | Parameterized Queries (SQL Injection Guard) | ✅ TERPASANG | `[Evidence: grep "DB::raw" -> app/Http/Controllers/*.php -> seluruh klausa selectRaw hanya menggunakan fungsi agregasi COUNT/SUM/COALESCE tanpa interpolasi variabel user; parameter filter dipetakan via Eloquent bound where]` |
| **SP-002** | Output Escaping (XSS Prevention di Blade) | ✅ TERPASANG | `[Evidence: grep "{!!" -> resources/views/ -> 0 hits; seluruh view Blade menggunakan sintaks aman {{ $var }} dengan htmlspecialchars otomatis]` |
| **SP-003 / SP-030** | File Upload Security & Malware Isolation | ✅ TERPASANG | `[Evidence: grep "hasFile|UploadedFile" -> app/*.php -> 0 hits; aplikasi bersifat read-only analytics, seluruh ingesti data dilakukan melalui CLI Artisan]` |
| **SP-004** | Password Hashing & Auth Integrity | ✅ TERPASANG | `[Evidence: app/Http/Controllers/AuthController.php:24 -> Auth::attempt + session()->regenerate(); database/seeders/DatabaseSeeder.php:24 -> Hash::make(cost 12)]` |
| **SP-005** | Mass Assignment Protection | ✅ TERPASANG | `[Evidence: grep "fillable" -> app/Models/*.php -> seluruh 5 model (School, StudentStat, TeacherStat, Teacher, Population) mendeklarasikan $fillable]` |
| **SP-006 / SP-024** | Route Authentication & RBAC Isolation | ✅ TERPASANG | `[Evidence: routes/web.php:24 -> seluruh 20 modul analitik dilindungi middleware('auth')]` |
| **SP-007 / SP-009** | Environment Secret Isolation | ✅ TERPASANG | `[Evidence: git check-ignore .env database/database.sqlite -> ignored; curl https://pendidikan.sistemdata.id/.env -> HTTP 404 Not Found]` |
| **SP-008** | CORS Enforcement | ✅ TERPASANG | `[Evidence: default same-origin policy aktif, tidak ada wildcard Access-Control-Allow-Origin: *]` |
| **SP-010** | CSRF Protection | 🟡 MEDIUM | `[Evidence: bootstrap/app.php:15 -> validateCsrfTokens(except: ['chatbot/ask']); endpoint dilindungi middleware('auth') dan SameSite=Lax cookie]` |
| **SP-013 / SP-023** | Security Headers & Origin Isolation | 🟡 MINOR | `[Evidence: curl -I https://pendidikan.sistemdata.id/login -> X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff, Referrer-Policy: strict-origin-when-cross-origin; HSTS & CSP belum dideklarasikan di Nginx]` |
| **SP-015** | Tiered Rate Limiting (SP-015 Implemented) | ✅ TERPASANG | `[Evidence: AppServiceProvider.php -> RateLimiter::for('login') [5/min IP+email], RateLimiter::for('chatbot-ask') [30/min user/IP], RateLimiter::for('chatbot-ping') [60/min IP]; php artisan route:list -v -> throttle terdaftar aktif]` |
| **SP-016** | Supply Chain Dependency Audit | ✅ TERPASANG | `[Evidence: composer audit --locked -> 0 advisories; npm audit -> found 0 vulnerabilities]` |
| **SP-017** | Software & Data Integrity Verification | ✅ TERPASANG | `[Evidence: composer.lock & package-lock.json tersinkronisasi penuh dengan versi framework Laravel v13.40.0]` |
| **SP-018 / SP-019** | Error Handling & Stack Trace Cloaking | ✅ TERPASANG | `[Evidence: ssh nargaroth "grep APP_DEBUG .env" -> APP_ENV=production, APP_DEBUG=false; stack trace tidak bocor ke publik]` |
| **SP-020** | SSRF Prevention | ✅ TERPASANG | `[Evidence: app/Services/EducationRagService.php:273 -> outbound HTTP client di-hardcode ke domain generativelanguage.googleapis.com dengan batas timeout 5-7 detik]` |
| **SP-021 / SP-022** | IDOR & Open Redirect Prevention | ✅ TERPASANG | `[Evidence: AuthController.php -> redirect()->intended(route('dashboard')) menggunakan nama rute internal terdaftar, tanpa redirect parameter terbuka]` |
| **SP-026** | Universal Input Validation | ✅ TERPASANG | `[Evidence: ChatbotController.php:14 -> $request->validate(['question' => 'required|string|max:1000', 'history' => 'nullable|array|max:10'])]` |
| **SP-028** | API Key Lifecycle Isolation | ✅ TERPASANG | `[Evidence: EducationRagService.php -> memuat API key via config/env tanpa hardcode; 0 API key bocor di repositori Git]` |
| **SP-029** | Database Isolation & Network Boundary | ✅ TERPASANG | `[Evidence: ssh nargaroth "ls -la database/database.sqlite" -> -rw-rw----, direktori 775 di luar webroot public/; curl https://pendidikan.sistemdata.id/database/database.sqlite -> HTTP 404 Not Found]` |
| **SP-031** | Server Fingerprint Cloaking | ✅ TERPASANG | `[Evidence: curl -I https://pendidikan.sistemdata.id/login -> Server: nginx (tanpa nomor versi), X-Powered-By disembunyikan]` |
| **SP-032 / SP-033** | Public Docs Secret Sanitization | ✅ TERPASANG | `[Evidence: scan README.md & .docs/ -> 0 kredensial, 0 API key, 0 password seeder]` |

---

## 2. Rincian Temuan & Remediasi

### ✅ [RESOLVED] [MED-01] Pembatasan Laju Percobaan Masuk (Tiered Rate Limiting SP-015 pada `POST /login`)
- **Komponen:** [`routes/web.php:17`](../routes/web.php#L17) & [`app/Providers/AppServiceProvider.php`](../app/Providers/AppServiceProvider.php)
- **Status:** `✅ FIXED (2026-11-07)`
- **Implementasi:**
  1. Didefinisikan Tier 1 Auth Limiter `RateLimiter::for('login')` dengan batas **5 percobaan per menit** per kombinasi IP + Email ternormalisasi.
  2. Apabila batas tercapai, sistem menyajikan tampilan ramah pengguna dengan pesan peringatan penundaan waktu tunggu dan status `HTTP 429 Too Many Requests`.
  3. Didefinisikan Tier 2 AI Engine Limiter `RateLimiter::for('chatbot-ask')` dengan kuota **30 request per menit** per ID pengguna/IP.
  4. Didefinisikan Tier 3 Diagnostic Limiter `RateLimiter::for('chatbot-ping')` dengan kuota **60 request per menit** per IP.
  5. Seluruh rute telah dikompilasi ke dalam route cache produksi (`php artisan route:cache`).

---

### 🟡 [MED-02] Penanganan Pengecualian CSRF pada `/chatbot/ask`
- **Komponen:** [`bootstrap/app.php:15`](../bootstrap/app.php#L15)
- **Kategori OWASP:** A01:2025 – Broken Access Control / Cross-Site Request Forgery
- **Analisis:** Pengecualian token CSRF pada `chatbot/ask` diizinkan untuk menghindari error HTTP 419 saat sesi lama tidak aktif. Meskipun rute ini tetap dilindungi middleware `auth` dan cookie sesi Laravel menggunakan kebijakan `SameSite=Lax`, verifikasi origin atau token CSRF dinamis tetap merupakan *defense-in-depth* terbaik.
- **Status Mitigasi Eksisting:**
  1. Dilindungi middleware `auth` (hanya sesi terautentikasi yang dapat memanggil).
  2. Dilindungi `throttle:30,1` (maksimal 30 panggilan per menit).
  3. `SameSite=Lax` mencegah pengiriman cookie pada request POST lintas domain standar.

---

### 🔵 [INFO-01] Penambahan Header HSTS & Content-Security-Policy di Nginx
- **Komponen:** Konfigurasi Nginx Server Block (`/etc/nginx/sites-available/...`)
- **Kategori OWASP:** A02:2025 – Security Misconfiguration
- **Analisis:** Server produksi telah menyertakan `X-Frame-Options`, `X-Content-Type-Options`, dan `Referrer-Policy`. Untuk menyempurnakan skor audit SSL Labs, dapat ditambahkan header:
  ```nginx
  add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
  ```

