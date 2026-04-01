<?php

namespace App\Exceptions\Attendance;

class AttendanceCorrectionWorkflowException extends AttendanceException
{
    public function __construct(
        string $message = 'Correction workflow tidak valid.',
        string $errorCode = 'ATTENDANCE_CORRECTION_ERROR',
        int $statusCode = 422,
        array $context = [],
        ?\Exception $previous = null,
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }
}
