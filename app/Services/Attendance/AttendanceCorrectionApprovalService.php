<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\AttendanceCorrection;
use App\Models\User;

class AttendanceCorrectionApprovalService
{
    public function __construct(
        private readonly AttendanceCorrectionReviewService $attendanceCorrectionReviewService,
    ) {}

    public function approve(AttendanceCorrection $correction, User $reviewer, ?string $reviewerNote = null): AttendanceCorrection
    {
        return $this->attendanceCorrectionReviewService->approve($correction, $reviewer, $reviewerNote);
    }

    public function reject(AttendanceCorrection $correction, User $reviewer, string $reviewerNote): AttendanceCorrection
    {
        return $this->attendanceCorrectionReviewService->reject($correction, $reviewer, $reviewerNote);
    }
}
