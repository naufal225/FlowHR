# Mobile Attendance History Backend Plan

**Tanggal dibuat:** 2 April 2026

## Ringkasan
- Fokus iterasi ini hanya backend Laravel untuk kebutuhan History di mobile app employee.
- Reuse endpoint existing `GET /api/mobile/attendance/history` dan service existing `AttendanceHistoryService`.
- Perubahan bersifat additive dan non-breaking.

## Tujuan
- Menyediakan data badge correction pada setiap item history attendance.
- Menjaga kompatibilitas kontrak API lama untuk mobile client existing.
- Menghindari service/endpoint baru selama kebutuhan bisa ditangani struktur yang sudah ada.

## Perubahan Implementasi
1. Model `Attendance`
- Tambah relasi `corrections()` ke `AttendanceCorrection`.
- Tambah relasi `latestCorrection()` untuk mengambil correction terbaru per attendance.

2. Service `AttendanceHistoryService`
- Tambah eager-load `latestCorrection` pada query history agar tidak N+1.
- Kolom minimum yang diambil dari correction: `id`, `attendance_id`, `status`, `updated_at`.

3. Controller `AttendanceController` (API Mobile)
- Extend payload setiap row history dengan field:
  - `correction.has_correction`
  - `correction.latest_status`
  - `correction.latest_updated_at`

4. Test
- Update feature test history agar memverifikasi field `correction` di payload.
- Pastikan skenario ada/tidak ada correction tetap valid.

## Kontrak API Publik (Additive)
- Endpoint tetap: `GET /api/mobile/attendance/history`
- Tambahan baru pada setiap item `data[]`:
  - `correction: { has_correction: boolean, latest_status: string|null, latest_updated_at: string|null }`

## Validasi
- Jalankan test: `php artisan test tests/Feature/Api/Mobile/Attendance/AttendanceControllerTest.php`
- Pastikan tidak ada regression pada response shape existing (`data`, `meta`, filter, pagination).

