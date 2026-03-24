<?php

namespace App\Exceptions\Attendance;

class AlreadyCheckedInException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Anda sudah melakukan check-in hari ini.',
            errorCode: 'ALREADY_CHECKED_IN',
            statusCode: 409,
            context: $context,
        );
    }
}
