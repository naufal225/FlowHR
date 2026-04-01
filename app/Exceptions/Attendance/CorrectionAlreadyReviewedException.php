<?php

namespace App\Exceptions\Attendance;

class CorrectionAlreadyReviewedException extends AttendanceCorrectionWorkflowException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Correction request has already been reviewed.',
            errorCode: 'CORRECTION_ALREADY_REVIEWED',
            statusCode: 422,
            context: $context,
        );
    }
}
