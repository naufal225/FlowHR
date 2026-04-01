<?php

namespace App\Exceptions\Attendance;

class MobileAuthNotAllowedException extends AttendanceException
{
    public function __construct(array $context = [])
    {
        parent::__construct(
            message: 'Akun ini tidak diizinkan menggunakan mobile employee access.',
            errorCode: 'MOBILE_AUTH_NOT_ALLOWED',
            statusCode: 403,
            context: $context,
        );
    }
}
