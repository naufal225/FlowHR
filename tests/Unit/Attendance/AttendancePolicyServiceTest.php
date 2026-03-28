<?php

namespace Tests\Unit\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Services\Attendance\AttendancePolicyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendancePolicyServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_uses_the_assigned_office_timezone_when_building_user_policy(): void
    {
        $service = new AttendancePolicyService();
        $office = $this->createOfficeLocation([
            'timezone' => 'UTC',
        ]);

        $this->createAttendanceSetting($office, [
            'work_start_time' => '08:00:00',
            'work_end_time' => '16:00:00',
        ]);

        $employee = $this->createEmployee([], $office);

        $policy = $service->getPolicyForUser($employee->id, Carbon::parse('2026-03-27 07:30:00', 'UTC'));

        $this->assertSame('UTC', $policy->timezone);
    }

    public function test_it_resolves_overnight_schedule_into_the_next_calendar_day(): void
    {
        $service = new AttendancePolicyService();

        $schedule = $service->resolveWorkSchedule(
            AttendancePolicyData::fromArray([
                'office_location_id' => 1,
                'work_start_time' => '22:00:00',
                'work_end_time' => '06:00:00',
                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'allowed_radius_meter' => 100,
                'timezone' => 'Asia/Jakarta',
            ]),
            Carbon::parse('2026-03-27 23:00:00', 'Asia/Jakarta'),
        );

        $this->assertSame('2026-03-27 22:00:00', $schedule['work_start_at']->toDateTimeString());
        $this->assertSame('2026-03-28 06:00:00', $schedule['work_end_at']->toDateTimeString());
    }
}
