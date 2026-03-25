<?php

namespace App\Exceptions\Attendance;

class InvalidAttendanceLocationException extends AttendanceException
{
    public function __construct(string $message = 'Lokasi tidak valid.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'INVALID_LOCATION',
            statusCode: 422,
            context: $context,
        );
    }
}
