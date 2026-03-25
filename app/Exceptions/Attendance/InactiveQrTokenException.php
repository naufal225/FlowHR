<?php

namespace App\Exceptions\Attendance;

class InactiveQrTokenException extends AttendanceException
{
    public function __construct(string $message = 'QR code sedang tidak aktif.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'INACTIVE_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
