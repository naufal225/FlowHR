<?php

namespace Tests\Unit\Dashboard;

use App\Models\Holiday;
use App\Models\Leave;
use App\Services\Dashboard\DashboardLeaveCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class DashboardLeaveCalendarServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_includes_approved_leave_dates_in_calendar_payload(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $this->createApprovedLeave($employee->id, '2026-04-10', '2026-04-10');

        $payload = app(DashboardLeaveCalendarService::class)->build(Leave::query(), 2026);

        $this->assertArrayHasKey('2026-04-10', $payload['approved_by_date']);
        $this->assertCount(1, $payload['approved_by_date']['2026-04-10']);
        $this->assertSame($employee->name, $payload['approved_by_date']['2026-04-10'][0]['employee']);
        $this->assertSame([], $payload['holiday_dates']);
        $this->assertSame([], $payload['holidays_by_date']);
    }

    public function test_it_includes_holiday_dates_even_when_no_approved_leave_exists(): void
    {
        Holiday::query()->create([
            'name' => 'Nyepi',
            'start_from' => '2026-03-25',
            'end_at' => '2026-03-25',
        ]);

        $payload = app(DashboardLeaveCalendarService::class)->build(Leave::query(), 2026);

        $this->assertSame([], $payload['approved_by_date']);
        $this->assertContains('2026-03-25', $payload['holiday_dates']);
        $this->assertArrayHasKey('2026-03-25', $payload['holidays_by_date']);
        $this->assertSame('Nyepi', $payload['holidays_by_date']['2026-03-25'][0]['name']);
    }

    public function test_it_keeps_overlap_between_approved_leave_and_holiday_as_two_datasets(): void
    {
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $this->createApprovedLeave($employee->id, '2026-05-01', '2026-05-01');

        Holiday::query()->create([
            'name' => 'Labor Day',
            'start_from' => '2026-05-01',
            'end_at' => '2026-05-01',
        ]);

        $payload = app(DashboardLeaveCalendarService::class)->build(Leave::query(), 2026);

        $this->assertArrayHasKey('2026-05-01', $payload['approved_by_date']);
        $this->assertContains('2026-05-01', $payload['holiday_dates']);
        $this->assertArrayHasKey('2026-05-01', $payload['holidays_by_date']);
    }

    public function test_it_expands_multi_day_holiday_to_each_date_key(): void
    {
        Holiday::query()->create([
            'name' => 'Company Shutdown',
            'start_from' => '2026-12-24',
            'end_at' => '2026-12-26',
        ]);

        $payload = app(DashboardLeaveCalendarService::class)->build(Leave::query(), 2026);

        $this->assertArrayHasKey('2026-12-24', $payload['holidays_by_date']);
        $this->assertArrayHasKey('2026-12-25', $payload['holidays_by_date']);
        $this->assertArrayHasKey('2026-12-26', $payload['holidays_by_date']);
        $this->assertContains('2026-12-24', $payload['holiday_dates']);
        $this->assertContains('2026-12-25', $payload['holiday_dates']);
        $this->assertContains('2026-12-26', $payload['holiday_dates']);
    }

    public function test_it_respects_for_leader_scope_for_approver_calendar_query(): void
    {
        $office = $this->createOfficeLocation();
        $divisionA = $this->createDivision();
        $divisionB = $this->createDivision();

        $leaderA = $this->createEmployee([], $office, $divisionA);
        $leaderB = $this->createEmployee([], $office, $divisionB);
        $divisionA->update(['leader_id' => $leaderA->id]);
        $divisionB->update(['leader_id' => $leaderB->id]);

        $employeeA = $this->createEmployee([], $office, $divisionA);
        $employeeB = $this->createEmployee([], $office, $divisionB);
        $this->createApprovedLeave($employeeA->id, '2026-06-10', '2026-06-10');
        $this->createApprovedLeave($employeeB->id, '2026-06-10', '2026-06-10');

        $payload = app(DashboardLeaveCalendarService::class)
            ->build(Leave::query()->forLeader($leaderA->id), 2026);

        $employeesOnDate = $payload['approved_by_date']['2026-06-10'] ?? [];
        $employeeNames = array_map(
            static fn (array $item): string => (string) ($item['employee'] ?? ''),
            $employeesOnDate
        );

        $this->assertContains($employeeA->name, $employeeNames);
        $this->assertNotContains($employeeB->name, $employeeNames);
    }

    private function createApprovedLeave(int $employeeId, string $dateStart, string $dateEnd): Leave
    {
        return Leave::query()->create([
            'employee_id' => $employeeId,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'reason' => 'Annual leave for testing.',
            'status_1' => 'approved',
        ]);
    }
}
