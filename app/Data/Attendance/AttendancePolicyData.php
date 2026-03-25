<?php

namespace App\Data\Attendance;

class AttendancePolicyData
{
    public function __construct(
        public readonly int $officeLocationId,
        public readonly string $workStartTime,
        public readonly string $workEndTime,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $lateToleranceMinutes,
        public readonly int $qrRotationSeconds,
        public readonly int $minLocationAccuracyMeter,
        public readonly int $allowedRadiusMeter,
        public readonly string $timezone = 'Asia/Jakarta',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            officeLocationId: (int) $data['office_location_id'],
            workStartTime: (string) $data['work_start_time'],
            workEndTime: (string) $data['work_end_time'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            lateToleranceMinutes: (int) $data['late_tolerance_minutes'],
            qrRotationSeconds: (int) $data['qr_rotation_seconds'],
            minLocationAccuracyMeter: (int) $data['min_location_accuracy_meter'],
            allowedRadiusMeter: (int) $data['allowed_radius_meter'],
            timezone: (string) ($data['timezone'] ?? 'Asia/Jakarta'),
        );
    }

    public function toArray(): array
    {
        return [
            'office_location_id' => $this->officeLocationId,
            'work_start_time' => $this->workStartTime,
            'work_end_time' => $this->workEndTime,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'late_tolerance_minutes' => $this->lateToleranceMinutes,
            'qr_rotation_seconds' => $this->qrRotationSeconds,
            'min_location_accuracy_meter' => $this->minLocationAccuracyMeter,
            'allowed_radius_meter' => $this->allowedRadiusMeter,
            'timezone' => $this->timezone,
        ];
    }

    public function latestAllowedCheckInTime(): string
    {
        return $this->workStartTime;
    }
}
