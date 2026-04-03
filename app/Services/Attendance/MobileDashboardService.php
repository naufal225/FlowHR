<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Data\Attendance\DailyAttendanceStatusData;
use App\Data\Attendance\MobileDashboardData;
use App\Exceptions\Attendance\AttendanceException;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MobileDashboardService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';
    private const RECENT_ATTENDANCE_LIMIT = 5;
    private const LOCATION_STATUS_VALID = 'valid';
    private const LOCATION_STATUS_INVALID = 'invalid';
    private const LOCATION_STATUS_SUSPICIOUS = 'suspicious';
    private const ACCURACY_LEVEL_GOOD = 'good';
    private const ACCURACY_LEVEL_FAIR = 'fair';
    private const ACCURACY_LEVEL_POOR = 'poor';
    private const ACCURACY_GOOD_MAX_METER = 50.0;
    private const ACCURACY_FAIR_MAX_METER = 100.0;

    public function __construct(
        private readonly AttendanceDailyStatusResolverService $dailyStatusResolverService,
        private readonly AttendancePolicyService $attendancePolicyService,
        private readonly AttendanceLocationValidationService $attendanceLocationValidationService,
    ) {}

    public function buildForUser(User $user, ?Carbon $now = null): MobileDashboardData
    {
        $now ??= now(self::DEFAULT_TIMEZONE);
        $today = $now->copy()->setTimezone(self::DEFAULT_TIMEZONE)->startOfDay();

        $user->loadMissing([
            'officeLocation:id,name,address,radius_meter,timezone',
        ]);

        $todayAttendance = $this->findTodayAttendance($user->id, $today);
        $approvedLeave = $this->findApprovedLeaveForDate($user->id, $today);

        $todayStatus = $this->resolveTodayStatus(
            user: $user,
            date: $today,
            attendance: $todayAttendance,
            approvedLeave: $approvedLeave,
        );

        ['policy' => $policy, 'warning' => $policyWarning] = $this->resolvePolicy($user->id, $today);

        $hasPendingCorrection = $this->hasPendingCorrection($user->id, $todayAttendance?->id);
        $recentAttendances = $this->buildRecentAttendances($user->id, self::RECENT_ATTENDANCE_LIMIT);

        $actionState = $this->buildActionState($todayStatus, $todayAttendance, $policy, $now);
        $locationReadiness = $this->buildLocationReadiness($policy, $todayAttendance);
        $dayContext = $this->buildDayContext($todayStatus);
        $alerts = $this->buildAlerts(
            statusData: $todayStatus,
            attendance: $todayAttendance,
            approvedLeave: $approvedLeave,
            hasPendingCorrection: $hasPendingCorrection,
            actionState: $actionState,
            policyWarning: $policyWarning,
        );

        return new MobileDashboardData(
            user: $this->buildUserSection($user),
            todayStatus: $this->buildTodayStatusSection($todayStatus),
            attendanceSummary: $this->buildAttendanceSummary(
                attendance: $todayAttendance,
                userId: $user->id,
                now: $now,
                policy: $policy,
                recentAttendances: $recentAttendances,
            ),
            actionState: $actionState,
            policy: $this->buildPolicySection($policy),
            locationReadiness: $locationReadiness,
            dayContext: $dayContext,
            recentAttendances: $recentAttendances,
            alerts: $alerts,
        );
    }

    private function buildUserSection(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'office_location_id' => $user->office_location_id,
            'office_location_name' => $user->officeLocation?->name,
            'active_role' => method_exists($user, 'getActiveRole') ? ($user->getActiveRole() ?: 'employee') : 'employee',
        ];
    }

    private function buildTodayStatusSection(DailyAttendanceStatusData $statusData): array
    {
        return [
            'date' => $statusData->date->toDateString(),
            'status' => $statusData->status,
            'label' => $statusData->label,
            'attendance_id' => $statusData->attendanceId,
            'check_in_at' => $statusData->checkInAt?->toDateTimeString(),
            'check_out_at' => $statusData->checkOutAt?->toDateTimeString(),
            'is_late' => $statusData->isLate,
            'is_early_leave' => $statusData->isEarlyLeave,
            'is_suspicious' => $statusData->isSuspicious,
            'reason' => $statusData->reason,
        ];
    }

    private function buildAttendanceSummary(
        ?Attendance $attendance,
        int $userId,
        Carbon $now,
        ?AttendancePolicyData $policy,
        array $recentAttendances,
    ): array
    {
        $timezone = $policy?->timezone ?? self::DEFAULT_TIMEZONE;
        $averageStartMinutes = $this->resolveAverageStartMinutes($userId, $now, $timezone);
        $summary = [];

        if ($attendance === null) {
            $summary = [
                'record_status' => null,
                'record_status_label' => null,
                'check_in_status' => null,
                'check_in_status_label' => null,
                'check_out_status' => null,
                'check_out_status_label' => null,
                'late_minutes' => null,
                'early_leave_minutes' => null,
                'overtime_minutes' => null,
                'notes' => null,
            ];
        } else {
            $summary = [
                'record_status' => $attendance->record_status?->value,
                'record_status_label' => $attendance->record_status?->label(),
                'check_in_status' => $attendance->check_in_status?->value,
                'check_in_status_label' => $attendance->check_in_status?->label(),
                'check_out_status' => $attendance->check_out_status?->value,
                'check_out_status_label' => $attendance->check_out_status?->label(),
                'late_minutes' => (int) ($attendance->late_minutes ?? 0),
                'early_leave_minutes' => (int) ($attendance->early_leave_minutes ?? 0),
                'overtime_minutes' => (int) ($attendance->overtime_minutes ?? 0),
                'notes' => $attendance->notes,
            ];
        }

        return array_merge($summary, [
            'avg_start' => $this->buildAverageStartSummary(
                averageStartMinutes: $averageStartMinutes,
                policy: $policy,
                now: $now,
            ),
            'this_week' => $this->buildThisWeekSummary(
                userId: $userId,
                now: $now,
                timezone: $timezone,
            ),
            'recent_activity' => $this->buildRecentActivitySummary(
                recentAttendances: $recentAttendances,
                now: $now,
                timezone: $timezone,
            ),
            'insight' => $this->buildStartTimeInsight(
                averageStartMinutes: $averageStartMinutes,
                todayAttendance: $attendance,
                now: $now,
                timezone: $timezone,
            ),
        ]);
    }

    private function resolveAverageStartMinutes(int $userId, Carbon $now, string $timezone): ?int
    {
        $records = Attendance::query()
            ->select(['check_in_at', 'work_date'])
            ->where('user_id', $userId)
            ->whereNotNull('check_in_at')
            ->whereDate('work_date', '>=', $now->copy()->setTimezone($timezone)->subDays(30)->toDateString())
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $totalMinutes = 0;
        $sampleSize = 0;

        foreach ($records as $record) {
            if (! ($record->check_in_at instanceof Carbon)) {
                continue;
            }

            $localCheckIn = $record->check_in_at->copy()->setTimezone($timezone);
            $totalMinutes += ($localCheckIn->hour * 60) + $localCheckIn->minute;
            $sampleSize++;
        }

        if ($sampleSize === 0) {
            return null;
        }

        return (int) round($totalMinutes / $sampleSize);
    }

    private function buildAverageStartSummary(
        ?int $averageStartMinutes,
        ?AttendancePolicyData $policy,
        Carbon $now,
    ): array {
        if ($averageStartMinutes === null) {
            return [
                'time' => null,
                'delta_from_shift_start_minutes' => null,
            ];
        }

        $deltaFromShiftStart = null;

        if ($policy !== null) {
            try {
                $schedule = $this->attendancePolicyService->resolveWorkSchedule($policy, $now);
                $shiftStartMinutes = ((int) $schedule['work_start_at']->hour * 60) + (int) $schedule['work_start_at']->minute;
                $deltaFromShiftStart = $averageStartMinutes - $shiftStartMinutes;
            } catch (\Throwable) {
                $deltaFromShiftStart = null;
            }
        }

        return [
            'time' => $this->formatMinutesAsClock($averageStartMinutes),
            'delta_from_shift_start_minutes' => $deltaFromShiftStart,
        ];
    }

    private function buildThisWeekSummary(int $userId, Carbon $now, string $timezone): array
    {
        $weekStart = $now->copy()->setTimezone($timezone)->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = $now->copy()->setTimezone($timezone)->endOfWeek(Carbon::SUNDAY)->toDateString();

        $records = Attendance::query()
            ->select(['check_in_at', 'check_out_at', 'work_date'])
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$weekStart, $weekEnd])
            ->whereNotNull('check_in_at')
            ->whereNotNull('check_out_at')
            ->get();

        $totalMinutes = 0;

        foreach ($records as $record) {
            if (! ($record->check_in_at instanceof Carbon) || ! ($record->check_out_at instanceof Carbon)) {
                continue;
            }

            $checkInAt = $record->check_in_at->copy()->setTimezone($timezone);
            $checkOutAt = $record->check_out_at->copy()->setTimezone($timezone);
            $workedMinutes = (int) $checkInAt->diffInMinutes($checkOutAt, false);

            $totalMinutes += max(0, $workedMinutes);
        }

        return [
            'total_minutes' => $totalMinutes,
            'total_hours' => round($totalMinutes / 60, 1),
        ];
    }

    private function buildRecentActivitySummary(array $recentAttendances, Carbon $now, string $timezone): array
    {
        $activities = [];

        foreach ($recentAttendances as $attendance) {
            if (! is_array($attendance)) {
                continue;
            }

            $label = $this->resolveRelativeDayLabel($attendance['work_date'] ?? null, $now, $timezone);
            $checkInAt = $attendance['check_in_at'] ?? null;
            $checkOutAt = $attendance['check_out_at'] ?? null;

            if ($checkInAt !== null) {
                $activities[] = [
                    'label' => $label,
                    'type' => 'clock_in',
                    'title' => 'Clock In',
                    'at' => $checkInAt,
                ];
            }

            if ($checkOutAt !== null) {
                $activities[] = [
                    'label' => $label,
                    'type' => 'clock_out',
                    'title' => 'Clock Out',
                    'at' => $checkOutAt,
                ];
            }

            if (count($activities) >= 4) {
                break;
            }
        }

        return array_values(array_slice($activities, 0, 4));
    }

    private function buildStartTimeInsight(
        ?int $averageStartMinutes,
        ?Attendance $todayAttendance,
        Carbon $now,
        string $timezone,
    ): array {
        if ($averageStartMinutes === null) {
            return [
                'type' => 'neutral',
                'minutes' => null,
                'message' => 'Typical start time is being calculated from your recent attendance history.',
            ];
        }

        $referenceMinutes = $this->resolveReferenceStartMinutes($todayAttendance, $now, $timezone);
        $difference = $averageStartMinutes - $referenceMinutes;
        $absoluteMinutes = abs($difference);

        if ($absoluteMinutes <= 1) {
            return [
                'type' => 'on_track',
                'minutes' => 0,
                'message' => 'You are right on your typical start time today.',
            ];
        }

        if ($difference > 0) {
            return [
                'type' => 'ahead',
                'minutes' => $absoluteMinutes,
                'message' => "You're {$absoluteMinutes} minutes ahead of your typical start time. Great start to the morning!",
            ];
        }

        return [
            'type' => 'behind',
            'minutes' => $absoluteMinutes,
            'message' => "You're {$absoluteMinutes} minutes behind your typical start time. Stay focused and check in safely.",
        ];
    }

    private function resolveReferenceStartMinutes(?Attendance $attendance, Carbon $now, string $timezone): int
    {
        if ($attendance?->check_in_at instanceof Carbon) {
            $reference = $attendance->check_in_at->copy()->setTimezone($timezone);
        } else {
            $reference = $now->copy()->setTimezone($timezone);
        }

        return ($reference->hour * 60) + $reference->minute;
    }

    private function resolveRelativeDayLabel(?string $workDate, Carbon $now, string $timezone): string
    {
        if ($workDate === null) {
            return '-';
        }

        try {
            $targetDate = Carbon::parse($workDate, $timezone)->startOfDay();
            $today = $now->copy()->setTimezone($timezone)->startOfDay();

            if ($targetDate->equalTo($today)) {
                return 'Today';
            }

            if ($targetDate->equalTo($today->copy()->subDay())) {
                return 'Yesterday';
            }

            return $targetDate->translatedFormat('D, d M');
        } catch (\Throwable) {
            return $workDate;
        }
    }

    private function formatMinutesAsClock(int $minutesFromMidnight): string
    {
        $minutesFromMidnight = max(0, min(1439, $minutesFromMidnight));
        $hour = intdiv($minutesFromMidnight, 60);
        $minute = $minutesFromMidnight % 60;

        return sprintf('%02d:%02d', $hour, $minute);
    }

    private function buildActionState(
        DailyAttendanceStatusData $statusData,
        ?Attendance $attendance,
        ?AttendancePolicyData $policy,
        Carbon $now,
    ): array {
        $nextAction = 'none';
        $canCheckIn = false;
        $canCheckOut = false;
        $disabledReason = null;

        if ($statusData->status === 'off_day') {
            $disabledReason = 'Today is configured as an off day.';
        } elseif ($statusData->status === 'on_leave') {
            $disabledReason = 'You are on approved leave today.';
        } elseif ($attendance === null) {
            if ($policy === null) {
                $disabledReason = 'Attendance policy is not available for your profile.';
            } elseif (! $this->attendancePolicyService->isWithinCheckInWindow($policy, $now)) {
                $disabledReason = 'Check-in window is currently closed.';
            } else {
                $canCheckIn = true;
                $nextAction = 'check_in';
            }
        } else {
            if ($attendance->check_in_at === null) {
                $disabledReason = 'Attendance record is incomplete and check-in timestamp is missing.';
            } elseif ($attendance->check_out_at !== null) {
                $disabledReason = 'Check-out has already been recorded for today.';
            } elseif ($policy === null) {
                $disabledReason = 'Attendance policy is not available for check-out validation.';
            } elseif (! $this->attendancePolicyService->isWithinCheckOutWindow($policy, $now)) {
                $disabledReason = 'Check-out window is currently closed.';
            } else {
                $canCheckOut = true;
                $nextAction = 'check_out';
            }
        }

        return [
            'next_action' => $nextAction,
            'can_check_in' => $canCheckIn,
            'can_check_out' => $canCheckOut,
            'action_disabled_reason' => $disabledReason,
        ];
    }

    private function buildPolicySection(?AttendancePolicyData $policy): array
    {
        return [
            'work_start_time' => $policy?->workStartTime,
            'work_end_time' => $policy?->workEndTime,
            'late_tolerance_minutes' => $policy?->lateToleranceMinutes,
            'timezone' => $policy?->timezone,
        ];
    }

    private function buildLocationReadiness(?AttendancePolicyData $policy, ?Attendance $attendance): array
    {
        $readiness = [
            'office_radius_meter' => $policy?->allowedRadiusMeter,
            'min_location_accuracy_meter' => $policy?->minLocationAccuracyMeter,
            'gps_required' => true,
            'last_known_distance_meter' => null,
            'last_known_accuracy_meter' => null,
            'location_status' => null,
            'location_reason' => null,
            'has_location_fix' => false,
            'accuracy_meter' => null,
            'distance_meter' => null,
            'status' => null,
            'status_label' => $this->resolveLocationStatusLabel(null),
            'accuracy_level' => null,
            'accuracy_label' => $this->resolveAccuracyLabel(null),
            'reason' => null,
            'is_valid' => null,
            'is_suspicious' => null,
        ];

        if ($attendance === null) {
            return $readiness;
        }

        $lastKnown = $this->extractLastKnownLocation($attendance);

        if ($lastKnown['latitude'] === null || $lastKnown['longitude'] === null) {
            return $readiness;
        }

        $accuracyMeter = $lastKnown['accuracy_meter'] !== null ? (float) $lastKnown['accuracy_meter'] : null;

        $readiness['has_location_fix'] = true;
        $readiness = $this->withLocationAccuracy($readiness, $accuracyMeter);

        if ($policy === null) {
            return $this->withLocationStatus(
                readiness: $readiness,
                status: null,
                reason: 'Attendance policy is not available to validate latest location.',
            );
        }

        try {
            $result = $this->attendanceLocationValidationService->validateForPolicy(
                policy: $policy,
                latitude: (float) $lastKnown['latitude'],
                longitude: (float) $lastKnown['longitude'],
                accuracyMeter: $accuracyMeter,
            );

            $readiness['last_known_distance_meter'] = $result->distanceMeter;
            $readiness['distance_meter'] = $result->distanceMeter;
            $readiness = $this->withLocationAccuracy($readiness, $result->accuracyMeter);

            return $this->withLocationStatus(
                readiness: $readiness,
                status: $result->isSuspicious ? self::LOCATION_STATUS_SUSPICIOUS : self::LOCATION_STATUS_VALID,
                reason: $result->reason ? $this->humanizeReason($result->reason) : null,
            );
        } catch (AttendanceException $exception) {
            $context = $exception->getContext();
            $distanceMeter = isset($context['distance_meter']) ? (float) $context['distance_meter'] : null;

            $readiness['last_known_distance_meter'] = $distanceMeter;
            $readiness['distance_meter'] = $distanceMeter;
            $readiness = $this->withLocationAccuracy($readiness, $accuracyMeter);

            return $this->withLocationStatus(
                readiness: $readiness,
                status: self::LOCATION_STATUS_INVALID,
                reason: $exception->getMessage(),
            );
        }
    }

    private function withLocationAccuracy(array $readiness, ?float $accuracyMeter): array
    {
        $accuracyLevel = $this->resolveAccuracyLevel($accuracyMeter);

        $readiness['last_known_accuracy_meter'] = $accuracyMeter;
        $readiness['accuracy_meter'] = $accuracyMeter;
        $readiness['accuracy_level'] = $accuracyLevel;
        $readiness['accuracy_label'] = $this->resolveAccuracyLabel($accuracyMeter);

        return $readiness;
    }

    private function withLocationStatus(array $readiness, ?string $status, ?string $reason): array
    {
        $normalizedStatus = $this->normalizeLocationStatus($status);

        $readiness['location_status'] = $normalizedStatus;
        $readiness['status'] = $normalizedStatus;
        $readiness['status_label'] = $this->resolveLocationStatusLabel($normalizedStatus);
        $readiness['location_reason'] = $reason;
        $readiness['reason'] = $reason;
        $readiness['is_valid'] = match ($normalizedStatus) {
            self::LOCATION_STATUS_VALID => true,
            self::LOCATION_STATUS_INVALID, self::LOCATION_STATUS_SUSPICIOUS => false,
            default => null,
        };
        $readiness['is_suspicious'] = match ($normalizedStatus) {
            self::LOCATION_STATUS_SUSPICIOUS => true,
            self::LOCATION_STATUS_VALID, self::LOCATION_STATUS_INVALID => false,
            default => null,
        };

        return $readiness;
    }

    private function normalizeLocationStatus(?string $status): ?string
    {
        return match ($status) {
            self::LOCATION_STATUS_VALID,
            self::LOCATION_STATUS_INVALID,
            self::LOCATION_STATUS_SUSPICIOUS => $status,
            default => null,
        };
    }

    private function resolveLocationStatusLabel(?string $status): string
    {
        return match ($status) {
            self::LOCATION_STATUS_VALID => 'Dalam Radius',
            self::LOCATION_STATUS_INVALID => 'Di Luar Radius',
            self::LOCATION_STATUS_SUSPICIOUS => 'Perlu Validasi',
            default => 'Status lokasi tidak tersedia',
        };
    }

    private function resolveAccuracyLevel(?float $accuracyMeter): ?string
    {
        if ($accuracyMeter === null) {
            return null;
        }

        if ($accuracyMeter < self::ACCURACY_GOOD_MAX_METER) {
            return self::ACCURACY_LEVEL_GOOD;
        }

        if ($accuracyMeter <= self::ACCURACY_FAIR_MAX_METER) {
            return self::ACCURACY_LEVEL_FAIR;
        }

        return self::ACCURACY_LEVEL_POOR;
    }

    private function resolveAccuracyLabel(?float $accuracyMeter): string
    {
        return match ($this->resolveAccuracyLevel($accuracyMeter)) {
            self::ACCURACY_LEVEL_GOOD => 'GPS Baik',
            self::ACCURACY_LEVEL_FAIR => 'GPS Cukup',
            self::ACCURACY_LEVEL_POOR => 'GPS Lemah',
            default => 'Akurasi tidak tersedia',
        };
    }

    private function buildDayContext(DailyAttendanceStatusData $statusData): array
    {
        $message = match ($statusData->status) {
            'off_day' => 'Today is a non-working day.',
            'on_leave' => 'You are on approved leave today.',
            default => $statusData->reason ?: ($statusData->label ?: 'No additional day context.'),
        };

        return [
            'is_off_day' => $statusData->status === 'off_day',
            'is_on_leave' => $statusData->status === 'on_leave',
            'message' => $message,
        ];
    }

    private function buildRecentAttendances(int $userId, int $limit): array
    {
        return Attendance::query()
            ->select([
                'id',
                'work_date',
                'check_in_at',
                'check_out_at',
                'record_status',
                'is_suspicious',
            ])
            ->where('user_id', $userId)
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(static function (Attendance $attendance): array {
                return [
                    'id' => $attendance->id,
                    'work_date' => $attendance->work_date?->toDateString(),
                    'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
                    'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
                    'record_status' => $attendance->record_status?->value,
                    'is_suspicious' => (bool) $attendance->is_suspicious,
                ];
            })
            ->values()
            ->all();
    }

    private function buildAlerts(
        DailyAttendanceStatusData $statusData,
        ?Attendance $attendance,
        ?Leave $approvedLeave,
        bool $hasPendingCorrection,
        array $actionState,
        ?string $policyWarning,
    ): array {
        $alerts = [];

        if ($policyWarning !== null) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Attendance Policy Issue',
                'message' => $policyWarning,
            ];
        }

        if ($statusData->status === 'off_day') {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Off Day',
                'message' => 'Today is marked as a non-working day.',
            ];
        }

        if ($statusData->status === 'on_leave') {
            $alerts[] = [
                'type' => 'info',
                'title' => 'On Leave',
                'message' => $approvedLeave?->reason
                    ? 'Approved leave: ' . $approvedLeave->reason
                    : 'You are covered by approved leave for today.',
            ];
        }

        if ($attendance !== null && $approvedLeave !== null) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Attendance-Leave Conflict',
                'message' => 'Approved leave and attendance record exist on the same date.',
            ];
        }

        if ((bool) $statusData->isSuspicious || (bool) ($attendance?->is_suspicious ?? false)) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Suspicious Attendance',
                'message' => $attendance?->suspicious_reason
                    ? $this->humanizeReason($attendance->suspicious_reason)
                    : 'Attendance has been flagged for review.',
            ];
        }

        if ($statusData->status === 'absent') {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Absent Risk',
                'message' => 'No attendance record was found after threshold time.',
            ];
        }

        if ($hasPendingCorrection) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pending Correction',
                'message' => 'You still have a pending attendance correction for today.',
            ];
        }

        if (
            $actionState['next_action'] === 'none'
            && $actionState['action_disabled_reason'] !== null
            && ! in_array($statusData->status, ['off_day', 'on_leave'], true)
        ) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Action Guidance',
                'message' => $actionState['action_disabled_reason'],
            ];
        }

        return array_values(array_slice($alerts, 0, 5));
    }

    private function resolveTodayStatus(
        User $user,
        Carbon $date,
        ?Attendance $attendance,
        ?Leave $approvedLeave,
    ): DailyAttendanceStatusData {
        if ($date->isWeekend()) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $user->id,
                'date' => $date,
                'status' => 'off_day',
                'label' => 'Hari libur',
                'reason' => 'The selected date is a non-working day.',
            ]);
        }

        if ($approvedLeave !== null) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $user->id,
                'date' => $date,
                'status' => 'on_leave',
                'label' => 'Sedang cuti',
                'reason' => 'Approved leave exists for this date.',
            ]);
        }

        if ($attendance !== null) {
            return $this->resolveStatusFromAttendance($attendance, $date);
        }

        return $this->dailyStatusResolverService->resolveForUser($user, $date);
    }

    private function resolveStatusFromAttendance(Attendance $attendance, Carbon $date): DailyAttendanceStatusData
    {
        $isLate = (int) ($attendance->late_minutes ?? 0) > 0;
        $isEarlyLeave = (int) ($attendance->early_leave_minutes ?? 0) > 0;
        $isSuspicious = (bool) ($attendance->is_suspicious ?? false);

        if ($isSuspicious) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $attendance->user_id,
                'date' => $date,
                'status' => 'suspicious',
                'label' => 'Perlu review',
                'attendance_id' => $attendance->id,
                'check_in_at' => $attendance->check_in_at,
                'check_out_at' => $attendance->check_out_at,
                'is_late' => $isLate,
                'is_early_leave' => $isEarlyLeave,
                'is_suspicious' => true,
                'reason' => $attendance->suspicious_reason ?: 'Attendance flagged as suspicious.',
            ]);
        }

        if ($attendance->check_in_at !== null && $attendance->check_out_at !== null) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $attendance->user_id,
                'date' => $date,
                'status' => 'complete',
                'label' => 'Absensi lengkap',
                'attendance_id' => $attendance->id,
                'check_in_at' => $attendance->check_in_at,
                'check_out_at' => $attendance->check_out_at,
                'is_late' => $isLate,
                'is_early_leave' => $isEarlyLeave,
                'is_suspicious' => false,
                'reason' => null,
            ]);
        }

        if ($attendance->check_in_at !== null) {
            return DailyAttendanceStatusData::fromArray([
                'user_id' => $attendance->user_id,
                'date' => $date,
                'status' => 'ongoing',
                'label' => 'Masih berlangsung',
                'attendance_id' => $attendance->id,
                'check_in_at' => $attendance->check_in_at,
                'check_out_at' => $attendance->check_out_at,
                'is_late' => $isLate,
                'is_early_leave' => false,
                'is_suspicious' => false,
                'reason' => 'Check-in exists, but check-out has not been recorded yet.',
            ]);
        }

        return DailyAttendanceStatusData::fromArray([
            'user_id' => $attendance->user_id,
            'date' => $date,
            'status' => 'suspicious',
            'label' => 'Perlu review',
            'attendance_id' => $attendance->id,
            'check_in_at' => $attendance->check_in_at,
            'check_out_at' => $attendance->check_out_at,
            'is_late' => $isLate,
            'is_early_leave' => $isEarlyLeave,
            'is_suspicious' => true,
            'reason' => 'Attendance row exists but check-in timestamp is missing.',
        ]);
    }

    private function findTodayAttendance(int $userId, Carbon $date): ?Attendance
    {
        return Attendance::query()
            ->select([
                'id',
                'user_id',
                'office_location_id',
                'work_date',
                'check_in_at',
                'check_in_latitude',
                'check_in_longitude',
                'check_in_accuracy_meter',
                'check_in_status',
                'check_out_at',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_accuracy_meter',
                'check_out_status',
                'record_status',
                'late_minutes',
                'early_leave_minutes',
                'overtime_minutes',
                'is_suspicious',
                'suspicious_reason',
                'notes',
            ])
            ->where('user_id', $userId)
            ->whereDate('work_date', $date->toDateString())
            ->first();
    }

    private function findApprovedLeaveForDate(int $userId, Carbon $date): ?Leave
    {
        return Leave::query()
            ->select(['id', 'employee_id', 'date_start', 'date_end', 'reason', 'status_1'])
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $date->toDateString())
            ->whereDate('date_end', '>=', $date->toDateString())
            ->first();
    }

    private function hasPendingCorrection(int $userId, ?int $attendanceId): bool
    {
        if ($attendanceId === null) {
            return false;
        }

        return AttendanceCorrection::query()
            ->where('user_id', $userId)
            ->where('attendance_id', $attendanceId)
            ->where('status', 'pending')
            ->exists();
    }

    private function resolvePolicy(int $userId, Carbon $date): array
    {
        try {
            return [
                'policy' => $this->attendancePolicyService->getPolicyForUser($userId, $date),
                'warning' => null,
            ];
        } catch (AttendanceException $exception) {
            return [
                'policy' => null,
                'warning' => $exception->getMessage(),
            ];
        }
    }

    private function extractLastKnownLocation(Attendance $attendance): array
    {
        if ($attendance->check_out_latitude !== null && $attendance->check_out_longitude !== null) {
            return [
                'latitude' => (float) $attendance->check_out_latitude,
                'longitude' => (float) $attendance->check_out_longitude,
                'accuracy_meter' => $attendance->check_out_accuracy_meter !== null
                    ? (float) $attendance->check_out_accuracy_meter
                    : null,
            ];
        }

        if ($attendance->check_in_latitude !== null && $attendance->check_in_longitude !== null) {
            return [
                'latitude' => (float) $attendance->check_in_latitude,
                'longitude' => (float) $attendance->check_in_longitude,
                'accuracy_meter' => $attendance->check_in_accuracy_meter !== null
                    ? (float) $attendance->check_in_accuracy_meter
                    : null,
            ];
        }

        return [
            'latitude' => null,
            'longitude' => null,
            'accuracy_meter' => null,
        ];
    }

    private function humanizeReason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            return null;
        }

        return Str::contains($reason, ' ')
            ? $reason
            : Str::headline(str_replace(['.', '-', '_', '|'], ' ', strtolower($reason)));
    }
}
