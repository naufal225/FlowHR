<?php

namespace App\Exceptions\Attendance;

class OnLeaveAttendanceNotAllowedException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Anda sedang berada dalam status cuti yang disetujui, sehingga absensi tidak dapat dilakukan.',
            errorCode: 'ON_LEAVE_ATTENDANCE_NOT_ALLOWED',
            statusCode: 403,
            context: $context,
        );
    }
}
