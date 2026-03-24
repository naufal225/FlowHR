<?php

namespace App\Exceptions\Attendance;

class AttendanceNotFoundException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Data absensi tidak ditemukan.',
            errorCode: 'ATTENDANCE_NOT_FOUND',
            statusCode: 404,
            context: $context,
        );
    }
}
