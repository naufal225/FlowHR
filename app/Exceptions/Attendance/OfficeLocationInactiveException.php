<?php

namespace App\Exceptions\Attendance;

class OfficeLocationInactiveException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Lokasi kantor sedang tidak aktif.',
            errorCode: 'OFFICE_LOCATION_INACTIVE',
            statusCode: 422,
            context: $context,
        );
    }
}
