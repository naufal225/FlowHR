<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\Roles;
use App\Exceptions\Attendance\CorrectionAlreadyReviewedException;
use App\Exceptions\Attendance\CorrectionNotReviewableByCurrentUserException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionReviewService
{
    public function __construct(
        private readonly AttendanceCorrectionApplyService $attendanceCorrectionApplyService,
        private readonly AttendanceLogService $attendanceLogService,
    ) {}

    public function approve(AttendanceCorrection $correction, User $reviewer, ?string $reviewerNote = null): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $reviewer, $reviewerNote): AttendanceCorrection {
            $lockedCorrection = AttendanceCorrection::query()
                ->with(['attendance.user.roles', 'attendance.user.division'])
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->assertPending($lockedCorrection);
            $this->assertCanReview($lockedCorrection, $reviewer);

            $attendance = Attendance::query()
                ->lockForUpdate()
                ->findOrFail($lockedCorrection->attendance_id);

            $lockedCorrection->forceFill([
                'status' => 'approved',
                'reviewer_note' => $reviewerNote,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now('Asia/Jakarta'),
                'applied_by' => $reviewer->id,
                'applied_at' => now('Asia/Jakarta'),
            ])->save();

            $result = $this->attendanceCorrectionApplyService->apply($attendance, $lockedCorrection, $reviewer);

            $lockedCorrection->forceFill([
                'resulting_attendance_snapshot' => $result['resulting_snapshot'],
            ])->save();

            $this->attendanceLogService->logCorrectionApproved(
                attendanceId: $attendance->id,
                userId: $attendance->user_id,
                context: [
                    'correction_id' => $lockedCorrection->id,
                    'reviewed_by' => $reviewer->id,
                    'reviewer_role' => $reviewer->getActiveRole(),
                ],
                occurredAt: now('Asia/Jakarta'),
            );

            return $lockedCorrection->fresh(['attendance.user', 'reviewer', 'appliedBy']);
        });
    }

    public function reject(AttendanceCorrection $correction, User $reviewer, string $reviewerNote): AttendanceCorrection
    {
        return DB::transaction(function () use ($correction, $reviewer, $reviewerNote): AttendanceCorrection {
            $lockedCorrection = AttendanceCorrection::query()
                ->with(['attendance.user.roles', 'attendance.user.division'])
                ->lockForUpdate()
                ->findOrFail($correction->id);

            $this->assertPending($lockedCorrection);
            $this->assertCanReview($lockedCorrection, $reviewer);

            $lockedCorrection->forceFill([
                'status' => 'rejected',
                'reviewer_note' => $reviewerNote,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now('Asia/Jakarta'),
            ])->save();

            $this->attendanceLogService->logCorrectionRejected(
                attendanceId: $lockedCorrection->attendance_id,
                userId: $lockedCorrection->user_id,
                context: [
                    'correction_id' => $lockedCorrection->id,
                    'reviewed_by' => $reviewer->id,
                    'reviewer_role' => $reviewer->getActiveRole(),
                ],
                occurredAt: now('Asia/Jakarta'),
            );

            return $lockedCorrection->fresh(['attendance.user', 'reviewer']);
        });
    }

    private function assertPending(AttendanceCorrection $correction): void
    {
        if ($correction->status !== 'pending') {
            throw new CorrectionAlreadyReviewedException([
                'correction_id' => $correction->id,
                'current_status' => $correction->status,
            ]);
        }
    }

    private function assertCanReview(AttendanceCorrection $correction, User $reviewer): void
    {
        if ($reviewer->hasActiveRole(Roles::Admin->value) || $reviewer->hasActiveRole(Roles::SuperAdmin->value)) {
            return;
        }

        $employee = $correction->attendance?->user;
        $employeeDivision = $employee?->division;

        $canReviewAsTeamLeader =
            $reviewer->hasActiveRole(Roles::Approver->value)
            && $employee !== null
            && $employeeDivision !== null
            && (int) $employeeDivision->leader_id === (int) $reviewer->id
            && $employee->hasRole(Roles::Employee->value)
            && (int) $employee->id !== (int) $reviewer->id;

        if ($canReviewAsTeamLeader) {
            return;
        }

        throw new CorrectionNotReviewableByCurrentUserException([
            'correction_id' => $correction->id,
            'reviewer_id' => $reviewer->id,
            'reviewer_role' => $reviewer->getActiveRole(),
        ]);
    }
}
