<?php

namespace App\Exceptions\Attendance;

class ExpiredQrTokenException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'QR code sudah kedaluwarsa.',
            errorCode: 'EXPIRED_QR_TOKEN',
            statusCode: 422,
            context: $context,
        );
    }
}
