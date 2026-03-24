<?php

namespace App\Exceptions\Attendance;

class OfficeLocationNotAssignedException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Karyawan belum memiliki lokasi kantor yang ditetapkan.',
            errorCode: 'OFFICE_LOCATION_NOT_ASSIGNED',
            statusCode: 422,
            context: $context,
        );
    }
}
