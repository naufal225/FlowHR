<?php

namespace App\Exceptions\Attendance;

class AttendancePolicyNotFoundException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Pengaturan absensi untuk lokasi kantor tidak ditemukan.',
            errorCode: 'ATTENDANCE_POLICY_NOT_FOUND',
            statusCode: 422,
            context: $context,
        );
    }
}
