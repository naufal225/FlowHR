<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Data\Attendance\DailyAttendanceStatusData;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Services\HolidayDateService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AttendanceDailyStatusResolverService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private AttendancePolicyService $attendancePolicyService,
        private HolidayDateService $holidayDateService,
    ) {}

    public function resolveForUser(User $user, Carbon $date): DailyAttendanceStatusData
    {
        $date = $this->normalizeDate($date);

        $offDayContext = $this->getOffDayContext($date);

        if ($offDayContext !== null) {
            return $this->buildOffDayStatus($user, $date, $offDayContext);
        }

        $attendance = $this->findAttendanceForDate($user->id, $date);
        $leave = $this->findApprovedLeaveForDate($user->id, $date);

        if ($attendance !== null) {
            return $this->resolveFromAttendance($attendance, $date, $leave);
        }

        return $this->resolveWithoutAttendance($user, $date, $leave);
    }

    /**
     * Resolve status untuk banyak user sekaligus.
     * Ini belum dioptimasi penuh untuk bulk query besar,
     * tapi sudah cukup rapi untuk scope awal.
     *
     * @param Collection<int, User> $users
     * @return Collection<int, DailyAttendanceStatusData>
     */
    public function resolveForUsers(Collection $users, Carbon $date): Collection
    {
        $date = $this->normalizeDate($date);

        return $users->map(function (User $user) use ($date): DailyAttendanceStatusData {
            return $this->resolveForUser($user, $date);
        });
    }

    private function normalizeDate(Carbon $date): Carbon
    {
        return $date->copy()
            ->timezone(self::DEFAULT_TIMEZONE)
            ->startOfDay();
    }

    public function getOffDayContext(Carbon $date): ?array
    {
        $date = $this->normalizeDate($date);

        $holiday = $this->findHolidayForDate($date);

        if ($holiday !== null) {
            $holidayName = trim((string) $holiday->name);

            return [
                'type' => 'holiday',
                'label' => $holidayName !== '' ? $holidayName : 'Hari libur nasional',
                'reason' => $holidayName !== ''
                    ? 'Today is a holiday: ' . $holidayName . '.'
                    : 'Today is a holiday.',
            ];
        }

        if ($date->isWeekend()) {
            return [
                'type' => 'weekend',
                'label' => 'Hari libur akhir pekan',
                'reason' => 'The selected date falls on a weekend.',
            ];
        }

        return null;
    }

    private function buildOffDayStatus(User $user, Carbon $date, array $offDayContext): DailyAttendanceStatusData
    {
        return DailyAttendanceStatusData::fromArray([
            'user_id' => $user->id,
            'date' => $date,
            'status' => 'off_day',
            'label' => $offDayContext['label'] ?? 'Hari libur',
            'reason' => $offDayContext['reason'] ?? 'The selected date is a non-working day.',
        ]);
    }

    private function findAttendanceForDate(int $userId, Carbon $date): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date->toDateString())
            ->first();
    }

    private function findApprovedLeaveForDate(int $userId, Carbon $date): ?Leave
    {
        return Leave::query()
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $date->toDateString())
            ->whereDate('date_end', '>=', $date->toDateString())
            ->first();
    }

    private function findHolidayForDate(Carbon $date): ?Holiday
    {
        return $this->holidayDateService->findHolidayForDate($date);
    }

    private function resolveFromAttendance(
        Attendance $attendance,
        Carbon $date,
        ?Leave $leave = null,
    ): DailyAttendanceStatusData {
        $reason = $attendance->suspicious_reason;

        if ($leave !== null) {
            $reason = $this->appendReason(
                $reason,
                'Approved leave exists on the same date as an attendance record.'
            );

            Log::warning('Attendance and approved leave found on the same date.', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'work_date' => $date->toDateString(),
                'leave_id' => $leave->id,
            ]);
        }

        $hasCheckIn = $attendance->check_in_at !== null;
        $hasCheckOut = $attendance->check_out_at !== null;

        if ($hasCheckIn && $hasCheckOut) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $attendance->user_id,
                'date' => $date,
                'status' => 'complete',
                'label' => 'Absensi lengkap',
                'attendance_id' => $attendance->id,
                'check_in_at' => $attendance->check_in_at,
                'check_out_at' => $attendance->check_out_at,
                'is_late' => (int) ($attendance->late_minutes ?? 0) > 0,
                'is_early_leave' => (int) ($attendance->early_leave_minutes ?? 0) > 0,
                'is_suspicious' => (bool) $attendance->is_suspicious,
                'reason' => $reason,
            ]);
        }

        if ($hasCheckIn && ! $hasCheckOut) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $attendance->user_id,
                'date' => $date,
                'status' => 'checked_in',
                'label' => 'Sudah check-in',
                'attendance_id' => $attendance->id,
                'check_in_at' => $attendance->check_in_at,
                'check_out_at' => null,
                'is_late' => (int) ($attendance->late_minutes ?? 0) > 0,
                'is_early_leave' => false,
                'is_suspicious' => (bool) $attendance->is_suspicious,
                'reason' => $reason,
            ]);
        }

        Log::warning('Inconsistent attendance record detected while resolving daily status.', [
            'attendance_id' => $attendance->id,
            'user_id' => $attendance->user_id,
            'work_date' => $date->toDateString(),
            'record_status' => $attendance->record_status?->value ?? null,
            'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
            'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
        ]);

        return DailyAttendanceStatusData::fromArray([
            'user_id' => $attendance->user_id,
            'date' => $date,
            'status' => 'absent',
            'label' => 'Data absensi tidak konsisten',
            'attendance_id' => $attendance->id,
            'check_in_at' => $attendance->check_in_at,
            'check_out_at' => $attendance->check_out_at,
            'is_late' => (int) ($attendance->late_minutes ?? 0) > 0,
            'is_early_leave' => (int) ($attendance->early_leave_minutes ?? 0) > 0,
            'is_suspicious' => true,
            'reason' => $this->appendReason(
                $reason,
                'Attendance record exists but does not have a valid check-in/check-out combination.'
            ),
        ]);
    }

    private function resolveWithoutAttendance(
        User $user,
        Carbon $date,
        ?Leave $leave = null,
    ): DailyAttendanceStatusData {
        if ($leave !== null) {
            $leaveReason = trim((string) $leave->reason);

            return DailyAttendanceStatusData::fromArray([
                'user_id' => $user->id,
                'date' => $date,
                'status' => 'on_leave',
                'label' => 'Sedang cuti',
                'reason' => $leaveReason !== ''
                    ? 'Approved leave: ' . $leaveReason
                    : 'Approved leave exists for this date.',
            ]);
        }

        if ($this->hasPassedAbsenceThreshold($user, $date)) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $user->id,
                'date' => $date,
                'status' => 'absent',
                'label' => 'Tidak hadir',
                'reason' => 'No attendance record was found after the absence threshold passed.',
            ]);
        }

        return DailyAttendanceStatusData::fromArray([
            'user_id' => $user->id,
            'date' => $date,
            'status' => 'not_checked_in_yet',
            'label' => 'Belum check-in',
            'reason' => 'No attendance record has been found yet and the absence threshold has not passed.',
        ]);
    }

    private function hasPassedAbsenceThreshold(User $user, Carbon $date): bool
    {
        $date = $this->normalizeDate($date);
        $today = now(self::DEFAULT_TIMEZONE)->startOfDay();

        if ($date->lt($today)) {
            return true;
        }

        if ($date->gt($today)) {
            return false;
        }

        $policy = $this->attendancePolicyService->getPolicyForUser($user->id, $date);

        $workStart = Carbon::parse(
            $date->toDateString() . ' ' . $policy->workStartTime,
            $policy->timezone ?? self::DEFAULT_TIMEZONE
        );

        $absenceThreshold = $workStart->copy()->addMinutes((int) $policy->lateToleranceMinutes);

        return now($policy->timezone ?? self::DEFAULT_TIMEZONE)->greaterThan($absenceThreshold);
    }

    private function appendReason(?string $baseReason, string $extraReason): string
    {
        $baseReason = trim((string) $baseReason);
        $extraReason = trim($extraReason);

        if ($baseReason === '') {
            return $extraReason;
        }

        return $baseReason . ' ' . $extraReason;
    }
}
