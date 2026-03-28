<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Data\Attendance\DailyAttendanceStatusData;
use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceLogActionStatus;
use App\Models\Attendance;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSetting;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceUiService
{
    private const STATUS_META = [
        'complete' => ['label' => 'Complete', 'icon' => 'fa-solid fa-circle-check', 'pill_classes' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200', 'surface_classes' => 'border-emerald-200 bg-emerald-50', 'icon_bg_classes' => 'bg-emerald-100 text-emerald-700', 'dot_classes' => 'bg-emerald-500'],
        'checked_in' => ['label' => 'Checked In', 'icon' => 'fa-solid fa-right-to-bracket', 'pill_classes' => 'bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200', 'surface_classes' => 'border-sky-200 bg-sky-50', 'icon_bg_classes' => 'bg-sky-100 text-sky-700', 'dot_classes' => 'bg-sky-500'],
        'late' => ['label' => 'Late', 'icon' => 'fa-solid fa-clock', 'pill_classes' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200', 'surface_classes' => 'border-amber-200 bg-amber-50', 'icon_bg_classes' => 'bg-amber-100 text-amber-700', 'dot_classes' => 'bg-amber-500'],
        'early_leave' => ['label' => 'Early Leave', 'icon' => 'fa-solid fa-person-walking-arrow-right', 'pill_classes' => 'bg-orange-100 text-orange-700 ring-1 ring-inset ring-orange-200', 'surface_classes' => 'border-orange-200 bg-orange-50', 'icon_bg_classes' => 'bg-orange-100 text-orange-700', 'dot_classes' => 'bg-orange-500'],
        'absent' => ['label' => 'Absent', 'icon' => 'fa-solid fa-circle-xmark', 'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200', 'surface_classes' => 'border-rose-200 bg-rose-50', 'icon_bg_classes' => 'bg-rose-100 text-rose-700', 'dot_classes' => 'bg-rose-500'],
        'not_checked_in' => ['label' => 'Not Checked In', 'icon' => 'fa-solid fa-user-clock', 'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200', 'surface_classes' => 'border-rose-200 bg-rose-50', 'icon_bg_classes' => 'bg-rose-100 text-rose-700', 'dot_classes' => 'bg-rose-500'],
        'on_leave' => ['label' => 'On Leave', 'icon' => 'fa-solid fa-umbrella-beach', 'pill_classes' => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200', 'surface_classes' => 'border-slate-200 bg-slate-50', 'icon_bg_classes' => 'bg-slate-100 text-slate-700', 'dot_classes' => 'bg-slate-500'],
        'off_day' => ['label' => 'Off Day', 'icon' => 'fa-solid fa-calendar-day', 'pill_classes' => 'bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200', 'surface_classes' => 'border-gray-200 bg-gray-50', 'icon_bg_classes' => 'bg-gray-100 text-gray-700', 'dot_classes' => 'bg-gray-500'],
        'suspicious' => ['label' => 'Suspicious', 'icon' => 'fa-solid fa-shield-halved', 'pill_classes' => 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200', 'surface_classes' => 'border-red-200 bg-red-50', 'icon_bg_classes' => 'bg-red-100 text-red-700', 'dot_classes' => 'bg-red-500'],
        'incomplete' => ['label' => 'Incomplete', 'icon' => 'fa-solid fa-triangle-exclamation', 'pill_classes' => 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200', 'surface_classes' => 'border-red-200 bg-red-50', 'icon_bg_classes' => 'bg-red-100 text-red-700', 'dot_classes' => 'bg-red-500'],
        'overtime' => ['label' => 'Overtime', 'icon' => 'fa-solid fa-moon', 'pill_classes' => 'bg-cyan-100 text-cyan-700 ring-1 ring-inset ring-cyan-200', 'surface_classes' => 'border-cyan-200 bg-cyan-50', 'icon_bg_classes' => 'bg-cyan-100 text-cyan-700', 'dot_classes' => 'bg-cyan-500'],
        'config_issue' => ['label' => 'Configuration Issue', 'icon' => 'fa-solid fa-gear', 'pill_classes' => 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200', 'surface_classes' => 'border-red-200 bg-red-50', 'icon_bg_classes' => 'bg-red-100 text-red-700', 'dot_classes' => 'bg-red-500'],
    ];

    public function badgeFromStatus(string $status, ?string $label = null): array
    {
        $key = $this->normalizeStatus($status);
        $meta = self::STATUS_META[$key] ?? self::STATUS_META['config_issue'];

        return [
            'key' => $key,
            'label' => $label ?? $meta['label'],
            'icon' => $meta['icon'],
            'pill_classes' => $meta['pill_classes'],
            'surface_classes' => $meta['surface_classes'],
            'icon_bg_classes' => $meta['icon_bg_classes'],
            'dot_classes' => $meta['dot_classes'],
        ];
    }

    public function makeDailyStatus(DailyAttendanceStatusData $status): array
    {
        $primary = $this->badgeFromStatus($status->status, $status->label);

        return [
            'key' => $primary['key'],
            'badge' => $primary,
            'description' => $status->reason ?: $this->defaultStatusDescription($primary['key']),
            'attendance_id' => $status->attendanceId,
            'date_label' => $this->formatDate($status->date),
            'check_in' => $this->formatTime($status->checkInAt),
            'check_out' => $this->formatTime($status->checkOutAt),
            'flags' => $this->buildFlags((bool) $status->isLate, (bool) $status->isEarlyLeave, (bool) $status->isSuspicious, 0),
        ];
    }

    public function makePolicySummary(AttendancePolicyData $policy, ?OfficeLocation $office = null): array
    {
        return [
            'office_name' => $office?->name ?? 'Assigned office',
            'office_address' => $office?->address,
            'work_start' => $this->formatClockString($policy->workStartTime),
            'work_end' => $this->formatClockString($policy->workEndTime),
            'late_tolerance' => $policy->lateToleranceMinutes . ' min',
            'allowed_radius' => $policy->allowedRadiusMeter . ' m',
            'min_accuracy' => $policy->minLocationAccuracyMeter . ' m',
            'qr_rotation' => $policy->qrRotationSeconds . ' sec',
            'timezone' => $policy->timezone,
        ];
    }

    public function makeHistoryRow(Attendance $attendance, bool $includeEmployee = false): array
    {
        return [
            'id' => $attendance->id,
            'date' => $this->formatDate($attendance->work_date),
            'date_short' => $attendance->work_date?->format('d M'),
            'employee_name' => $includeEmployee ? ($attendance->user?->name ?? '-') : null,
            'employee_email' => $includeEmployee ? ($attendance->user?->email ?? '-') : null,
            'office_name' => $attendance->officeLocation?->name ?? '-',
            'mode' => $attendance->attendance_qr_token_id !== null ? 'QR' : 'Mobile',
            'check_in' => $this->formatTime($attendance->check_in_at),
            'check_out' => $this->formatTime($attendance->check_out_at),
            'record_status_label' => $attendance->record_status?->label() ?? '-',
            'check_in_status_label' => $attendance->check_in_status?->label() ?? '-',
            'check_out_status_label' => $attendance->check_out_status?->label() ?? '-',
            'late_minutes' => (int) ($attendance->late_minutes ?? 0),
            'early_leave_minutes' => (int) ($attendance->early_leave_minutes ?? 0),
            'overtime_minutes' => (int) ($attendance->overtime_minutes ?? 0),
            'late_label' => $this->formatMinutes($attendance->late_minutes),
            'early_leave_label' => $this->formatMinutes($attendance->early_leave_minutes),
            'overtime_label' => $this->formatMinutes($attendance->overtime_minutes),
            'primary_status' => $this->badgeFromStatus($this->resolvePrimaryAttendanceStatus($attendance)),
            'flags' => $this->buildFlags(
                (int) ($attendance->late_minutes ?? 0) > 0 || $attendance->check_in_status === AttendanceCheckInStatus::LATE,
                (int) ($attendance->early_leave_minutes ?? 0) > 0 || $attendance->check_out_status === AttendanceCheckOutStatus::EARLY_LEAVE,
                (bool) $attendance->is_suspicious,
                (int) ($attendance->overtime_minutes ?? 0),
            ),
            'suspicious_reason' => $this->humanizeReason($attendance->suspicious_reason),
        ];
    }

    public function makeMonitorRow(User $employee, DailyAttendanceStatusData $status): array
    {
        $daily = $this->makeDailyStatus($status);

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_email' => $employee->email,
            'office_name' => $employee->officeLocation?->name ?? 'Office not assigned',
            'division_name' => $employee->division?->name ?? '-',
            'status' => $daily['badge'],
            'status_key' => $daily['key'],
            'status_description' => $daily['description'],
            'check_in' => $daily['check_in'],
            'check_out' => $daily['check_out'],
            'flags' => $daily['flags'],
            'has_check_in' => $status->checkInAt !== null,
            'is_late' => (bool) $status->isLate,
            'is_suspicious' => (bool) $status->isSuspicious,
            'detail_id' => $status->attendanceId,
        ];
    }

    public function makeMonitorErrorRow(User $employee, string $message): array
    {
        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'employee_email' => $employee->email,
            'office_name' => $employee->officeLocation?->name ?? 'Office not assigned',
            'division_name' => $employee->division?->name ?? '-',
            'status' => $this->badgeFromStatus('config_issue', 'Configuration Issue'),
            'status_key' => 'config_issue',
            'status_description' => $message,
            'check_in' => '-',
            'check_out' => '-',
            'flags' => [],
            'has_check_in' => false,
            'is_late' => false,
            'is_suspicious' => true,
            'detail_id' => null,
        ];
    }

    public function makeAttendanceDetail(Attendance $attendance, bool $includeSensitive): array
    {
        return [
            'id' => $attendance->id,
            'date' => $this->formatDate($attendance->work_date),
            'primary_status' => $this->badgeFromStatus($this->resolvePrimaryAttendanceStatus($attendance)),
            'flags' => $this->buildFlags((int) ($attendance->late_minutes ?? 0) > 0, (int) ($attendance->early_leave_minutes ?? 0) > 0, (bool) $attendance->is_suspicious, (int) ($attendance->overtime_minutes ?? 0)),
            'summary' => [
                ['label' => 'Record Status', 'value' => $attendance->record_status?->label() ?? '-'],
                ['label' => 'Check-in Status', 'value' => $attendance->check_in_status?->label() ?? '-'],
                ['label' => 'Check-out Status', 'value' => $attendance->check_out_status?->label() ?? '-'],
                ['label' => 'Late Minutes', 'value' => $this->formatMinutes($attendance->late_minutes)],
                ['label' => 'Early Leave', 'value' => $this->formatMinutes($attendance->early_leave_minutes)],
                ['label' => 'Overtime', 'value' => $this->formatMinutes($attendance->overtime_minutes)],
            ],
            'employee' => ['name' => $attendance->user?->name ?? '-', 'email' => $attendance->user?->email ?? '-', 'office' => $attendance->officeLocation?->name ?? '-', 'division' => $attendance->user?->division?->name ?? '-'],
            'check_in' => ['time' => $this->formatDateTime($attendance->check_in_at), 'recorded_at' => $this->formatDateTime($attendance->check_in_recorded_at), 'latitude' => $this->formatCoordinate($attendance->check_in_latitude), 'longitude' => $this->formatCoordinate($attendance->check_in_longitude), 'accuracy' => $this->formatAccuracy($attendance->check_in_accuracy_meter)],
            'check_out' => ['time' => $this->formatDateTime($attendance->check_out_at), 'recorded_at' => $this->formatDateTime($attendance->check_out_recorded_at), 'latitude' => $this->formatCoordinate($attendance->check_out_latitude), 'longitude' => $this->formatCoordinate($attendance->check_out_longitude), 'accuracy' => $this->formatAccuracy($attendance->check_out_accuracy_meter)],
            'suspicious_reason' => $this->humanizeReason($attendance->suspicious_reason),
            'notes' => $attendance->notes,
            'qr' => ['generated_at' => $this->formatDateTime($attendance->attendanceQrToken?->generated_at), 'expired_at' => $this->formatDateTime($attendance->attendanceQrToken?->expired_at), 'masked_token' => $includeSensitive ? $this->maskToken($attendance->attendanceQrToken?->token) : null, 'is_active' => $attendance->attendanceQrToken?->is_active ?? false],
            'logs' => $attendance->logs->map(function ($log) use ($includeSensitive): array {
                return [
                    'id' => $log->id,
                    'type' => $log->action_type?->label() ?? Str::headline((string) $log->action_type),
                    'status' => $this->logBadge((string) ($log->action_status?->value ?? $log->action_status)),
                    'message' => $log->message ?: 'No message was recorded for this event.',
                    'occurred_at' => $this->formatDateTime($log->occurred_at ?? $log->created_at),
                    'latitude' => $this->formatCoordinate($log->latitude),
                    'longitude' => $this->formatCoordinate($log->longitude),
                    'accuracy' => $this->formatAccuracy($log->accuracy_meter),
                    'ip_address' => $includeSensitive ? ($log->ip_address ?? '-') : null,
                    'device_info' => $includeSensitive ? ($log->device_info ?? '-') : null,
                ];
            })->all(),
        ];
    }

    public function makeQrCard(?AttendanceQrToken $token, ?OfficeLocation $office, ?AttendanceSetting $setting): array
    {
        $isValid = $token?->is_currently_valid ?? false;

        return [
            'office_location_id' => $office?->id,
            'office_name' => $office?->name ?? '-',
            'office_address' => $office?->address,
            'rotation_seconds' => $setting?->qr_rotation_seconds,
            'min_accuracy' => $setting?->min_location_accuracy_meter,
            'has_token' => $token !== null,
            'status_label' => $isValid ? 'Active' : ($token !== null ? 'Inactive' : 'Unavailable'),
            'status_classes' => $isValid ? 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200' : ($token !== null ? 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200' : 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'),
            'token' => $token?->token,
            'masked_token' => $this->maskToken($token?->token),
            'generated_at' => $this->formatDateTime($token?->generated_at),
            'expires_at' => $this->formatDateTime($token?->expired_at),
            'generated_at_iso' => $token?->generated_at?->toIso8601String(),
            'expires_at_iso' => $token?->expired_at?->toIso8601String(),
            'expires_at_timestamp' => $token?->expired_at?->timestamp,
            'expires_in' => $token?->expired_at ? ($token->expired_at->isPast() ? 'Expired' : now($token->expired_at->timezone)->diffForHumans($token->expired_at, ['parts' => 2, 'short' => true])) : '-',
            'is_active' => $token?->is_active ?? false,
            'is_expired' => $token?->is_expired ?? false,
            'is_valid' => $isValid,
        ];
    }

    public function makeSettingSummary(?AttendanceSetting $setting, ?OfficeLocation $office): ?array
    {
        if ($setting === null) {
            return null;
        }

        return [
            'office_name' => $office?->name ?? $setting->officeLocation?->name ?? '-',
            'work_start' => $this->formatClockString((string) $setting->work_start_time),
            'work_end' => $this->formatClockString((string) $setting->work_end_time),
            'late_tolerance' => $setting->late_tolerance_minutes . ' min',
            'qr_rotation' => $setting->qr_rotation_seconds . ' sec',
            'min_accuracy' => $setting->min_location_accuracy_meter . ' m',
            'active' => $setting->is_active,
            'radius' => $office?->radius_meter ? $office->radius_meter . ' m' : '-',
        ];
    }

    public function humanizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            return null;
        }

        return Str::contains($reason, ' ')
            ? $reason
            : Str::headline(str_replace(['.', '-'], ' ', strtolower($reason)));
    }

    public function formatMinutes(?int $minutes): string
    {
        $minutes = (int) ($minutes ?? 0);
        return $minutes > 0 ? $minutes . ' min' : '-';
    }

    private function buildFlags(bool $isLate, bool $isEarlyLeave, bool $isSuspicious, int $overtimeMinutes): array
    {
        $flags = [];
        if ($isLate) {
            $flags[] = $this->badgeFromStatus('late');
        }
        if ($isEarlyLeave) {
            $flags[] = $this->badgeFromStatus('early_leave');
        }
        if ($overtimeMinutes > 0) {
            $flags[] = $this->badgeFromStatus('overtime', 'Overtime');
        }
        if ($isSuspicious) {
            $flags[] = $this->badgeFromStatus('suspicious');
        }
        return $flags;
    }

    private function resolvePrimaryAttendanceStatus(Attendance $attendance): string
    {
        if ($attendance->check_in_at === null && $attendance->check_out_at === null) {
            return 'absent';
        }
        if ($attendance->record_status?->value === 'incomplete') {
            return 'incomplete';
        }
        if ($attendance->check_in_at !== null && $attendance->check_out_at === null) {
            return 'checked_in';
        }
        return 'complete';
    }

    private function normalizeStatus(string $status): string
    {
        return match ($status) {
            'not_checked_in_yet' => 'not_checked_in',
            'ongoing' => 'checked_in',
            default => $status,
        };
    }

    private function defaultStatusDescription(string $status): string
    {
        return match ($status) {
            'complete' => 'Attendance for the selected day has been completed.',
            'checked_in' => 'Check-out is still pending for this attendance record.',
            'late' => 'Attendance exists but it was recorded after the tolerance window.',
            'early_leave' => 'The employee checked out before the scheduled work end time.',
            'absent' => 'No valid attendance record was found for the selected day.',
            'not_checked_in' => 'No check-in has been recorded yet for the current workday.',
            'on_leave' => 'The employee is covered by an approved leave request.',
            'off_day' => 'The selected day is treated as a non-working day.',
            'suspicious' => 'This attendance has been flagged for manual review.',
            default => 'Attendance data needs attention before it can be relied upon operationally.',
        };
    }

    private function logBadge(string $status): array
    {
        return match ($status) {
            AttendanceLogActionStatus::SUCCESS->value => ['label' => AttendanceLogActionStatus::SUCCESS->label(), 'pill_classes' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'],
            AttendanceLogActionStatus::REJECTED->value => ['label' => AttendanceLogActionStatus::REJECTED->label(), 'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200'],
            default => ['label' => AttendanceLogActionStatus::SUSPICIOUS->label(), 'pill_classes' => 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200'],
        };
    }

    private function formatDate(?Carbon $value): string
    {
        return $value?->translatedFormat('D, d M Y') ?? '-';
    }

    private function formatTime(?Carbon $value): string
    {
        return $value?->format('H:i') ?? '-';
    }

    private function formatDateTime(?Carbon $value): string
    {
        return $value?->translatedFormat('d M Y H:i') ?? '-';
    }

    private function formatClockString(string $value): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $value)->format('H:i');
        } catch (\Throwable) {
            try {
                return Carbon::createFromFormat('H:i', $value)->format('H:i');
            } catch (\Throwable) {
                return $value;
            }
        }
    }

    private function formatCoordinate(mixed $value): string
    {
        return $value !== null ? number_format((float) $value, 6) : '-';
    }

    private function formatAccuracy(mixed $value): string
    {
        return $value !== null ? number_format((float) $value, 1) . ' m' : '-';
    }

    private function maskToken(?string $token): string
    {
        $token = trim((string) $token);
        if ($token === '') {
            return '-';
        }
        if (Str::length($token) <= 8) {
            return $token;
        }
        return Str::substr($token, 0, 4) . '...' . Str::substr($token, -4);
    }
}
