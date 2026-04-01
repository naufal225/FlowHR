<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Exceptions\Attendance\AttendanceCorrectionWorkflowException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionSubmissionService
{
    public function __construct(
        private readonly AttendanceCorrectionSnapshotService $attendanceCorrectionSnapshotService,
        private readonly AttendanceLogService $attendanceLogService,
    ) {}

    public function submit(
        User $user,
        int $attendanceId,
        ?Carbon $requestedCheckInTime,
        ?Carbon $requestedCheckOutTime,
        string $reason,
    ): AttendanceCorrection {
        $attendance = Attendance::query()
            ->where('id', $attendanceId)
            ->where('user_id', $user->id)
            ->first();

        if ($attendance === null) {
            throw new AttendanceCorrectionWorkflowException(
                message: 'Log absensi tidak valid untuk user aktif.',
                errorCode: 'ATTENDANCE_CORRECTION_ATTENDANCE_INVALID',
            );
        }

        $this->assertCorrectionRequestIsValid($attendance, $requestedCheckInTime, $requestedCheckOutTime);

        return DB::transaction(function () use ($attendance, $user, $requestedCheckInTime, $requestedCheckOutTime, $reason): AttendanceCorrection {
            $hasPendingCorrection = AttendanceCorrection::query()
                ->where('user_id', $user->id)
                ->where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($hasPendingCorrection) {
                throw new AttendanceCorrectionWorkflowException(
                    message: 'Log absensi ini sudah memiliki pengajuan koreksi yang masih pending.',
                    errorCode: 'ATTENDANCE_CORRECTION_ALREADY_PENDING',
                );
            }

            $correction = AttendanceCorrection::query()->create([
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'requested_check_in_time' => $requestedCheckInTime,
                'requested_check_out_time' => $requestedCheckOutTime,
                'reason' => trim($reason),
                'original_attendance_snapshot' => $this->attendanceCorrectionSnapshotService->makeSnapshot($attendance),
                'status' => 'pending',
            ]);

            $this->attendanceLogService->logCorrectionSubmitted(
                attendanceId: $attendance->id,
                userId: $user->id,
                context: [
                    'correction_id' => $correction->id,
                    'requested_check_in_time' => $requestedCheckInTime?->toIso8601String(),
                    'requested_check_out_time' => $requestedCheckOutTime?->toIso8601String(),
                ],
                occurredAt: now('Asia/Jakarta'),
            );

            return $correction->fresh(['attendance', 'reviewer']);
        });
    }

    private function assertCorrectionRequestIsValid(
        Attendance $attendance,
        ?Carbon $requestedCheckInTime,
        ?Carbon $requestedCheckOutTime,
    ): void {
        if ($requestedCheckInTime === null && $requestedCheckOutTime === null) {
            throw new AttendanceCorrectionWorkflowException(
                message: 'Minimal salah satu waktu koreksi harus diisi.',
                errorCode: 'ATTENDANCE_CORRECTION_EMPTY_REQUEST',
            );
        }

        if ($requestedCheckInTime !== null && $requestedCheckOutTime !== null && $requestedCheckOutTime->lt($requestedCheckInTime)) {
            throw new AttendanceCorrectionWorkflowException(
                message: 'Waktu check out koreksi tidak boleh lebih awal dari check in koreksi.',
                errorCode: 'ATTENDANCE_CORRECTION_INVALID_TIME_RANGE',
            );
        }

        if ($requestedCheckOutTime !== null && $requestedCheckInTime === null && $attendance->check_in_at === null) {
            throw new AttendanceCorrectionWorkflowException(
                message: 'Koreksi check-in wajib diisi jika log saat ini belum memiliki check-in.',
                errorCode: 'ATTENDANCE_CORRECTION_MISSING_CHECK_IN',
            );
        }
    }
}
