# FlowHR

FlowHR adalah aplikasi utama HRIS berbasis Laravel untuk web internal dan mobile API. Repo ini menangani:

- autentikasi dan role-based access untuk `super-admin`, `admin`, `manager`, `approver`, `finance`, dan `employee`
- pengajuan dan approval `reimbursement`, `overtime`, `official travel`, dan `leave`
- attendance web + mobile, QR attendance, koreksi attendance, dan dashboard attendance
- mobile API untuk aplikasi `flowhr-mobile-employee`
- pembuatan request export report yang didelegasikan ke `FlowHR-reporting-app`

## Arsitektur

- `FlowHR`: aplikasi utama web + mobile API
- `FlowHR-reporting-app`: service internal untuk enqueue dan proses report async
- `flowhr-mobile-employee`: aplikasi mobile employee berbasis Expo React Native

Alur reporting:

1. User klik export dari FlowHR.
2. FlowHR membuat row `report_exports` di database utama.
3. FlowHR memanggil endpoint internal reporting app.
4. Reporting app memasukkan job ke queue `reports`.
5. Worker reporting memproses file PDF/XLSX/ZIP ke shared storage.
6. FlowHR membaca status dan menyediakan tombol download hasil export.

## Tech Stack

- PHP 8.2+ / 8.3
- Laravel 12
- PostgreSQL
- Laravel Sanctum
- Spatie Laravel Permission
- Laravel Queue dengan `database` driver
- Laravel Vite
- Tailwind CSS 4
- Alpine.js
- DomPDF
- Laravel Excel / PhpSpreadsheet
- Pusher / Laravel Echo dependencies untuk realtime frontend

## Dependency Utama

### Backend Composer

- `laravel/framework`
- `laravel/sanctum`
- `laravel/octane`
- `spatie/laravel-permission`
- `barryvdh/laravel-dompdf`
- `maatwebsite/excel`
- `pusher/pusher-php-server`

### Frontend NPM

- `vite`
- `tailwindcss`
- `@tailwindcss/vite`
- `alpinejs`
- `axios`
- `laravel-echo`
- `pusher-js`

## Prasyarat

- Git
- PHP 8.2 atau lebih baru
- Composer
- Node.js 20+ dan npm
- PostgreSQL
- Windows/Laragon atau environment lokal lain yang bisa menjalankan PHP, Node, dan PostgreSQL

## Clone dan Install

Clone repo:

```bash
git clone <repo-url>
cd "FlowHR Ukom Project"
```

Masuk ke folder aplikasi utama:

```bash
cd FlowHR
```

Install dependency backend dan frontend:

```bash
composer install
npm install
```

## Konfigurasi Environment

Salin file environment:

```bash
copy .env.example .env
```

Atur minimal value berikut di `.env`:

```env
APP_NAME=FlowHR
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=flowhr
DB_USERNAME=postgres
DB_PASSWORD=your_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

REPORT_LEGACY_EXPORT_ENABLED=false
REPORTING_DISPATCH_ENABLED=true
REPORTING_INTERNAL_URL=http://127.0.0.1:8001
REPORTING_INTERNAL_ENQUEUE_PATH=/api/internal/report-exports/enqueue
REPORTING_INTERNAL_TIMEOUT_SECONDS=10
REPORTING_INTERNAL_CLIENT_ID=flowhr-main-app
REPORTING_INTERNAL_SHARED_SECRET=replace_with_same_secret_as_reporting_app
REPORTING_INTERNAL_CLOCK_SKEW_SECONDS=60

REPORT_SHARED_DISK=report_shared
REPORT_SHARED_ROOT="D:/absolute/path/to/shared-report-storage"
REPORT_SUMMARY_PDF_MAX_ROWS=300
```

Catatan:

- `REPORTING_INTERNAL_SHARED_SECRET` harus sama persis dengan value di `FlowHR-reporting-app/.env`
- `REPORT_SHARED_ROOT` harus menunjuk folder absolut yang sama persis dengan reporting app
- untuk mobile device fisik, backend sebaiknya dijalankan dengan `--host=0.0.0.0`

## Bootstrap Database

Generate app key, jalankan migration, seed data demo, dan buat symbolic link storage:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan config:clear
```

Seeder bawaan membuat user demo dengan password default:

- `akbar@gmail.com` / `password`
- user ini memiliki role `superAdmin`, `admin`, `approver`, `employee`, `manager`, dan `finance`
- seluruh user hasil seed memakai domain `gmail.com` dan password `password`

## Menjalankan FlowHR

### Opsi 1: mode sederhana

Terminal 1:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Terminal 2:

```bash
npm run dev
```

### Opsi 2: helper script Composer

```bash
composer dev
```

Catatan:

- `composer dev` akan menjalankan web server, queue listener default, log tail, dan Vite
- reporting async tetap membutuhkan `FlowHR-reporting-app` yang dijalankan terpisah

## URL Penting

- Web app: `http://127.0.0.1:8000`
- Mobile API base: `http://127.0.0.1:8000/api/mobile`

## Mobile API yang Dipakai Aplikasi Mobile

Endpoint utama:

- `POST /api/mobile/auth/login`
- `GET /api/mobile/auth/me`
- `POST /api/mobile/auth/logout`
- `GET /api/mobile/dashboard`
- `GET /api/mobile/employee/leave`
- `POST /api/mobile/attendance/check-in`
- `POST /api/mobile/attendance/check-out`
- `GET /api/mobile/attendance/history`
- `GET /api/mobile/attendance/history/{attendanceId}`
- `GET /api/mobile/attendance/corrections`
- `POST /api/mobile/attendance/corrections`

## Agar Reporting Bisa Dipakai

FlowHR saja tidak cukup. Anda juga harus menyalakan `FlowHR-reporting-app`.

Service yang wajib aktif bersamaan:

1. `FlowHR` web server
2. `FlowHR` Vite dev server
3. `FlowHR-reporting-app` internal API di port `8001`
4. `FlowHR-reporting-app` queue worker untuk queue `reports`

Lihat README di folder `FlowHR-reporting-app` untuk setup lengkap service reporting.

## Alur Menjalankan Semua Komponen Sampai Siap Dipakai

1. Clone repo
2. Setup `FlowHR`
3. Setup `FlowHR-reporting-app`
4. Setup `flowhr-mobile-employee`
5. Jalankan PostgreSQL
6. Jalankan `FlowHR` di port `8000`
7. Jalankan `FlowHR-reporting-app` di port `8001`
8. Jalankan worker reporting
9. Jalankan aplikasi mobile dengan base URL ke `FlowHR`

## Testing dan Verifikasi Dasar

Perintah yang berguna:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan test
```

Verifikasi reporting dari FlowHR:

1. Login ke web
2. Buka halaman admin/super-admin/finance yang punya tombol export
3. Buat export report
4. Pastikan status berubah `queued -> processing -> completed`

Jika status berhenti di `queued`, biasanya masalahnya ada di reporting app atau worker `reports`.
