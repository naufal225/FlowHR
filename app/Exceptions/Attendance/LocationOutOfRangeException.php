<?php

namespace App\Exceptions\Attendance;

class LocationOutOfRangeException extends AttendanceException
{
    public function __construct(string $message = 'Lokasi Anda berada di luar radius absensi yang diizinkan.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'LOCATION_OUT_OF_RANGE',
            statusCode: 422,
            context: $context,
        );
    }
}
