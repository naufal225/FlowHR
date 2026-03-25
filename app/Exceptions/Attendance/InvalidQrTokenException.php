<?php

namespace App\Exceptions\Attendance;

class InvalidQrTokenException extends AttendanceException
{
    public function __construct(string $message = 'QR code tidak valid.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'INVALID_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
