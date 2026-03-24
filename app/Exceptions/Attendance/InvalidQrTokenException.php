<?php

namespace App\Exceptions\Attendance;

class InvalidQrTokenException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'QR code tidak valid.',
            errorCode: 'INVALID_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
