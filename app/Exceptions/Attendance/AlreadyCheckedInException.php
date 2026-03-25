<?php

namespace App\Exceptions\Attendance;

class AlreadyCheckedInException extends AttendanceException
{
    public function __construct(string $message = 'Anda sudah melakukan check-in hari ini.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'ALREADY_CHECKED_IN',
            statusCode: 409,
            context: $context,
        );
    }
}
