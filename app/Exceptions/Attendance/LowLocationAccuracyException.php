<?php

namespace App\Exceptions\Attendance;

class LowLocationAccuracyException extends AttendanceException
{
    public function __construct(string $message = 'Akurasi lokasi terlalu rendah untuk memproses absensi.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: 'LOW_LOCATION_ACCURACY',
            statusCode: 422,
            context: $context,
        );
    }
}
