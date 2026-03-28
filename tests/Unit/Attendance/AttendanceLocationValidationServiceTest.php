<?php

namespace Tests\Unit\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Exceptions\Attendance\InvalidAttendanceLocationException;
use App\Exceptions\Attendance\LocationOutOfRangeException;
use App\Services\Attendance\AttendanceLocationValidationService;
use Tests\TestCase;

class AttendanceLocationValidationServiceTest extends TestCase
{
    public function test_it_marks_missing_accuracy_as_suspicious_instead_of_hard_rejecting(): void
    {
        $service = new AttendanceLocationValidationService();

        $result = $service->validateForPolicy(
            AttendancePolicyData::fromArray([
                'office_location_id' => 1,
                'work_start_time' => '09:00:00',
                'work_end_time' => '17:00:00',
                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'allowed_radius_meter' => 100,
                'timezone' => 'Asia/Jakarta',
            ]),
            latitude: -6.2000000,
            longitude: 106.8166667,
            accuracyMeter: null,
        );

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->isSuspicious);
        $this->assertSame('MISSING_ACCURACY', $result->reason);
    }

    public function test_it_rejects_invalid_user_coordinates_early(): void
    {
        $service = new AttendanceLocationValidationService();

        $this->expectException(InvalidAttendanceLocationException::class);

        $service->validateForPolicy(
            AttendancePolicyData::fromArray([
                'office_location_id' => 1,
                'work_start_time' => '09:00:00',
                'work_end_time' => '17:00:00',
                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'allowed_radius_meter' => 100,
                'timezone' => 'Asia/Jakarta',
            ]),
            latitude: 120.0,
            longitude: 106.8166667,
            accuracyMeter: 10.0,
        );
    }

    public function test_it_rejects_location_far_outside_allowed_radius(): void
    {
        $service = new AttendanceLocationValidationService();

        $this->expectException(LocationOutOfRangeException::class);

        $service->validateForPolicy(
            AttendancePolicyData::fromArray([
                'office_location_id' => 1,
                'work_start_time' => '09:00:00',
                'work_end_time' => '17:00:00',
                'office_latitude' => -6.2000000,
                'office_longitude' => 106.8166667,
                'late_tolerance_minutes' => 15,
                'qr_rotation_seconds' => 30,
                'min_location_accuracy_meter' => 50,
                'allowed_radius_meter' => 100,
                'timezone' => 'Asia/Jakarta',
            ]),
            latitude: -6.2100000,
            longitude: 106.8166667,
            accuracyMeter: 5.0,
        );
    }
}
