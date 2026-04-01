<?php

namespace App\Exceptions\Attendance;

class CorrectionNotReviewableByCurrentUserException extends AttendanceCorrectionWorkflowException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'You are not allowed to review this attendance correction.',
            errorCode: 'CORRECTION_REVIEW_NOT_ALLOWED',
            statusCode: 403,
            context: $context,
        );
    }
}
