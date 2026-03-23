FlowHR – AI Development Guardrails

Project: FlowHR (Laravel Web Application)
Owner: Naufal Ma’ruf Ashrori
AI Agent: GPT Codex (VS Code Extension)
Scope: Web Laravel Only

1. Project Context

FlowHR adalah aplikasi HR berbasis Laravel yang sudah stabil dan berjalan dengan fitur:

Leave (Cuti)

Reimbursement

Overtime

Official Travel

Multi-Level Approval (Approver 1, 2, 3 Finance)

Multi-Role User (Role selectable after login)

⚠️ Sistem sudah digunakan dan tidak boleh dirusak.

2. Core Development Principle

Tambahan fitur dan perbaikan UI boleh dilakukan,
tetapi tidak boleh mengubah flow bisnis yang sudah berjalan.

Semua perubahan harus:

Backward compatible

Tidak memecah approval logic

Tidak mengubah struktur role existing

Tidak mengubah route existing tanpa alasan kuat

3. Scope of Work (Allowed)

AI hanya boleh bekerja pada:

3.1 UI Refactor

Konsistensi komponen

Perbaikan layout

Styling improvement

Status badge standardization

Approval inbox improvement

Sidebar refinement

Rebranding (logo, color palette, typography)

⚠️ Tidak boleh mengubah logic controller tanpa diminta.

3.2 Attendance Module (Web Side Only)

Web Laravel hanya bertanggung jawab untuk:

Attendance log monitoring

Attendance policy setup

Shift configuration

Geofence configuration

Reporting & export

Attendance correction approval

Web Laravel TIDAK BOLEH:

Meng-handle QR scanning

Mengakses GPS device

Membuat logic mobile check-in

Membuat React Native code

4. Critical Rule – Mobile Separation

Fitur absensi utama (check-in / check-out, QR scan, GPS capture)
akan dibahas dan dikembangkan di aplikasi React Native terpisah.

AI DILARANG:

Membuat folder React Native

Membuat kode React Native

Menambahkan dependencies mobile

Menyarankan implementasi mobile di project ini

Project ini adalah Laravel Web Only.

5. Existing Approval Flow (DO NOT MODIFY)

Leave:

Employee → Approver 2 → Done

Reimbursement / Overtime / Official Travel:

Employee → Approver 1 → Approver 2 → Approver 3 (Finance) → Done

AI tidak boleh:

Mengubah urutan approval

Menggabungkan flow

Mengubah role approval

Mengubah struktur status tanpa instruksi eksplisit

6. Role System Rules

User bisa memiliki multiple role

Setelah login, user memilih role aktif

Role switching tetap dipertahankan

AI tidak boleh:

Mengubah mekanisme role switching

Menghapus multi-role capability

Mengubah relasi role-user

7. Attendance Data Design Rules

Jika membuat tabel attendance:

Harus additive (tidak mengubah tabel lama)

Harus modular

Harus terpisah dari tabel approval lama

Dilarang:

Menggabungkan attendance ke tabel leave

Mengubah struktur reimbursement/overtime/travel

8. Database Safety Policy

Sebelum membuat migration:

Pastikan tidak mengubah kolom existing

Jangan rename kolom lama

Jangan drop kolom lama

Jangan ubah foreign key lama

Jika perubahan besar diperlukan:

Harus disetujui eksplisit oleh owner

9. Code Quality Standard

Semua kode baru harus:

Mengikuti struktur Laravel standar

Menggunakan Service Layer jika logic kompleks

Tidak menaruh business logic berat di Blade

Menggunakan Form Request validation

Menggunakan Policy untuk authorization

10. UI & Rebranding Rules

UI Refactor harus:

Tidak mengubah route

Tidak mengubah nama view secara sembarangan

Tidak mengubah API response format

Tidak menghapus komponen lama tanpa pengganti

Rebranding boleh:

Update color palette

Update typography

Update layout spacing

Update logo

Tidak boleh:

Mengubah struktur menu drastis tanpa diskusi

11. Approval Inbox Design Constraint

Approval Inbox boleh:

Dipisah per kategori (leave, reimbursement, overtime, official travel)

Atau digabung dengan filter

Namun:

Logic query approval tidak boleh diubah

Status existing tidak boleh diganti

Query harus mengikuti role aktif

12. API Rules

Jika membuat API untuk attendance:

Prefix: /api/attendance/*

Tidak mengubah API lama

Tidak menghapus endpoint lama

Web hanya menyediakan:

GET log

GET policy

POST policy

GET report

POST correction approval

13. Guardrail Against Over-Engineering

AI tidak boleh:

Mengganti arsitektur menjadi microservice

Menambahkan Redis tanpa instruksi

Mengubah ke SPA tanpa instruksi

Menambahkan framework baru

Mengubah Laravel version

Fokus: Enhancement, bukan Rewrite.

14. Safe Refactor Procedure

Jika ingin refactor:

Analisis file lama

Pastikan tidak memecah dependency

Jangan rename method public

Jangan ubah signature function yang dipakai luas

15. Regression Prevention

Setiap perubahan harus:

Tidak merusak approval lama

Tidak mengubah perhitungan status

Tidak mengubah relasi user-role

Tidak merusak login

Jika ragu → Jangan ubah.

16. When Unsure

Jika AI tidak yakin:

Jangan berasumsi

Jangan mengubah struktur

Berikan alternatif

Tunggu instruksi owner

17. Priority Order

Stabilitas sistem existing

UI improvement

Attendance monitoring module

Rebranding

Bukan sebaliknya.

18. Summary Directive

FlowHR adalah sistem produksi yang stabil.
Tugas AI adalah:

Menambahkan

Memperbaiki

Menyempurnakan

Bukan:

Menghapus

Mengganti arsitektur

Mengubah flow approval

Semua pengembangan harus bersifat non-destructive.
