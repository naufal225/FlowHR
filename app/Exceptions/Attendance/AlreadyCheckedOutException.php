<?php

namespace App\Exceptions\Attendance;

class AlreadyCheckedOutException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Anda sudah melakukan check-out hari ini.',
            errorCode: 'ALREADY_CHECKED_OUT',
            statusCode: 409,
            context: $context,
        );
    }
}
