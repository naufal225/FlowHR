<?php

namespace Tests\Feature\Web\Admin;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceCorrectionFlowTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_employee_submit_correction_creates_pending_request_with_original_snapshot(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        $employee = $this->createEmployee([], $office);
        $this->assignRole($employee, Roles::Employee->value);

        $attendance = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-30',
            'check_in_at' => Carbon::parse('2026-03-30 09:25:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-03-30 17:10:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::LATE,
            'check_out_status' => AttendanceCheckOutStatus::NORMAL,
            'record_status' => AttendanceRecordStatus::COMPLETE,
            'late_minutes' => 10,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 10,
        ]);

        $response = $this->actingAs($employee)
            ->withSession(['active_role' => Roles::Employee->value])
            ->post(route('employee.attendance.corrections.store'), [
                'attendance_record_id' => $attendance->id,
                'requested_check_in_time' => '2026-03-30T09:05',
                'requested_check_out_time' => '2026-03-30T17:30',
                'reason' => 'Check-in terlambat tercatat padahal saya sudah datang lebih awal.',
            ]);

        $response->assertRedirect(route('employee.attendance.show', $attendance->id));

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $employee->id,
            'status' => 'pending',
        ]);

        $correction = AttendanceCorrection::query()->firstOrFail();

        $this->assertSame('2026-03-30', $correction->original_attendance_snapshot['work_date']);
        $this->assertSame('late', $correction->original_attendance_snapshot['check_in_status']);
        $this->assertSame('complete', $correction->original_attendance_snapshot['record_status']);
        $this->assertSame(10, $correction->original_attendance_snapshot['late_minutes']);
    }

    public function test_admin_can_open_pending_corrections_inbox(): void
    {
        $office = $this->createOfficeLocation(['name' => 'Jakarta HQ']);
        $employee = $this->createEmployee(['name' => 'Employee Tester'], $office);
        $admin = $this->createEmployee(['name' => 'Admin HR'], $office);
        $this->assignRole($employee, Roles::Employee->value);
        $this->assignRole($admin, Roles::Admin->value);

        $attendance = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-29',
        ]);

        AttendanceCorrection::query()->create([
            'user_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-29 09:00:00', 'Asia/Jakarta'),
            'reason' => 'Mesin absensi terlambat sinkron.',
            'original_attendance_snapshot' => ['work_date' => '2026-03-29'],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->get(route('admin.attendance.corrections.index'));

        $response->assertOk()
            ->assertSee('Attendance Corrections', false)
            ->assertSee('Employee Tester', false)
            ->assertSee('Mesin absensi terlambat sinkron.', false)
            ->assertSee('Pending', false);
    }

    public function test_admin_approve_updates_attendance_and_marks_correction_reviewed(): void
    {
        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
        ]);

        $employee = $this->createEmployee([], $office);
        $admin = $this->createEmployee(['name' => 'Attendance Admin'], $office);
        $this->assignRole($employee, Roles::Employee->value);
        $this->assignRole($admin, Roles::Admin->value);

        $attendance = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-28',
            'check_in_at' => Carbon::parse('2026-03-28 09:40:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-03-28 16:15:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::LATE,
            'check_out_status' => AttendanceCheckOutStatus::EARLY_LEAVE,
            'record_status' => AttendanceRecordStatus::COMPLETE,
            'late_minutes' => 25,
            'early_leave_minutes' => 45,
            'overtime_minutes' => 0,
            'is_suspicious' => true,
            'suspicious_reason' => 'Manual mismatch',
        ]);

        $correction = AttendanceCorrection::query()->create([
            'user_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-28 09:05:00', 'Asia/Jakarta'),
            'requested_check_out_time' => Carbon::parse('2026-03-28 17:45:00', 'Asia/Jakarta'),
            'reason' => 'Absensi pulang dan datang perlu dikoreksi.',
            'original_attendance_snapshot' => [
                'work_date' => '2026-03-28',
                'check_in_status' => 'late',
                'check_out_status' => 'early',
                'record_status' => 'complete',
            ],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.attendance.corrections.review', $correction->id), [
                'action' => 'approve',
                'reviewer_note' => 'Disetujui setelah verifikasi log office.',
            ]);

        $response->assertRedirect(route('admin.attendance.corrections.show', $correction->id));

        $attendance->refresh();
        $correction->refresh();

        $this->assertSame('2026-03-28 09:05:00', $attendance->check_in_at?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-28 17:45:00', $attendance->check_out_at?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceCheckInStatus::ON_TIME, $attendance->check_in_status);
        $this->assertSame(AttendanceCheckOutStatus::OVERTIME, $attendance->check_out_status);
        $this->assertSame(0, $attendance->late_minutes);
        $this->assertSame(0, $attendance->early_leave_minutes);
        $this->assertGreaterThan(0, $attendance->overtime_minutes);
        $this->assertSame(AttendanceRecordStatus::COMPLETE, $attendance->record_status);
        $this->assertFalse((bool) $attendance->is_suspicious);
        $this->assertNull($attendance->suspicious_reason);

        $this->assertSame('approved', $correction->status);
        $this->assertSame($admin->id, $correction->reviewed_by);
        $this->assertSame('Disetujui setelah verifikasi log office.', $correction->reviewer_note);
        $this->assertNotNull($correction->reviewed_at);
    }

    public function test_admin_reject_keeps_attendance_unchanged_and_employee_can_see_reviewer_note(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $admin = $this->createEmployee(['name' => 'Admin Reviewer'], $office);
        $this->assignRole($employee, Roles::Employee->value);
        $this->assignRole($admin, Roles::Admin->value);

        $attendance = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-27',
            'check_in_at' => Carbon::parse('2026-03-27 09:20:00', 'Asia/Jakarta'),
            'check_out_at' => Carbon::parse('2026-03-27 17:00:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::LATE,
            'check_out_status' => AttendanceCheckOutStatus::NORMAL,
            'record_status' => AttendanceRecordStatus::COMPLETE,
            'late_minutes' => 5,
        ]);

        $correction = AttendanceCorrection::query()->create([
            'user_id' => $employee->id,
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-27 08:55:00', 'Asia/Jakarta'),
            'reason' => 'Meminta koreksi check-in.',
            'original_attendance_snapshot' => [
                'work_date' => '2026-03-27',
                'check_in_at' => '2026-03-27T09:20:00+07:00',
            ],
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::Admin->value])
            ->post(route('admin.attendance.corrections.review', $correction->id), [
                'action' => 'reject',
                'reviewer_note' => 'Data gate office menunjukkan jam masuk tetap 09:20.',
            ]);

        $response->assertRedirect(route('admin.attendance.corrections.show', $correction->id));

        $attendance->refresh();
        $correction->refresh();

        $this->assertSame('2026-03-27 09:20:00', $attendance->check_in_at?->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'));
        $this->assertSame(AttendanceCheckInStatus::LATE, $attendance->check_in_status);
        $this->assertSame(5, $attendance->late_minutes);
        $this->assertSame('rejected', $correction->status);
        $this->assertSame('Data gate office menunjukkan jam masuk tetap 09:20.', $correction->reviewer_note);

        $employeeResponse = $this->actingAs($employee)
            ->withSession(['active_role' => Roles::Employee->value])
            ->get(route('employee.attendance.show', $attendance->id));

        $employeeResponse->assertOk()
            ->assertSee('Rejected', false)
            ->assertSee('Reviewer note: Data gate office menunjukkan jam masuk tetap 09:20.', false);
    }
}

