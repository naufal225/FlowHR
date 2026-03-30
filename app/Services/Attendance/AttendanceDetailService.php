<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Attendance;

class AttendanceDetailService
{
    /**
     * Detail attendance untuk employee sendiri.
     * Scope dikunci ke user_id agar employee tidak bisa akses attendance orang lain.
     */
    public function getEmployeeAttendanceDetail(
        int $userId,
        int $attendanceId,
    ): Attendance {
        return Attendance::query()
            ->with([
                'officeLocation:id,name,address,latitude,longitude,radius_meter',
                'officeLocation.activeAttendanceSetting:id,office_location_id,work_start_time,work_end_time,late_tolerance_minutes,is_active',
                'attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active',
                'logs' => function ($query) {
                    $query->select([
                        'id',
                        'attendance_id',
                        'user_id',
                        'action_type',
                        'action_status',
                        'latitude',
                        'longitude',
                        'accuracy_meter',
                        'ip_address',
                        'device_info',
                        'message',
                        'occurred_at',
                        'created_at',
                    ])->orderBy('occurred_at', 'asc')
                      ->orderBy('id', 'asc');
                },
            ])
            ->where('id', $attendanceId)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    /**
     * Detail attendance untuk admin lintas employee.
     */
    public function getAttendanceDetailForAdmin(int $attendanceId): Attendance
    {
        return Attendance::query()
            ->with([
                'user:id,name,email,office_location_id',
                'officeLocation:id,name,address,latitude,longitude,radius_meter',
                'officeLocation.activeAttendanceSetting:id,office_location_id,work_start_time,work_end_time,late_tolerance_minutes,is_active',
                'attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active',
                'logs' => function ($query) {
                    $query->select([
                        'id',
                        'attendance_id',
                        'user_id',
                        'action_type',
                        'action_status',
                        'latitude',
                        'longitude',
                        'accuracy_meter',
                        'ip_address',
                        'device_info',
                        'message',
                        'occurred_at',
                        'created_at',
                    ])->orderBy('occurred_at', 'asc')
                      ->orderBy('id', 'asc');
                },
            ])
            ->where('id', $attendanceId)
            ->firstOrFail();
    }
}
