<?php

namespace Tests\Unit\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Services\Attendance\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceHistoryServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_keeps_employee_history_scoped_to_the_method_user_id(): void
    {
        $service = new AttendanceHistoryService();
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);
        $otherEmployee = $this->createEmployee([], $office);

        $employeeAttendance = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-21',
        ]);

        $this->createAttendance($otherEmployee, $office, null, [
            'work_date' => '2026-03-22',
        ]);

        $filter = AttendanceHistoryFilterData::fromArray([
            'user_id' => $otherEmployee->id,
            'per_page' => 50,
        ]);

        $paginator = $service->getEmployeeHistory($employee->id, $filter);
        $items = $paginator->items();

        $this->assertCount(1, $items);
        $this->assertSame($employeeAttendance->id, $items[0]->id);
        $this->assertSame($employee->id, $items[0]->user_id);
    }

    public function test_it_falls_back_to_work_date_sort_when_sort_by_is_not_whitelisted(): void
    {
        $service = new AttendanceHistoryService();
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);

        $older = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-01',
        ]);

        $newer = $this->createAttendance($employee, $office, null, [
            'work_date' => '2026-03-05',
        ]);

        $filter = AttendanceHistoryFilterData::fromArray([
            'sort_by' => 'drop_table',
            'sort_direction' => 'asc',
            'per_page' => 50,
        ]);

        $paginator = $service->getEmployeeHistory($employee->id, $filter);

        $this->assertSame(
            [$older->id, $newer->id],
            collect($paginator->items())->pluck('id')->all()
        );
    }

    public function test_it_filters_only_suspicious_records_when_requested(): void
    {
        $service = new AttendanceHistoryService();
        $office = $this->createOfficeLocation();
        $employee = $this->createEmployee([], $office);

        $this->createAttendance($employee, $office, null, [
            'work_date' => Carbon::parse('2026-03-10')->toDateString(),
            'is_suspicious' => false,
        ]);

        $suspicious = $this->createAttendance($employee, $office, null, [
            'work_date' => Carbon::parse('2026-03-11')->toDateString(),
            'is_suspicious' => true,
            'suspicious_reason' => 'LOW_ACCURACY',
        ]);

        $filter = AttendanceHistoryFilterData::fromArray([
            'is_suspicious' => true,
            'per_page' => 50,
        ]);

        $paginator = $service->getEmployeeHistory($employee->id, $filter);

        $this->assertSame([$suspicious->id], collect($paginator->items())->pluck('id')->all());
    }
}
