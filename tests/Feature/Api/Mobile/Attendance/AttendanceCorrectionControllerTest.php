<?php

namespace Tests\Feature\Api\Mobile\Attendance;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceCorrectionControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_employee_can_submit_correction_from_mobile_api(): void
    {
        $office = $this->createOfficeLocation();
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
            'overtime_minutes' => 10,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->postJson('/api/mobile/attendance/corrections', [
            'attendance_id' => $attendance->id,
            'requested_check_in_time' => '2026-03-30T09:05:00+07:00',
            'requested_check_out_time' => '2026-03-30T17:30:00+07:00',
            'reason' => 'Koreksi absensi dari mobile.',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Pengajuan koreksi absensi berhasil dikirim.',
                'data' => [
                    'attendance_id' => $attendance->id,
                    'status' => 'pending',
                    'reason' => 'Koreksi absensi dari mobile.',
                ],
            ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_id' => $attendance->id,
            'user_id' => $employee->id,
            'status' => 'pending',
        ]);
    }

    public function test_employee_correction_list_only_returns_own_records(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee(['name' => 'Employee Mobile'], $office);
        $otherEmployee = $this->createEmployee(['name' => 'Other Employee'], $office);
        $this->assignRole($employee, Roles::Employee->value);
        $this->assignRole($otherEmployee, Roles::Employee->value);

        $ownAttendance = $this->createAttendance($employee, $office);
        $otherAttendance = $this->createAttendance($otherEmployee, $office, null, [
            'work_date' => '2026-03-29',
        ]);

        AttendanceCorrection::query()->create([
            'user_id' => $employee->id,
            'attendance_id' => $ownAttendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-30 09:01:00', 'Asia/Jakarta'),
            'reason' => 'Own correction',
            'original_attendance_snapshot' => ['work_date' => '2026-03-30'],
            'status' => 'pending',
        ]);

        AttendanceCorrection::query()->create([
            'user_id' => $otherEmployee->id,
            'attendance_id' => $otherAttendance->id,
            'requested_check_in_time' => Carbon::parse('2026-03-29 09:01:00', 'Asia/Jakarta'),
            'reason' => 'Other correction',
            'original_attendance_snapshot' => ['work_date' => '2026-03-29'],
            'status' => 'pending',
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/mobile/attendance/corrections');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertSee('Own correction')
            ->assertDontSee('Other correction');
    }
}
