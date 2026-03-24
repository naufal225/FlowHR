<?php

namespace App\Exceptions\Attendance;

class InactiveQrTokenException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'QR code sedang tidak aktif.',
            errorCode: 'INACTIVE_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
