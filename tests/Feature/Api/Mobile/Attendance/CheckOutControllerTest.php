<?php

namespace Tests\Feature\Api\Mobile\Attendance;

use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Exceptions\Attendance\AttendanceNotAllowedException;
use App\Services\Attendance\AttendanceCheckOutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class CheckOutControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_returns_success_payload_from_the_check_out_service(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);
        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-27',
            'check_in_at' => '2026-03-27 08:55:00',
            'check_out_at' => '2026-03-27 17:05:00',
            'check_out_status' => AttendanceCheckOutStatus::OVERTIME,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 5,
            'record_status' => AttendanceRecordStatus::COMPLETE,
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ]);

        /** @var AttendanceCheckOutService&MockObject $service */
        $service = $this->createMock(AttendanceCheckOutService::class);
        $service->expects($this->once())
            ->method('checkOut')
            ->willReturn($attendance);

        $this->app->instance(AttendanceCheckOutService::class, $service);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/attendance/check-out', [
            'qr_token' => 'QR-001',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy_meter' => 4.5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Check-out berhasil direkam.',
                'data' => [
                    'id' => $attendance->id,
                    'work_date' => '2026-03-27',
                    'check_out_status' => 'overtime',
                    'early_leave_minutes' => 0,
                    'overtime_minutes' => 5,
                    'record_status' => 'complete',
                ],
            ]);
    }

    public function test_it_returns_structured_attendance_errors_for_mobile_api_requests(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        /** @var AttendanceCheckOutService&MockObject $service */
        $service = $this->createMock(AttendanceCheckOutService::class);
        $service->expects($this->once())
            ->method('checkOut')
            ->willThrowException(new AttendanceNotAllowedException('Check-out ditolak.', [
                'reason' => 'CHECK_IN_NOT_FOUND',
            ]));

        $this->app->instance(AttendanceCheckOutService::class, $service);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/attendance/check-out', [
            'qr_token' => 'QR-001',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy_meter' => 4.5,
        ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Check-out ditolak.',
                'code' => 'ATTENDANCE_NOT_ALLOWED',
                'context' => [
                    'reason' => 'CHECK_IN_NOT_FOUND',
                ],
            ]);
    }
}
