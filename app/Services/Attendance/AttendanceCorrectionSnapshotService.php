<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\Attendance;

class AttendanceCorrectionSnapshotService
{
    public function makeSnapshot(Attendance $attendance): array
    {
        return [
            'work_date' => $attendance->work_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toIso8601String(),
            'check_out_at' => $attendance->check_out_at?->toIso8601String(),
            'check_in_status' => $attendance->check_in_status?->value,
            'check_out_status' => $attendance->check_out_status?->value,
            'record_status' => $attendance->record_status?->value,
            'late_minutes' => (int) ($attendance->late_minutes ?? 0),
            'early_leave_minutes' => (int) ($attendance->early_leave_minutes ?? 0),
            'overtime_minutes' => (int) ($attendance->overtime_minutes ?? 0),
            'is_suspicious' => (bool) $attendance->is_suspicious,
            'suspicious_reason' => $attendance->suspicious_reason,
            'check_in_recorded_at' => $attendance->check_in_recorded_at?->toIso8601String(),
            'check_out_recorded_at' => $attendance->check_out_recorded_at?->toIso8601String(),
        ];
    }
}
