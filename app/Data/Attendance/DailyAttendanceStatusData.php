<?php

namespace App\Data\Attendance;

use Carbon\Carbon;

class DailyAttendanceStatusData
{
    public function __construct(
        public readonly int $userId,
        public readonly Carbon $date,
        public readonly string $status,
        public readonly ?string $label = null,
        public readonly ?int $attendanceId = null,
        public readonly ?Carbon $checkInAt = null,
        public readonly ?Carbon $checkOutAt = null,
        public readonly ?bool $isLate = null,
        public readonly ?bool $isEarlyLeave = null,
        public readonly ?bool $isSuspicious = null,
        public readonly ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            date: $data['date'] instanceof Carbon
                ? $data['date']
                : Carbon::parse($data['date']),
            status: (string) $data['status'],
            label: isset($data['label']) ? (string) $data['label'] : null,
            attendanceId: isset($data['attendance_id']) && $data['attendance_id'] !== '' ? (int) $data['attendance_id'] : null,
            checkInAt: !empty($data['check_in_at'])
                ? ($data['check_in_at'] instanceof Carbon ? $data['check_in_at'] : Carbon::parse($data['check_in_at']))
                : null,
            checkOutAt: !empty($data['check_out_at'])
                ? ($data['check_out_at'] instanceof Carbon ? $data['check_out_at'] : Carbon::parse($data['check_out_at']))
                : null,
            isLate: array_key_exists('is_late', $data) ? (bool) $data['is_late'] : null,
            isEarlyLeave: array_key_exists('is_early_leave', $data) ? (bool) $data['is_early_leave'] : null,
            isSuspicious: array_key_exists('is_suspicious', $data) ? (bool) $data['is_suspicious'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'date' => $this->date->toDateString(),
            'status' => $this->status,
            'label' => $this->label,
            'attendance_id' => $this->attendanceId,
            'check_in_at' => $this->checkInAt?->toDateTimeString(),
            'check_out_at' => $this->checkOutAt?->toDateTimeString(),
            'is_late' => $this->isLate,
            'is_early_leave' => $this->isEarlyLeave,
            'is_suspicious' => $this->isSuspicious,
            'reason' => $this->reason,
        ];
    }

    public function isPresent(): bool
    {
        return in_array($this->status, [
            'checked_in',
            'checked_out',
            'complete',
            'present',
        ], true);
    }
}
