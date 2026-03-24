<?php

namespace App\Exceptions\Attendance;

class AttendanceNotAllowedException extends AttendanceException
{
    public function __construct(
        string $message = 'Absensi tidak diizinkan.',
        array $context = []
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'ATTENDANCE_NOT_ALLOWED',
            statusCode: 403,
            context: $context,
        );
    }
}
