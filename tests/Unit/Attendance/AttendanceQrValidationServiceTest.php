<?php

namespace Tests\Unit\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Exceptions\Attendance\InvalidQrTokenException;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Attendance\AttendanceQrValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class AttendanceQrValidationServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    public function test_it_accepts_an_active_non_expired_qr_token_for_the_matching_office(): void
    {
        $office = $this->createOfficeLocation();
        $token = $this->createAttendanceQrToken($office, [
            'token' => 'VALID-QR-001',
            'expired_at' => Carbon::parse('2026-03-27 10:00:00', 'Asia/Jakarta'),
        ]);

        /** @var AttendancePolicyService&MockObject $policyService */
        $policyService = $this->createMock(AttendancePolicyService::class);
        $policyService->expects($this->once())
            ->method('getPolicyForUser')
            ->willReturn(AttendancePolicyData::fromArray([
                'office_location_id' => $office->id,
                'work_start_time' => '09:00:00',
                'work_end_time' => '17:00:00',
                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'allowed_radius_meter' => 100,
                'timezone' => 'Asia/Jakarta',
            ]));

        $service = new AttendanceQrValidationService($policyService);

        $validatedToken = $service->validateForOffice(
            userId: 99,
            expectedOfficeLocationId: $office->id,
            rawToken: '  VALID-QR-001  ',
            now: Carbon::parse('2026-03-27 09:00:00', 'Asia/Jakarta'),
        );

        $this->assertTrue($validatedToken->is($token));
    }

    public function test_it_rejects_blank_token_input(): void
    {
        /** @var AttendancePolicyService&MockObject $policyService */
        $policyService = $this->createMock(AttendancePolicyService::class);
        $policyService->expects($this->never())->method('getPolicyForUser');

        $service = new AttendanceQrValidationService($policyService);

        $this->expectException(InvalidQrTokenException::class);

        $service->validateForOffice(
            userId: 99,
            expectedOfficeLocationId: 1,
            rawToken: '   ',
            now: Carbon::parse('2026-03-27 09:00:00', 'Asia/Jakarta'),
        );
    }
}
