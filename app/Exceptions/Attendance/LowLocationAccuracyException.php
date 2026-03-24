<?php

namespace App\Exceptions\Attendance;

class LowLocationAccuracyException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Akurasi lokasi terlalu rendah untuk memproses absensi.',
            errorCode: 'LOW_LOCATION_ACCURACY',
            statusCode: 422,
            context: $context,
        );
    }
}
