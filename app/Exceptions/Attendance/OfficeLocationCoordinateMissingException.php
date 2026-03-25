<?php

namespace App\Exceptions\Attendance;

class OfficeLocationCoordinateMissingException extends AttendanceException
{
    public function __construct(
        string $message = 'Koordinat kantor belum dikonfigurasi.',
        array $context = []
    ) {
        parent::__construct(
            message: $message,
            errorCode: 'OFFICE_LOCATION_COORDINATE_MISSING',
            statusCode: 500,
            context: $context,
        );
    }
}
