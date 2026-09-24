# Sistem Manajemen Magang PT Logistax Mitratama Solusi

Backend Laravel (API + Blade) untuk sistem manajemen magang. Project ini **berdiri
sendiri sepenuhnya** — database, auth, dan codebase terpisah total dari sistem HR
(logistaxcore.com) maupun Logistax Core lainnya.

## Tech Stack

- Laravel 13 (PHP 8.3+)
- MySQL
- Laravel Sanctum (token-based auth untuk mobile app, 2 guard: `AdminUser` & `InternAccount`)
- Dashboard admin: Blade + session auth (guard `web`), Tailwind CSS v4 (via Vite), Alpine.js (CDN), Chart.js (CDN)

## Setup

### 1. Clone & install dependency

```bash
composer install
```

### 2. Konfigurasi environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` sesuai kredensial MySQL lokal kamu:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=logistax_magang
DB_USERNAME=root
DB_PASSWORD=
```

Buat database-nya terlebih dahulu:

```sql
CREATE DATABASE logistax_magang CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Migration

```bash
php artisan migrate
```

Semua tabel dibuat dengan UUID sebagai primary key (trait `HasUuids`).

### 4. Storage link (wajib untuk upload bukti izin/sakit)

```bash
php artisan storage:link
```

Perintah ini membuat symlink `public/storage` -> `storage/app/public`, supaya
file yang diupload lewat `POST /api/attendance/leave-request` (disk `public`,
folder `attendance-proofs`) bisa diakses publik lewat URL
`http://127.0.0.1:8000/storage/attendance-proofs/...` (nilai `proof_file_url`).
Tanpa langkah ini, file tetap tersimpan tapi tidak bisa diakses lewat browser.

### 5. Setup Browsershot / PDF Sertifikat (wajib untuk fitur certificate)

