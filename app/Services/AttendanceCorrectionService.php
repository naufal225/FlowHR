<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionService
{
    public function submit(User $user, AttendanceRecord $attendanceRecord, array $data): AttendanceCorrection
    {
        $checkIn = !empty($data['requested_check_in_time'])
            ? Carbon::parse($data['requested_check_in_time'])
            : null;
        $checkOut = !empty($data['requested_check_out_time'])
            ? Carbon::parse($data['requested_check_out_time'])
            : null;

        if (!$checkIn && !$checkOut) {
            throw ValidationException::withMessages([
                'requested_check_in_time' => 'Isi minimal salah satu waktu koreksi (check in atau check out).',
            ]);
        }

        $effectiveCheckIn = $checkIn ?? $attendanceRecord->check_in_time;
        $effectiveCheckOut = $checkOut ?? $attendanceRecord->check_out_time;

        if ($effectiveCheckIn && $effectiveCheckOut && $effectiveCheckOut->lt($effectiveCheckIn)) {
            throw ValidationException::withMessages([
                'requested_check_out_time' => 'Waktu check out tidak boleh lebih kecil dari check in.',
            ]);
        }

        $hasPendingCorrection = AttendanceCorrection::query()
            ->where('attendance_record_id', $attendanceRecord->id)
            ->where('status', AttendanceCorrection::STATUS_PENDING)
            ->exists();

        if ($hasPendingCorrection) {
            throw ValidationException::withMessages([
                'attendance_record_id' => 'Koreksi untuk log absensi ini masih menunggu persetujuan.',
            ]);
        }

        return AttendanceCorrection::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'attendance_date' => $attendanceRecord->attendance_date,
            'requested_check_in_time' => $checkIn,
            'requested_check_out_time' => $checkOut,
            'reason' => $data['reason'],
            'status' => AttendanceCorrection::STATUS_PENDING,
        ]);
    }
}

