<?php

namespace App\Exceptions\Attendance;

class AttendanceAlreadyCompletedException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Absensi hari ini sudah selesai diproses.',
            errorCode: 'ATTENDANCE_ALREADY_COMPLETED',
            statusCode: 409,
            context: $context,
        );
    }
}
