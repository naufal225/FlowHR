<?php

namespace Tests\Unit\Attendance;

use App\Enums\AttendanceLogActionType;
use App\Services\Attendance\AttendanceDetailService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceDetailServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_returns_employee_detail_with_logs_sorted_by_occurrence_time(): void
    {
        $service = new AttendanceDetailService();
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $token = $this->createAttendanceQrToken($office);
        $attendance = $this->createAttendance($employee, $office, $token, [
            'work_date' => '2026-03-25',
        ]);

        $laterLog = $this->createAttendanceLog($employee, $attendance, [
            'action_type' => AttendanceLogActionType::CHECK_OUT_SUCCESS,
            'occurred_at' => Carbon::parse('2026-03-25 17:05:00', 'Asia/Jakarta'),
        ]);

        $earlierLog = $this->createAttendanceLog($employee, $attendance, [
            'action_type' => AttendanceLogActionType::CHECK_IN_SUCCESS,
            'occurred_at' => Carbon::parse('2026-03-25 08:55:00', 'Asia/Jakarta'),
        ]);

        $detail = $service->getEmployeeAttendanceDetail($employee->id, $attendance->id);

        $this->assertTrue($detail->relationLoaded('officeLocation'));
        $this->assertTrue($detail->relationLoaded('attendanceQrToken'));
        $this->assertTrue($detail->relationLoaded('logs'));
        $this->assertSame([$earlierLog->id, $laterLog->id], $detail->logs->pluck('id')->all());
    }

    public function test_it_does_not_allow_employee_to_load_other_users_attendance_detail(): void
    {
        $service = new AttendanceDetailService();
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $otherEmployee = $this->createEmployee([], $office);
        $attendance = $this->createAttendance($otherEmployee, $office, null, [
            'work_date' => '2026-03-26',
        ]);

        $this->expectException(ModelNotFoundException::class);

        $service->getEmployeeAttendanceDetail($employee->id, $attendance->id);
    }
}
