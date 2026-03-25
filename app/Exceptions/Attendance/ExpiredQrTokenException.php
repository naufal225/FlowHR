<?php

namespace App\Exceptions\Attendance;

class ExpiredQrTokenException extends AttendanceException
{
    public function __construct(string $message = 'QR code sudah kedaluwarsa.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'EXPIRED_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
