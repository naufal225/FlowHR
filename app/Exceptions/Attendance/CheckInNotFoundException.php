<?php

namespace App\Exceptions\Attendance;

class CheckInNotFoundException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Check-in belum ditemukan, sehingga check-out tidak dapat dilakukan.',
            errorCode: 'CHECK_IN_NOT_FOUND',
            statusCode: 422,
            context: $context,
        );
    }
}
