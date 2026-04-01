<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Carbon\Carbon;

class AttendanceCorrectionApplyService
{
    public function __construct(
        private readonly AttendancePolicyService $attendancePolicyService,
        private readonly AttendanceCorrectionSnapshotService $attendanceCorrectionSnapshotService,
        private readonly AttendanceLogService $attendanceLogService,
    ) {}

    public function apply(Attendance $attendance, AttendanceCorrection $correction, User $actor): array
    {
        $payload = $this->buildAttendancePayload(
            attendance: $attendance,
            requestedCheckInAt: $correction->requested_check_in_time,
            requestedCheckOutAt: $correction->requested_check_out_time,
        );

        $attendance->fill($payload);
        $attendance->save();

        $resultingSnapshot = $this->attendanceCorrectionSnapshotService->makeSnapshot($attendance->fresh());

        $this->attendanceLogService->logCorrectionApplied(
            attendanceId: $attendance->id,
            userId: $attendance->user_id,
            context: [
                'correction_id' => $correction->id,
                'applied_by' => $actor->id,
                'reviewed_by' => $correction->reviewed_by,
                'requested_check_in_time' => $correction->requested_check_in_time?->toIso8601String(),
                'requested_check_out_time' => $correction->requested_check_out_time?->toIso8601String(),
                'resulting_record_status' => $attendance->record_status?->value,
            ],
            occurredAt: now('Asia/Jakarta'),
        );

        return [
            'attendance' => $attendance->fresh(),
            'resulting_snapshot' => $resultingSnapshot,
        ];
    }

    private function buildAttendancePayload(
        Attendance $attendance,
        ?Carbon $requestedCheckInAt,
        ?Carbon $requestedCheckOutAt,
    ): array {
        $finalCheckInAt = $requestedCheckInAt ?? $attendance->check_in_at;
        $finalCheckOutAt = $requestedCheckOutAt ?? $attendance->check_out_at;

        if ($finalCheckOutAt !== null && $finalCheckInAt === null) {
            throw new \DomainException('Correction cannot be approved because check-out would exist without check-in.');
        }

        if ($finalCheckInAt !== null && $finalCheckOutAt !== null && $finalCheckOutAt->lt($finalCheckInAt)) {
            throw new \DomainException('Correction cannot be approved because corrected check-out is earlier than corrected check-in.');
        }

        $policy = $this->attendancePolicyService->getPolicyForUser(
            $attendance->user_id,
            $attendance->work_date?->copy()?->startOfDay() ?? now('Asia/Jakarta')->startOfDay(),
        );

        $checkInStatus = $finalCheckInAt !== null
            ? $this->attendancePolicyService->determineCheckInStatus($policy, $finalCheckInAt)
            : null;

        $checkOutStatus = $finalCheckOutAt !== null
            ? $this->attendancePolicyService->determineCheckOutStatus($policy, $finalCheckOutAt)
            : null;

        $recordStatus = match (true) {
            $finalCheckInAt !== null && $finalCheckOutAt !== null => AttendanceRecordStatus::COMPLETE,
            $finalCheckInAt !== null => AttendanceRecordStatus::ONGOING,
            default => AttendanceRecordStatus::INCOMPLETE,
        };

        return [
            'check_in_at' => $finalCheckInAt,
            'check_in_status' => $checkInStatus['status'] ?? AttendanceCheckInStatus::NONE,
            'late_minutes' => (int) ($checkInStatus['late_minutes'] ?? 0),
            'check_out_at' => $finalCheckOutAt,
            'check_out_status' => $checkOutStatus['status'] ?? AttendanceCheckOutStatus::NONE,
            'early_leave_minutes' => (int) ($checkOutStatus['early_leave_minutes'] ?? 0),
            'overtime_minutes' => (int) ($checkOutStatus['overtime_minutes'] ?? 0),
            'record_status' => $recordStatus,
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ];
    }
}