Generate PDF sertifikat pakai [spatie/browsershot](https://github.com/spatie/browsershot)
(headless Chrome via Puppeteer). Langkah setup:

1. **Install Puppeteer** (mendownload Chromium sendiri) di root project:
   ```bash
   npm install puppeteer
   ```
   Node.js (v18+) dan npm harus sudah terpasang duluan.

2. **Cari path Chrome/Chromium hasil download Puppeteer.** Biasanya di
   `~/.cache/puppeteer/chrome/<versi>/chrome-win64/chrome.exe` (Windows) atau
   `~/.cache/puppeteer/chrome/<versi>/chrome-linux64/chrome` (Linux/Mac). Bisa
   dicek lewat:
   ```bash
   node -e "console.log(require('puppeteer').executablePath())"
   ```
   (kalau printnya `Promise { <pending> }` di beberapa versi Puppeteer, cari
   langsung ke folder cache di atas).

3. **Isi `.env`** dengan path node/npm/chrome yang ditemukan (perlu diisi
   eksplisit di Windows/Laragon karena Browsershot tidak selalu bisa
   menemukan binary node/npm/chrome otomatis dari proses PHP-FPM/artisan
   serve; di Linux dengan node ada di PATH sistem, ketiganya boleh
   dikosongkan):
   ```
   BROWSERSHOT_NODE_BINARY=/path/ke/node
   BROWSERSHOT_NPM_BINARY=/path/ke/npm
   BROWSERSHOT_CHROME_PATH=/path/ke/chrome.exe
   ```
   Nilai ini dibaca lewat `config/browsershot.php` dan dipakai di
   `App\Services\CertificatePdfService`.

4. **Test render** cepat lewat tinker untuk memastikan semuanya nyambung:
   ```bash
   php artisan tinker --execute="
   echo strlen((new App\Services\CertificatePdfService)->renderPdf('<h1>Test</h1>')).' bytes rendered';
   "
   ```

Font sertifikat (Poppins + Tangerine) diambil dari Google Fonts lewat
`<link>` di `resources/views/certificates/template.blade.php` — mesin yang
menjalankan `php artisan serve`/generate certificate perlu koneksi internet
saat render. Kalau environment produksi tidak punya akses internet keluar,
opsi lain adalah host font itu secara lokal (`@font-face` + file `.woff2` di
`public/fonts`) dan ganti `<link>` Google Fonts di template — belum dilakukan
di fase ini karena environment development di sini punya akses internet.

### 6. Build asset frontend (dashboard Blade)

Dashboard admin (`/dashboard`, `/interns`, dll) pakai Tailwind CSS v4 + Alpine.js,
di-compile lewat Vite. Wajib dijalankan sebelum dashboard bisa diakses dengan benar:

```bash
npm install
npm run build      # sekali jalan, untuk production/testing manual
# atau, selama development:
npm run dev         # watch mode, auto-rebuild saat file CSS/JS berubah
```

### 7. Jalankan server

```bash
php artisan serve
```

API tersedia di `http://127.0.0.1:8000/api`. Dashboard admin (Blade) tersedia di
`http://127.0.0.1:8000/login`.

### 8. Buat admin pertama & akses dashboard

Belum ada seeder/endpoint pendaftaran admin (sesuai scope — akun admin dibuat
manual oleh developer/DBA), buat lewat tinker:

```bash
php artisan tinker
>>> App\Models\AdminUser::create([
      'name' => 'Admin Utama',
      'email' => 'admin@logistax.test',
      'password' => Hash::make('password123'),
      'role' => 'admin_magang', // atau 'spv_mentor'
      'is_active' => true,
    ]);
```

Lalu buka `http://127.0.0.1:8000/login` dan masuk dengan email/password di atas.
Login dashboard ini **session-based** (guard `web`), terpisah total dari token
Sanctum yang dipakai mobile app / testing `requests.http` — lihat
"Auth Web (Dashboard) vs Auth API (Mobile)" di bawah.

### 9. Testing manual API

Gunakan file [`requests.http`](requests.http) (REST Client extension di VS Code,
atau import ke Postman/Insomnia) untuk mencoba semua endpoint API (mobile-facing).

## Struktur Auth

Ada 2 jenis user yang login terpisah, keduanya lewat Sanctum (`personal_access_tokens`
bersifat polymorphic sehingga satu tabel bisa menyimpan token untuk kedua model):

- **AdminUser** (`role`: `admin_magang` / `spv_mentor`) — akses dashboard Blade & API admin.
- **InternAccount** — akses via mobile app (API only).

Middleware custom membedakan tipe user yang login:

- `admin.role:<role1>,<role2>` (`App\Http\Middleware\EnsureAdminRole`) — memastikan
  `$request->user()` adalah instance `AdminUser`, aktif, dan rolenya sesuai parameter.
- `intern.auth` (`App\Http\Middleware\EnsureInternAuth`) — memastikan `$request->user()`
  adalah instance `InternAccount`.

## Ringkasan Endpoint (fase ini)

### Auth
- `POST /api/auth/admin/login`
- `POST /api/auth/intern/register`
- `POST /api/auth/intern/login`
- `POST /api/auth/logout` (butuh token, admin maupun intern)

### Interns (butuh `auth:sanctum` + `admin.role`)
- `GET /api/interns` — admin_magang lihat semua, spv_mentor hanya yang jadi mentornya
- `POST /api/interns` — admin_magang only, langsung status `active`
- `GET /api/interns/{intern}`
- `PATCH /api/interns/{intern}` — admin_magang only, update data dasar
- `POST /api/interns/{intern}/approve` — admin_magang only, hanya dari status `pending`
- `POST /api/interns/{intern}/reject` — admin_magang only, hanya dari status `pending`
- `POST /api/interns/{intern}/extend` — admin_magang only
- `POST /api/interns/{intern}/mark-failed` — admin_magang only, hanya dari status `active`/`extended`
- `POST /api/interns/{intern}/mark-completed` — admin_magang only, hanya dari status `active`/`extended`

### Divisions & Office Locations (admin_magang only)
- CRUD standar (`GET/POST/PATCH/DELETE`), delete = soft (`is_active = false`).
  Division tidak bisa dihapus kalau masih dipakai intern manapun.

### Attendance — Intern (butuh `auth:sanctum` + `intern.auth`)
- `POST /api/attendance/check-in` — body `latitude`, `longitude`. Ditolak kalau
  status intern bukan `active`/`extended`, kalau di luar radius semua
  `office_locations` aktif, atau kalau sudah check-in hari ini.
- `POST /api/attendance/check-out` — sama seperti check-in, tapi butuh sudah
  check-in dan belum check-out hari itu.
- `POST /api/attendance/leave-request` — body `date`, `status` (`izin`/`sakit`),
  `notes`, `proof_file` (upload). Lihat bagian "Keputusan Desain" di bawah untuk
  aturan wajib/opsional `proof_file`. Tidak bisa dipakai untuk tanggal di luar
  periode magang (`start_date`–`end_date`) atau tanggal yang sudah berstatus
  `hadir`/sudah ada pengajuan `pending`/`approved`.
- `GET /api/attendance/my-history?month=&year=` — riwayat absensi intern yang
  login saja, default bulan & tahun berjalan.

### Attendance — Admin (butuh `auth:sanctum` + `admin.role:admin_magang,spv_mentor`)
- `GET /api/attendance/monthly-report?month=&year=&division_id=&intern_id=` —
  rekap agregat (total hadir/izin/sakit/absen) per intern, bukan daftar record
  mentah. `spv_mentor` otomatis di-scope hanya ke intern bimbingannya.
- `GET /api/attendance/pending-approvals` — daftar pengajuan izin/sakit yang
  masih `pending`, termasuk `proof_file_url`. Scoped sama seperti di atas.
- `POST /api/attendance/{attendance}/approve` — hanya dari `approval_status =
  pending`; `spv_mentor` hanya boleh untuk intern bimbingannya (403 kalau bukan).
- `POST /api/attendance/{attendance}/reject` — body `reason` wajib, digabung ke
  kolom `notes` (tidak menimpa catatan asli dari intern).

### Evaluations — Admin & Mentor (butuh `auth:sanctum` + `admin.role:admin_magang,spv_mentor`)
- `GET /api/evaluations/pending?within_days=` — daftar intern yang belum
  punya evaluation, status `active`/`extended`, dan `end_date` sudah lewat atau
  jatuh dalam `within_days` hari ke depan (default 7). `spv_mentor` di-scope ke
  bimbingannya saja, `admin_magang` melihat semua.
- `POST /api/evaluations` — body `intern_id` + 4 skor komponen + `comments`
  (optional). Hanya mentor intern tsb (`mentor_id` cocok) atau `admin_magang`.
  Ditolak (400) kalau evaluation untuk intern itu sudah ada — gunakan PATCH.
  `total_score`/`grade` selalu dihitung server-side via
  `EvaluationScoringService`, nilai apa pun yang dikirim di body untuk field
  itu diabaikan.
- `PATCH /api/evaluations/{evaluation}` — partial update (kirim sebagian atau
  semua dari 4 skor + comments). `total_score`/`grade` selalu dihitung ulang.
  Lihat "Keputusan Desain: Logic Edit Evaluasi" di bawah untuk aturan siapa
  boleh edit kapan.
- `GET /api/evaluations/intern/{internId}` — detail evaluasi 1 intern.
  `admin_magang` bebas akses; `spv_mentor` hanya kalau dia mentornya (403
  kalau bukan). Kalau evaluation belum ada, return `data: null` dengan
  `message: "Evaluasi belum tersedia."` (bukan 404).

### Evaluation — Intern (butuh `auth:sanctum` + `intern.auth`)
- `GET /api/my-evaluation` — intern lihat evaluasinya sendiri. Sama seperti di
  atas, kalau belum dinilai return `data: null` + message, bukan error 404.

### Certificates — Admin only: generate/regenerate/preview (butuh `auth:sanctum` + `admin.role:admin_magang`)
- `POST /api/certificates/generate/{internId}` — syarat: `intern.status =
  completed` dan evaluation intern tsb ada & lengkap (4 skor terisi). Ditolak
  kalau certificate sudah ada dan `needs_certificate_regeneration = false`
  ("...gunakan endpoint regenerate"). Kalau certificate sudah ada DAN flag
  `true`, endpoint ini bertindak sebagai regenerate juga (update in-place,
  flag direset ke `false`). Nomor sertifikat dari `CertificateNumberService`,
  PDF di-render via Browsershot lalu dipassword pakai NIM intern (FPDI+TCPDF),
  disimpan ke `storage/app/public/certificates/`.
- `POST /api/certificates/regenerate/{internId}` — hanya valid kalau
  certificate sudah ada. Lihat "Keputusan Desain" di bawah soal kapan endpoint
  ini boleh dipakai.
- `GET /api/certificates/preview/{internId}` — render PDF sementara dengan
  nomor dummy `PREVIEW/LOGISTAX/INTERN/{romawi}/{tahun}`, **tanpa password**,
  **tidak menyimpan apa pun** ke database atau counter tahunan. Response
  langsung berupa file PDF (`Content-Type: application/pdf`), bukan JSON.

### Certificates — Admin & Mentor: metadata + download (butuh `auth:sanctum` + `admin.role:admin_magang,spv_mentor`)
- `GET /api/certificates/{internId}` — metadata sertifikat (nomor, tanggal
  terbit, kota, `pdf_url`, `download_count`) — BUKAN file PDF-nya. `spv_mentor`
  di-scope ke bimbingannya (403 kalau bukan). Kalau certificate belum ada,
  return `data: null` + `message: "Sertifikat belum tersedia."` (bukan 404).
- `GET /api/certificates/{internId}/download` — return file PDF asli
  (terpassword NIM), increment `download_count` setiap diakses. Ditolak kalau
  `intern.status = failed`, dengan pesan
  "Sertifikat tidak dapat diproses untuk intern dengan status gagal."

### Certificate — Intern (butuh `auth:sanctum` + `intern.auth`)
- `GET /api/my-certificate` — metadata sertifikat sendiri, `data: null` +
  message kalau belum ada (bukan 404). Ini route terpisah dari
  `/certificates/{internId}` (bukan endpoint yang sama dideteksi dari guard) —
  mengikuti pola yang sudah dipakai di `/my-evaluation` pada fase evaluation,
  supaya konsisten satu project.
- `GET /api/my-certificate/download` — download sertifikat sendiri, aturan
  sama seperti download admin/mentor di atas.

Semua response API mengikuti format:

```json
{ "success": true, "data": {}, "message": null }
```

## Keputusan Desain: Status "Rejected"

Task meminta keputusan eksplisit soal bagaimana merepresentasikan intern yang
ditolak, karena enum `status` semula tidak punya nilai untuk itu. Keputusan yang
diambil: **menambahkan `'rejected'` ke enum `status`** pada tabel `interns`
(lihat `database/migrations/..._create_interns_table.php` dan komentar di
`InternController::reject()`), bukan memakai flag `is_rejected` terpisah.

Alasan:
- Status intern jadi eksplisit dan konsisten — satu kolom `status` selalu
  mencerminkan kondisi intern saat ini, tidak ada state ambigu (mis. status
  tetap `pending` padahal sudah ditolak).
- Listing dengan filter `status=pending` otomatis tidak menampilkan intern yang
  sudah ditolak, tanpa perlu kondisi tambahan `is_rejected = false` di setiap
  query.
- Jejak penolakan tetap tersimpan lewat kolom `rejection_reason` yang sudah ada.

## Keputusan Desain: `proof_file` pada Leave Request

`POST /api/attendance/leave-request` mewajibkan `proof_file` **hanya untuk
status `sakit`**, dan menjadikannya **opsional untuk `izin`** (lihat
`App\Http\Requests\Attendance\LeaveRequestRequest::rules()`, rule
`required_if:status,sakit`).

Alasan:
- Sakit lazimnya punya bukti medis (surat dokter/keterangan klinik) yang wajar
  diminta sebagai syarat approval, sementara izin (keperluan pribadi) cukup
  dengan alasan tertulis di kolom `notes` tanpa perlu dokumen formal.
- Mewajibkan bukti untuk keduanya dianggap terlalu kaku untuk kasus izin
  singkat/mendadak; membuat keduanya opsional dianggap terlalu longgar untuk
  klaim sakit yang butuh verifikasi admin/mentor sebelum di-approve.

## Scope Fase Ini (Fase 2 — Attendance)

Ditambahkan di fase ini: model `Attendance` (relasi ke `Intern` & `AdminUser`),
`AttendanceLocationService` (Haversine + validasi radius), dan seluruh endpoint
`/api/attendance/*` di atas. Tidak ada migration baru — skema `attendances` dari
Fase 1 sudah cukup untuk semua field yang dibutuhkan.

Tidak termasuk (akan dikerjakan di fase selanjutnya):
- Controller/logic untuk `evaluations`, `certificates`, reports/export.
- Dashboard Blade untuk admin.
- Package tambahan di luar Laravel + Sanctum.

Tidak ada file di luar scope Fase 2 yang tersentuh — module auth, interns,
divisions, office-locations dari Fase 1 tidak diubah, kecuali penambahan
`AttendanceController`, `AttendanceLocationService`, Form Request baru di
`app/Http/Requests/Attendance/`, route attendance baru di `routes/api.php`,
dan langkah `storage:link` di README ini. Tidak ada file/project Laravel lain
(HR, Logistax Core, portal-logistax) yang disentuh — repo ini tetap berdiri
sendiri sepenuhnya.

## Keputusan Desain: Logic Edit Evaluasi (PATCH /api/evaluations/{evaluation})

`total_score` dihitung dengan bobot tetap: discipline 25%, performance 35%,
attitude 25%, communication 15% (lihat `EvaluationScoringService`). Grade dari
`total_score`: A (85–100), B (70–84.99), C (55–69.99), D (<55). Dihitung ulang
setiap ada perubahan skor apa pun, baik lewat `POST` maupun `PATCH` — request
tidak pernah bisa mengirim `total_score`/`grade` secara langsung karena field
itu tidak ada di aturan Form Request, jadi otomatis diabaikan meski dikirim.

Aturan siapa boleh mem-PATCH evaluasi, dan efeknya (di `EvaluationController::update()`):

| Yang edit | Sudah ada certificate? | Hasil |
|---|---|---|
| `spv_mentor` yang sama dengan `evaluated_by` (penginput pertama) | Belum | Boleh edit bebas. `edited_by_admin` tetap `false`. |
| `spv_mentor` yang **bukan** `evaluated_by` | — | **403** — bukan mentor yang menginput evaluasi ini. |
| `spv_mentor` mana pun (termasuk `evaluated_by`) | Sudah ada | **403** — "Sertifikat sudah terbit, hanya admin yang bisa mengubah nilai. Sertifikat perlu digenerate ulang setelah ini." |
| `admin_magang` | Belum | Boleh edit. `edited_by_admin = true`, `last_edited_by = auth id`. |
| `admin_magang` | Sudah ada | Boleh edit. `edited_by_admin = true`, `last_edited_by = auth id`, **`needs_certificate_regeneration = true`**. Certificate lama TIDAK dihapus di sini. |

Kenapa `needs_certificate_regeneration` sebagai kolom terpisah (bukan
menghapus/mengubah certificate langsung): tanggung jawab generate ulang PDF
sertifikat ada di fase certificate selanjutnya, bukan di fase evaluation ini.
Flag ini cukup jadi penanda buat fase itu bahwa nilai berubah setelah
sertifikat terbit, tanpa evaluation module perlu tahu apa pun soal cara kerja
PDF generation atau harus menghapus record certificate yang mungkin masih
dipakai/didownload user.

## Scope Fase Ini (Fase 3 — Evaluation)

Ditambahkan di fase ini: model `Evaluation` (relasi `intern`, `evaluator`
[`evaluated_by`], `lastEditor` [`last_edited_by`]), `EvaluationScoringService`
(hitung `total_score` & `grade`), seluruh endpoint `/api/evaluations/*` dan
`/api/my-evaluation` di atas.

Migration tambahan: `add_needs_certificate_regeneration_to_evaluations_table`
— alter table nambah kolom `needs_certificate_regeneration` (boolean, default
`false`) ke tabel `evaluations` yang sudah ada dari Fase 1. Bukan migration
ulang. Kolom ini baru dipakai (dibaca) di fase certificate nanti; di fase ini
cuma disiapkan & di-set `true` sesuai skenario di tabel atas.

Tidak termasuk (akan dikerjakan di fase selanjutnya):
- Controller/logic untuk `certificates`, reports/export.
- Dashboard Blade untuk admin.
- Package tambahan di luar Laravel + Sanctum.

Tidak ada file di luar scope Fase 3 yang tersentuh — module auth, interns,
divisions, office-locations, attendance dari Fase 1 & 2 tidak diubah sama
sekali (model `Intern` juga tidak perlu diubah karena relasi `hasOne
evaluation()` sudah ada sejak Fase 1). Yang ditambahkan hanya
`EvaluationController`, `EvaluationScoringService`, Form Request baru di
`app/Http/Requests/Evaluation/`, route evaluation baru di `routes/api.php`,
migration alter table di atas, dan bagian ini di README. Tidak ada
file/project Laravel lain (HR, Logistax Core, portal-logistax) yang disentuh.

## Keputusan Desain: Package PDF & Password Protection (Fase 4 — Certificate)

**Render HTML -> PDF:** `spatie/browsershot` (headless Chrome via Puppeteer).
Dipilih karena mendukung CSS modern penuh (flexbox, `clip-path`,
`linear-gradient`, Google Fonts) yang dibutuhkan desain sertifikat ini —
generator PDF berbasis HTML lama seperti dompdf/mpdf tidak mendukung
`clip-path` atau flexbox dengan baik, sehingga bentuk geometris dekoratif dan
tata letak ribbon di template ini tidak akan ter-render sama seperti di
browser.

**Password-protect PDF:** `setasign/fpdi-tcpdf` (bridge `setasign/fpdi` +
`tecnickcom/tcpdf`). Browsershot/Chrome tidak punya opsi native untuk
mengenkripsi PDF dengan password, jadi dibutuhkan pass kedua: PDF hasil
Browsershot di-import halaman-per-halaman via FPDI ke dokumen TCPDF baru,
lalu `TCPDF::SetProtection()` dipanggil untuk menambahkan user password
sebelum disimpan. Dipilih dibanding alternatif seperti `pdftk`/`qpdf` (via
`mikehaertl/php-pdftk`) karena murni PHP — tidak butuh binary eksternal
tambahan yang harus di-install & ada di PATH sistem (penting untuk portability
di Windows/Laragon tempat project ini dikembangkan). Catatan: package ini
berstatus **abandoned** di Packagist (tidak ada rilis baru, tanpa pengganti
resmi disarankan), tapi masih berfungsi penuh untuk use case ini (sudah
diverifikasi: PDF hasil enkripsi punya `/Encrypt` dictionary yang valid).
Kalau ke depannya butuh alternatif yang aktif dikembangkan, opsi lain adalah
memanggil binary `qpdf`/`pdftk` via `Symfony\Component\Process` — butuh
instalasi terpisah di server, jadi tidak dipilih untuk fase ini.

Font untuk nama peserta di sertifikat: **Tangerine** (Google Font, weight
700). Dipilih di antara kandidat yang diberikan (Playfair Display, Tangerine,
Alex Brush, Sacramento) karena paling mendekati gaya "kaligrafi formal/elegan"
yang diminta — goresan mengalir klasik yang umum dipakai khusus untuk nama di
sertifikat resmi, dibanding Alex Brush/Sacramento yang lebih ke arah brush
script kasual (cocok untuk undangan/media sosial, bukan dokumen formal), dan
Playfair Display yang serif elegan tapi bukan kaligrafi/script sama sekali.

## Keputusan Desain: certificate_number Saat Regenerate

**Nomor sertifikat TETAP SAMA saat regenerate** (tidak mengambil nomor urut
baru dari `CertificateNumberService`). Alasan:
- Nomor sertifikat adalah identitas resmi dokumen untuk intern tsb. Regenerate
  karena revisi nilai (lewat evaluation PATCH oleh admin) atau perbaikan data
  lain bukan berarti itu menjadi sertifikat yang berbeda secara hukum/identitas
  — hanya kontennya yang diperbarui.
- Menghindari "membakar" slot counter tahunan untuk dokumen yang sebenarnya
  sama; kalau nomor baru diambil tiap regenerate, counter tahunan bisa
  melonjak jauh melebihi jumlah intern yang benar-benar lulus di tahun itu,
  padahal maksud counter adalah menghitung sertifikat unik per tahun.
- `issued_date` TETAP diperbarui ke tanggal regenerate (tanggal re-issue
  terbaru), karena itu mencerminkan kapan dokumen PDF yang beredar sekarang
  benar-benar diterbitkan/dicetak — beda konsep dengan nomor identitas.

File PDF lama otomatis tertimpa (path diturunkan deterministik dari
`certificate_number` lewat `CertificatePdfService::filenameFor()`), jadi tidak
ada file PDF versi lama yang jadi sampah/yatim di storage.

## Keputusan Desain: Batasan Endpoint Regenerate

`POST /api/certificates/regenerate/{internId}` **TIDAK dibatasi** hanya boleh
dipakai saat `evaluations.needs_certificate_regeneration = true` — admin_magang
boleh regenerate kapan saja selama certificate untuk intern itu sudah pernah
dibuat. Alasan: ada skenario valid untuk regenerate manual di luar perubahan
nilai, misalnya salah ketik `issued_city`, ingin memperbarui `issued_date`
untuk re-issue resmi, atau sekadar re-render ulang PDF setelah template
sertifikat direvisi (mis. logo/warna diperbaiki) — semuanya tidak selalu
berkaitan dengan evaluasi yang berubah. Membatasi endpoint ini hanya untuk
kasus flag `true` akan memaksa admin memanipulasi data evaluation dulu (atau
lewat query manual) hanya supaya bisa regenerate, yang lebih rumit dan rawan
disalahgunakan dibanding cukup mengizinkan admin_magang (role tertinggi)
regenerate langsung kapan pun dibutuhkan. Flag
`needs_certificate_regeneration` tetap berguna sebagai **penanda/reminder**
(dicek & ditampilkan di data evaluation) bahwa nilai berubah setelah
sertifikat terbit — bukan sebagai gate keras di endpoint ini.

## Scope Fase Ini (Fase 4 — Certificate)

Ditambahkan: `CertificateNumberService` (nomor + proteksi race condition lewat
`lockForUpdate`), `CertificatePdfService` (render Browsershot + password FPDI),
`CertificateController` (7 endpoint di atas), template
`resources/views/certificates/template.blade.php`, `config/browsershot.php`.

**Tidak ada migration baru untuk tabel `certificates`** — semua kolom yang
dibutuhkan (`certificate_number`, `issued_date`, `issued_city`, `pdf_url`,
`pdf_password`, `download_count`, `generated_by`) sudah cukup dari Fase 1.
Path file PDF sengaja tidak disimpan sebagai kolom terpisah — diturunkan
deterministik dari `certificate_number` (lihat `CertificatePdfService::filenameFor()`),
supaya tidak perlu alter table.

Package baru yang diinstall: `spatie/browsershot`, `setasign/fpdi-tcpdf`
(otomatis membawa `setasign/fpdi` & `tecnickcom/tcpdf`), plus `puppeteer` via
npm (bukan composer — lihat langkah setup di atas). Semua disebutkan sesuai
arahan task, tidak ada package tambahan lain yang dipasang tanpa alasan
langsung terkait fitur ini.

Tidak termasuk (fase selanjutnya): module reports/export.

Tidak ada file di luar scope Fase 4 yang tersentuh — module auth, interns,
divisions, office-locations, attendance, evaluations dari Fase 1–3 tidak
diubah. Model `Intern` juga tidak perlu diubah karena relasi `hasOne
certificate()` sudah ada sejak Fase 1. Tidak ada file/project Laravel lain
(HR, Logistax Core, portal-logistax) yang disentuh.

## Contoh PDF Sertifikat

Contoh sertifikat asli (data dummy meniru gambar referensi, sudah dipassword
dengan NIM `5503230014`) disimpan di
`storage/app/private/sample-certificates/contoh-sertifikat-Yasinta-Putri-Maulani.pdf`
untuk keperluan review visual. File ini di luar disk `public` (tidak
ter-expose lewat URL) supaya tidak tercampur dengan certificate yang benar-benar
digenerate lewat endpoint di masa depan.

## Revisi Desain Template (berdasarkan referensi visual & logo asli)

Setelah preview pertama, template direvisi mengacu ke dua asset yang
diberikan:

- `public/images/logo-logistax.jpeg` — logo asli. **Dipakai langsung sebagai
  `<img>`**, bukan direka ulang pakai teks+CSS. Karena file aslinya berupa
  kanvas persegi dengan banyak whitespace di sekitar wordmark (hanya ~20%
  dari tinggi kanvas yang benar-benar berisi logo), whitespace itu di-trim
  lebih dulu ke `public/images/logo-logistax-trimmed.png` (logo yang sama,
  cuma tanpa kanvas kosong) — kalau tidak di-trim, bounding box `<img>`
  jadi terlalu tinggi dan menutupi judul "SERTIFIKAT" di bawahnya (JPEG
  opaque, tidak transparan). `CertificatePdfService::logoBase64()` membaca
  file trimmed ini dan embed sebagai data URI di template.
- `storage/app/private/design-reference/sertifikat-referensi.png` — dipakai
  sebagai acuan utama untuk posisi/ukuran/warna semua elemen: title
  "SERTIFIKAT" dipindah jadi left-aligned (bukan center) mengikuti logo,
  ribbon "PENGHARGAAN" dibuat flush ke tepi kiri (bleed), kolom
  "Di berikan kepada / nama / NIM / paragraf / tanggal" digeser ke kanan
  (bukan lagi rata tengah halaman), ukuran font judul & paragraf dibesarkan
  signifikan (judul ±58pt, paragraf ±17pt — sebelumnya 36pt/11pt, jauh lebih
  kecil dari referensi), dan warna dekorasi disamakan persis dengan hasil
  sampling piksel dari gambar referensi (navy `#193b68`, biru `#3072c9`,
  cyan `#63c3ef`).
- Shape dekoratif pojok kanan-atas & kiri-bawah dibangun ulang sebagai 3
  parallelogram bertingkat (navy/biru/cyan) yang **bleed ke tepi fisik
  halaman** (posisi negatif dibiarkan terpotong oleh `overflow:hidden` pada
  `body`, bukan dikurung dalam frame kecil seperti revisi sebelumnya) — sesuai
  gaya di gambar referensi — sambil tetap dijaga jaraknya dari judul dan
  blok tanda tangan supaya tidak menimpa teks.

Ini masih hasil kalibrasi manual (ukur piksel dari gambar referensi lalu
dikonversi ke mm), jadi kemungkinan masih perlu penyesuaian halus lagi kalau
ada detail yang belum pas — silakan koreksi lagi.

## Auth Web (Dashboard) vs Auth API (Mobile)

Ada dua jalur login yang benar-benar terpisah, sengaja tidak digabung:

| | Web Dashboard | API (mobile/testing) |
|---|---|---|
| Controller | `App\Http\Controllers\Web\AuthenticatedSessionController` | `App\Http\Controllers\Api\AuthController` (Fase 1, tidak diubah) |
| Mekanisme | Session (guard `web`, cookie) | Token Sanctum (`Authorization: Bearer ...`) |
| Route | `routes/web.php` (`/login`, `/logout`) | `routes/api.php` (`/api/auth/admin/login`, dst) |
| Middleware proteksi | `admin.role.web:admin_magang,spv_mentor` (baru, redirect ke `/login` / abort 403 HTML) | `admin.role:admin_magang,spv_mentor` (Fase 1, JSON 403, tidak diubah) |

Keduanya autentikasi ke tabel `admin_users` yang sama (`Auth::guard('web')->attempt()`
memverifikasi password via `Hash::check` seperti biasa), tapi tokennya independen —
login di dashboard tidak membuat token Sanctum, dan sebaliknya. `EnsureAdminRoleWeb`
adalah middleware baru (bukan mengubah `EnsureAdminRole` yang sudah ada), supaya
kegagalan otorisasi di web merender halaman/redirect, bukan JSON.

## Reuse Logic API vs Web (Fase 5)

Task ini secara eksplisit meminta approve/reject dkk di web memanggil logic yang
sama dengan API, bukan reimplementasi. Pendekatan yang diambil: extract ke
service class, dipakai oleh controller API maupun controller Web. Behavior/response
shape endpoint API yang sudah ada tidak berubah (sudah diverifikasi ulang lewat
`requests.http`/curl setelah refactor — lihat ringkasan di akhir sesi ini).

Service baru:
- `App\Services\InternWorkflowService` — approve/reject/extend/mark-failed/mark-completed/createByAdmin intern. Diambil dari `Api\InternController`.
- `App\Services\AttendanceApprovalService` — approve/reject leave-request. Diambil dari `Api\AttendanceController`.
- `App\Services\EvaluationWorkflowService` — create/update evaluasi, termasuk seluruh matriks izin edit (admin vs mentor vs certificate-locked). Diambil dari `Api\EvaluationController`.
- `App\Services\CertificateIssuingService` — generate/regenerate/resolveDownload sertifikat (membungkus `CertificateNumberService` + `CertificatePdfService` yang sudah ada dari Fase 4). Diambil dari `Api\CertificateController`.

Semua service ini melempar `App\Exceptions\DomainActionException` (pesan + status
HTTP) kalau aturan bisnis menolak aksi (mis. "sudah di-approve", "bukan mentornya").
Controller API menangkapnya dan membungkus jadi JSON `{success:false,...}` seperti
sebelumnya; controller Web menangkapnya dan `back()->with('error', ...)`. Satu
sumber kebenaran, dua cara penyajian.

Form Request validasi (`StoreInternRequest`, `ApproveInternRequest`,
`RejectAttendanceRequest`, `StoreEvaluationRequest`, dll — semuanya dari Fase 1-4)
dipakai ulang apa adanya oleh controller Web, karena aturan validasinya memang
tidak guard-specific.

## Halaman Dashboard (Fase 5)

Sidebar: Dashboard, Manajemen Intern, Absensi, Penilaian, Sertifikat & Laporan —
collapsible (toggle di header sidebar, state disimpan di `localStorage` lewat
Alpine `x-data`/`$watch`), highlight menu aktif berdasarkan route saat ini.

- `/dashboard` — 4 stat card, chart tren kehadiran 7 hari (Chart.js), quick
  actions, tabel "Intern Perlu Perhatian". Semua angka query Eloquent langsung
  dari controller (bukan hit API sendiri), di-scope ke mentee untuk `spv_mentor`.
- `/interns` — tab Daftar Intern (search+filter+pagination) & Pending
  Registrasi (approve inline / reject via modal Alpine). Modal detail intern
  (Absensi/Nilai/Sertifikat) di-fetch on-demand lewat `fetch()` ke
  `GET /interns/{intern}/detail` (endpoint web baru, JSON, bukan endpoint API) —
  supaya list tidak perlu precompute summary utuh untuk semua baris sekaligus.
- `/attendance` — tab Rekap (grid kehadiran per hari per intern, warna per
  status) & Perlu Approval (approve/reject dengan modal alasan).
- `/evaluations` — daftar intern perlu dinilai; form skor pakai Alpine untuk
  live-preview total & grade saat mengetik/geser slider — tapi ini murni
  kosmetik di browser. Total & grade yang benar-benar tersimpan selalu dihitung
  ulang di server oleh `EvaluationScoringService` lewat `EvaluationWorkflowService`,
  nilai dari form sama sekali tidak dipakai untuk itu.
- `/certificates` — tab Sertifikat (generate/download/regenerate/preview) &
  Laporan (export CSV). Preview dibuka di tab baru (`target="_blank"`) ke route
  web baru `certificates.preview`, yang memanggil service render PDF yang
  persis sama dengan `GET /api/certificates/preview/{id}` — dibuat sebagai
  route terpisah (bukan reuse endpoint API secara langsung) karena endpoint API
  digembok Sanctum Bearer token, sedangkan browser tab biasa hanya mengirim
  session cookie; me-reuse endpoint API apa adanya dari sini tidak mungkin tanpa
  mengubah proteksinya (yang dilarang oleh scope).

## Export Laporan (Fase 5)

`App\Http\Controllers\Web\ReportController::export()` — CSV sederhana lewat PHP
native (`fputcsv`), tidak ada package baru yang diinstall untuk ini. Melayani
2 tombol: "Export CSV" di tab Rekap Absensi, dan form "Export Laporan" di tab
Laporan (jenis: Absensi/Nilai/Sertifikat, semua di-scope mentor untuk
`spv_mentor`). PDF/Excel berformat (mis. `maatwebsite/excel`) bisa ditambahkan
belakangan tanpa mengubah kontrak endpoint ini — sengaja tidak diinstall sekarang
karena task memperbolehkan CSV sebagai "export sederhana" duluan.

## Keputusan Desain & Hal Ambigu (Fase 5)

- Sidebar collapsible: file `.dc.html` referensi tidak menunjukkan tombol
  toggle collapse secara eksplisit, tapi task secara eksplisit mewajibkannya —
  ditambahkan tombol chevron di header sidebar, lebar menciut dari 232px ke
  76px (ikon tetap terlihat, label teks disembunyikan), state persist di
  `localStorage` per browser.
- Kriteria "telat approve pendaftaran" (dashboard "Intern Perlu Perhatian"):
  intern `status=pending` yang `created_at`-nya sudah lebih dari 3 hari
  yang lalu. SLA 3 hari dipilih sebagai asumsi wajar untuk admin memproses
  registrasi baru; angka ini murni keputusan implementasi (tidak disebutkan
  di task) — gampang diubah di `DashboardController::needAttention()` kalau
  SLA sebenarnya berbeda. Kriteria ini juga sengaja tidak ditampilkan untuk
  `spv_mentor` karena approve/reject registrasi adalah wewenang `admin_magang`
  saja.
- Detail intern: referensi menunjukkan modal (bukan halaman terpisah) —
  diikuti persis, modal Alpine + fetch on-demand, bukan route `interns/{id}` baru.
- Preview sertifikat: referensi menyebut dua opsi ("modal preview... atau
  tampilkan sebagai link buka tab baru") — dipilih buka tab baru, karena
  PDF embed di dalam modal `<iframe>` sering bermasalah lintas-browser dan PDF
  hasil Browsershot sudah pasti valid untuk dibuka langsung oleh viewer native
  browser.

## Scope Fase Ini (Fase 5 — Dashboard Blade)

Ditambahkan: seluruh `resources/views/{layouts,auth,dashboard,interns,attendance,evaluations,certificates}`,
`routes/web.php`, controller di `App\Http\Controllers\Web\*`, middleware
`EnsureAdminRoleWeb`, 4 service reusable di atas, `App\Exceptions\DomainActionException`,
helper tampilan kecil (`App\Support\Badge`, `AdminUser::initials()`/`roleLabel()`
— murni presentational, tidak mempengaruhi auth/API).

Tidak ada perubahan behavior pada controller API Fase 1-4 — hanya
di-refactor untuk memanggil service yang sama (diverifikasi ulang lewat
regression test manual: approve intern, store evaluation, dsb — response JSON,
status code, dan pesan error identik sebelum/sesudah refactor).

Package baru: tidak ada package Composer baru. Frontend: Tailwind v4 sudah
terpasang bawaan Laravel starter kit (tinggal dipakai), Alpine.js & Chart.js
dimuat lewat CDN (`<script>` tag) sesuai instruksi task, bukan lewat npm.

Tidak ada file/project Laravel lain yang disentuh. `routes/api.php` tidak diubah
sama sekali (hanya `routes/web.php` yang baru).
