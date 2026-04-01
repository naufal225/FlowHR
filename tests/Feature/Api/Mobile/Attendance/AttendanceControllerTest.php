<?php

namespace Tests\Feature\Api\Mobile\Attendance;

use App\Data\Attendance\DailyAttendanceStatusData;
use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\Attendance;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceDetailService;
use App\Services\Attendance\AttendanceHistoryService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_returns_today_status_for_the_authenticated_mobile_user(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        /** @var AttendanceDailyStatusResolverService&MockObject $resolver */
        $resolver = $this->createMock(AttendanceDailyStatusResolverService::class);
        $resolver->expects($this->once())
            ->method('resolveForUser')
            ->willReturn(DailyAttendanceStatusData::fromArray([
                'user_id' => $user->id,
                'date' => Carbon::parse('2026-03-27', 'Asia/Jakarta'),
                'status' => 'checked_in',
                'label' => 'Sudah check-in',
                'attendance_id' => 1001,
                'check_in_at' => Carbon::parse('2026-03-27 08:59:00', 'Asia/Jakarta'),
                'is_late' => false,
                'is_early_leave' => false,
                'is_suspicious' => false,
                'reason' => null,
            ]));

        $this->app->instance(AttendanceDailyStatusResolverService::class, $resolver);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/attendance/today-status?date=2026-03-27');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Attendance status for the selected date was retrieved successfully.',
                'data' => [
                    'date' => '2026-03-27',
                    'status' => 'checked_in',
                    'label' => 'Sudah check-in',
                    'attendance_id' => 1001,
                    'is_late' => false,
                    'is_early_leave' => false,
                    'is_suspicious' => false,
                    'has_pending_correction' => false,
                ],
            ]);
    }

    public function test_it_returns_paginated_attendance_history_payload(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $attendances = [
            new Attendance([
                'id' => 1,
                'work_date' => '2026-03-25',
                'check_in_at' => '2026-03-25 09:00:00',
                'check_out_at' => '2026-03-25 17:00:00',
                'check_in_status' => AttendanceCheckInStatus::ON_TIME,
                'check_out_status' => AttendanceCheckOutStatus::NORMAL,
                'record_status' => AttendanceRecordStatus::COMPLETE,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
                'is_suspicious' => false,
            ]),
            new Attendance([
                'id' => 2,
                'work_date' => '2026-03-26',
                'check_in_at' => '2026-03-26 09:20:00',
                'check_out_at' => null,
                'check_in_status' => AttendanceCheckInStatus::LATE,
                'check_out_status' => AttendanceCheckOutStatus::NONE,
                'record_status' => AttendanceRecordStatus::ONGOING,
                'late_minutes' => 20,
                'early_leave_minutes' => 0,
                'overtime_minutes' => 0,
                'is_suspicious' => true,
            ]),
        ];

        foreach ($attendances as $attendance) {
            $attendance->setRelation('officeLocation', $office);
        }

        /** @var AttendanceHistoryService&MockObject $historyService */
        $historyService = $this->createMock(AttendanceHistoryService::class);
        $historyService->expects($this->once())
            ->method('getEmployeeHistory')
            ->willReturn(new LengthAwarePaginator($attendances, 2, 15, 1));

        $this->app->instance(AttendanceHistoryService::class, $historyService);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/attendance/history?per_page=15');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Attendance history retrieved successfully.',
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 2,
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_it_validates_history_query_parameters(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/attendance/history?per_page=1000&sort_direction=up');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonValidationErrors(['per_page', 'sort_direction']);
    }

    public function test_detail_route_only_accepts_numeric_attendance_ids(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/attendance/history/not-a-number')->assertNotFound();
    }

    public function test_it_should_return_a_not_found_response_when_attendance_detail_is_missing(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        /** @var AttendanceDetailService&MockObject $detailService */
        $detailService = $this->createMock(AttendanceDetailService::class);
        $detailService->expects($this->once())
            ->method('getEmployeeAttendanceDetail')
            ->willThrowException(new ModelNotFoundException());

        $this->app->instance(AttendanceDetailService::class, $detailService);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/attendance/history/999999');

        $response->assertNotFound();
    }
}
