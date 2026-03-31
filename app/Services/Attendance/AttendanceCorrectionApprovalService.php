<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionApprovalService
{
    public function __construct(
        private readonly AttendancePolicyService $attendancePolicyService,
    ) {}

    public function approve(AttendanceCorrection $correction, int $reviewerId, ?string $reviewerNote = null): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $reviewerId, $reviewerNote): AttendanceCorrection {
            /** @var AttendanceCorrection $lockedCorrection */
            $lockedCorrection = AttendanceCorrection::query()
                ->with(['attendance.user'])
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->assertPending($lockedCorrection);

            $attendance = Attendance::query()
                ->with('user:id,office_location_id')
                ->lockForUpdate()
                ->findOrFail($lockedCorrection->attendance_id);

            $payload = $this->buildAttendancePayload(
                attendance: $attendance,
                requestedCheckInAt: $lockedCorrection->requested_check_in_time,
                requestedCheckOutAt: $lockedCorrection->requested_check_out_time,
            );

            $attendance->fill($payload);
            $attendance->save();

            $lockedCorrection->forceFill([
                'status' => 'approved',
                'reviewer_note' => $reviewerNote,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now('Asia/Jakarta'),
            ])->save();

            return $lockedCorrection->fresh(['attendance', 'reviewer']);
        });
    }

    public function reject(AttendanceCorrection $correction, int $reviewerId, string $reviewerNote): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $reviewerId, $reviewerNote): AttendanceCorrection {
            /** @var AttendanceCorrection $lockedCorrection */
            $lockedCorrection = AttendanceCorrection::query()
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->assertPending($lockedCorrection);

            $lockedCorrection->forceFill([
                'status' => 'rejected',
                'reviewer_note' => $reviewerNote,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now('Asia/Jakarta'),
            ])->save();

            return $lockedCorrection->fresh(['attendance', 'reviewer']);
        });
    }

    private function assertPending(AttendanceCorrection $correction): void
    {
        if ($correction->status !== 'pending') {
            throw new DomainException('Correction request has already been reviewed.');
        }
    }

    private function buildAttendancePayload(
        Attendance $attendance,
        ?Carbon $requestedCheckInAt,
        ?Carbon $requestedCheckOutAt,
    ): array {
        $finalCheckInAt = $requestedCheckInAt ?? $attendance->check_in_at;
        $finalCheckOutAt = $requestedCheckOutAt ?? $attendance->check_out_at;

        if ($finalCheckOutAt !== null && $finalCheckInAt === null) {
            throw new DomainException('Correction cannot be approved because check-out would exist without check-in.');
        }

        if ($finalCheckInAt !== null && $finalCheckOutAt !== null && $finalCheckOutAt->lt($finalCheckInAt)) {
            throw new DomainException('Correction cannot be approved because corrected check-out is earlier than corrected check-in.');
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
