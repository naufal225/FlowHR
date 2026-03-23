Project: FlowHR – Attendance Module, UI Refactor & Rebranding

Author: Naufal Ma’ruf Ashrori
Version: 1.0
Status: Draft – For AI Agents & Development Execution
Scope Type: Enhancement (Non-Breaking, Non-Disruptive)

1. Executive Summary

FlowHR adalah aplikasi HR berbasis web yang saat ini telah berjalan dan digunakan untuk:

Pengajuan Cuti

Reimbursement

Overtime

Official Travel

Multi-level Approval (Approver 1, 2, 3 Finance)

Pengembangan ini bertujuan untuk:

Menambahkan modul absensi (Attendance)

Melakukan perbaikan UI menyeluruh (tanpa mengubah logic bisnis)

Melakukan rebranding visual FlowHR

Mengembangkan aplikasi React Native terpisah untuk absensi dan approval

⚠️ Constraint utama:

Tidak boleh mengubah flow approval existing.

Tidak boleh mengubah struktur besar sistem yang sudah stabil.

Perubahan bersifat additive dan cosmetic improvement.

2. Product Goals
2.1 Tujuan Bisnis

Meningkatkan kontrol kehadiran karyawan.

Menyediakan audit-ready attendance data.

Menyatukan ekosistem HR (approval + attendance).

Meningkatkan profesionalisme visual brand FlowHR.

2.2 Tujuan Teknis

Absensi hanya dilakukan melalui mobile (React Native).

Web Laravel hanya untuk:

Monitoring

Reporting

Policy configuration

Audit log

3. Non-Goals

Tidak membangun sistem payroll.

Tidak merombak sistem role existing.

Tidak mengubah flow approval lama.

Tidak mengubah arsitektur database besar-besaran.

4. User Roles (Existing - No Major Change)

Super Admin

Admin

Approver 1

Approver 2

Approver 3 (Finance)

Employee

User dapat memiliki multiple role.
Setelah login, user memilih role aktif.
Role switching tetap dipertahankan.

5. Feature Scope
5.1 Attendance System (NEW MODULE)
Architecture Decision
Function	Platform
Check-in / Check-out	React Native
QR Scan	React Native
GPS Capture	React Native
Attendance Log View	Web Laravel
Policy Setup	Web Laravel
Attendance Report Export	Web Laravel

Decision:
Absensi tidak boleh dilakukan melalui web.

Alasan:

Validitas GPS lebih reliable di mobile.

Kamera & QR scan lebih stabil.

Anti-fraud lebih kuat.

5.2 Attendance Business Rules
5.2.1 Attendance Modes

On-Site QR Attendance

QR dinamis

Expired 30–60 detik

Validasi radius geofence

Field Attendance

Tanpa QR

Wajib GPS

Wajib note

Optional selfie (future phase)

5.2.2 Check-In Rules

1 check-in per shift

Tidak bisa double check-in

Check-out wajib setelah check-in

GPS accuracy <= 50 meter

Harus dalam radius geofence kantor

5.2.3 Geofence Policy

Diset di Web:

Lokasi kantor

Radius (meter)

Berlaku per divisi (optional)

5.2.4 Shift Policy

Fixed shift (08:00–17:00)

Flexible shift (range window)

Grace period (contoh: 15 menit)

5.2.5 Fraud Prevention

QR dinamis

Expired token

Validasi timestamp server

Device ID capture

Flag suspicious attendance

5.3 Attendance Data Model (High-Level)

Tables (New):

attendance_shifts
attendance_policies
attendance_records
attendance_qr_tokens
attendance_corrections

attendance_records fields:

id

user_id

check_in_time

check_out_time

latitude

longitude

accuracy

device_id

mode (qr/field)

status (present/late/outside_radius/invalid)

flagged (boolean)

6. UI Refactor (Web Laravel)
Objective

Meningkatkan konsistensi visual.

Meningkatkan kecepatan approval.

Tidak mengubah flow logic.

6.1 UI Principles

Konsisten warna status:

Pending → Yellow

Approved → Green

Rejected → Red

Semua modul punya timeline approval.

Loading / Empty / Error state jelas.

Sidebar konsisten semua role.

Role active indicator jelas.

6.2 Target UI Area

Dashboard

Approval Inbox

Request Detail Page

Submission Form

Attendance Monitoring

Report Page

7. Rebranding FlowHR
7.1 Scope Rebranding

Logo refresh

Primary color palette

Typography standardization

Favicon

Email template design

7.2 Non-Breaking Rebrand

Tidak mengubah nama database.

Tidak mengubah route.

Tidak mengubah API endpoint.

8. API Contract (Attendance)

POST /api/attendance/check-in
POST /api/attendance/check-out
GET /api/attendance/log
POST /api/attendance/correction

All attendance endpoints hanya diakses oleh mobile.

Web hanya GET log & config policy.

9. Reporting (Web)

Admin / Finance dapat:

Filter per tanggal

Filter per divisi

Export CSV / XLSX

View flagged attendance

10. Success Metrics

< 2% invalid attendance

Approval time lebih cepat

UI usability feedback meningkat

Tidak ada regression bug pada fitur lama

11. Rollout Plan

Phase 1 – UI Refactor
Phase 2 – Attendance API
Phase 3 – React Native App
Phase 4 – Hardening & Fraud Detection

12. Risks & Mitigation

Risk: GPS spoofing
Mitigation: flag suspicious pattern

Risk: User confusion
Mitigation: onboarding tutorial

Risk: Breaking old feature
Mitigation: no modification core approval logic

13. Future Expansion

Face verification

Payroll integration

BI analytics dashboard
