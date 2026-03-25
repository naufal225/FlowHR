<?php

namespace App\Exceptions\Attendance;

class OfficeLocationMismatchException extends AttendanceException
{
    public function __construct(string $message = 'Lokasi Anda tidak cocok dengan kode QR.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'LOCATION_MISMATCH',
            statusCode: 422,
            context: $context,
        );
    }
}
