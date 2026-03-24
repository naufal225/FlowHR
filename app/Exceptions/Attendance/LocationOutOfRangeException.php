<?php

namespace App\Exceptions\Attendance;

class LocationOutOfRangeException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Lokasi Anda berada di luar radius absensi yang diizinkan.',
            errorCode: 'LOCATION_OUT_OF_RANGE',
            statusCode: 422,
            context: $context,
        );
    }
}
