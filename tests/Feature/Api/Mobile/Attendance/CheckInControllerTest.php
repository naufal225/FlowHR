<?php

namespace Tests\Feature\Api\Mobile\Attendance;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Services\Attendance\AttendanceCheckInService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class CheckInControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_check_in_route_requires_sanctum_authentication(): void
    {
        $response = $this->postJson('/api/mobile/attendance/check-in', [
            'qr_token' => 'QR-001',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy_meter' => 5,
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'code' => 'UNAUTHORIZED',
            ]);
    }

    public function test_it_returns_success_payload_from_the_check_in_service(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);
        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-27',
            'check_in_at' => '2026-03-27 08:58:00',
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'late_minutes' => 0,
            'record_status' => AttendanceRecordStatus::ONGOING,
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ]);

        /** @var AttendanceCheckInService&MockObject $service */
        $service = $this->createMock(AttendanceCheckInService::class);
        $service->expects($this->once())
            ->method('checkIn')
            ->willReturn($attendance);

        $this->app->instance(AttendanceCheckInService::class, $service);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/attendance/check-in', [
            'qr_token' => 'QR-001',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy_meter' => 4.5,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Check-in berhasil direkam.',
                'data' => [
                    'id' => $attendance->id,
                    'work_date' => '2026-03-27',
                    'check_in_status' => 'on_time',
                    'late_minutes' => 0,
                    'record_status' => 'ongoing',
                    'is_suspicious' => false,
                ],
            ]);
    }

    public function test_it_should_accept_nullable_accuracy_meter_without_returning_a_server_error(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);
        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-27',
            'check_in_at' => '2026-03-27 08:59:00',
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'late_minutes' => 0,
            'record_status' => AttendanceRecordStatus::ONGOING,
            'is_suspicious' => false,
        ]);

        /** @var AttendanceCheckInService&MockObject $service */
        $service = $this->createMock(AttendanceCheckInService::class);
        $service->expects($this->once())
            ->method('checkIn')
            ->willReturn($attendance);

        $this->app->instance(AttendanceCheckInService::class, $service);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/mobile/attendance/check-in', [
            'qr_token' => 'QR-001',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
        ]);

        $response->assertOk();
    }
}
