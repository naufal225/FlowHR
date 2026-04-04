<?php

declare(strict_types=1);

namespace App\Services\Leave;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use Carbon\Carbon;
use Throwable;

class MobileLeavePageService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private readonly AttendanceDailyStatusResolverService $attendanceDailyStatusResolverService,
    ) {}

    public function buildForUser(User $user, int $page = 1, int $perPage = 10, ?Carbon $now = null): array
    {
        $timezone = $this->resolveTimezone($user);
        $referenceNow = ($now ?? now($timezone))->copy()->setTimezone($timezone);
        $today = $referenceNow->copy()->startOfDay();

        return [
            'data' => [
                'today_context' => $this->buildTodayContext($user, $today),
                'summary' => $this->buildSummary($user->id, $today),
                'active_or_upcoming_leaves' => $this->buildActiveOrUpcomingLeaves($user->id, $today),
                'history' => $this->buildHistory($user->id, $page, $perPage),
            ],
            'meta' => [
                'server_time' => $referenceNow->toIso8601String(),
                'timezone' => $timezone,
            ],
        ];
    }

    private function buildTodayContext(User $user, Carbon $today): array
    {
        $approvedLeave = $this->findApprovedLeaveForDate((int) $user->id, $today);
        $attendance = $approvedLeave === null
            ? $this->findAttendanceForDate((int) $user->id, $today)
            : null;

        ['status' => $attendanceStatus, 'label' => $attendanceStatusLabel, 'note' => $attendanceNote] =
            $this->resolveTodayAttendanceContext($user, $today, $approvedLeave, $attendance);

        return [
            'date' => $today->toDateString(),
            'day_name' => $today->format('l'),
            'attendance_status' => $attendanceStatus,
            'attendance_status_label' => $attendanceStatusLabel,
            'attendance_note' => $attendanceNote,
            'is_working_day' => ! $today->isWeekend(),
            'leave' => $approvedLeave ? $this->transformLeaveSummary($approvedLeave) : null,
            'attendance' => $attendance ? $this->transformAttendanceContext($attendance) : null,
        ];
    }

    private function resolveTodayAttendanceContext(
        User $user,
        Carbon $today,
        ?Leave $approvedLeave,
        ?Attendance $attendance,
    ): array {
        if ($approvedLeave !== null) {
            return [
                'status' => 'on_leave',
                'label' => 'Sedang Cuti',
                'note' => 'Anda tidak perlu check-in hari ini.',
            ];
        }

        if ($attendance !== null) {
            if ($attendance->check_in_at !== null && $attendance->check_out_at !== null) {
                return [
                    'status' => 'complete',
                    'label' => 'Absensi Lengkap',
                    'note' => 'Absensi hari ini sudah lengkap.',
                ];
            }

            if ($attendance->check_in_at !== null) {
                return [
                    'status' => 'checked_in',
                    'label' => 'Sudah Check-in',
                    'note' => 'Anda sudah check-in, jangan lupa check-out.',
                ];
            }

            return [
                'status' => 'suspicious',
                'label' => 'Perlu Review',
                'note' => 'Data absensi hari ini perlu ditinjau.',
            ];
        }

        try {
            $statusData = $this->attendanceDailyStatusResolverService->resolveForUser($user, $today);
            $status = (string) ($statusData->status ?? 'not_checked_in_yet');

            return [
                'status' => $status,
                'label' => $this->mapAttendanceStatusLabel($status, $statusData->label),
                'note' => $this->mapAttendanceStatusNote($status, $statusData->reason),
            ];
        } catch (Throwable) {
            return [
                'status' => 'unknown',
                'label' => 'Status Tidak Tersedia',
                'note' => 'Konteks absensi hari ini tidak tersedia.',
            ];
        }
    }

    private function buildSummary(int $userId, Carbon $today): array
    {
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $approvedLeavesInMonth = Leave::query()
            ->select(['id', 'date_start', 'date_end'])
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $monthEnd->toDateString())
            ->whereDate('date_end', '>=', $monthStart->toDateString())
            ->get();

        $approvedLeaveDaysThisMonth = (int) $approvedLeavesInMonth->sum(function (Leave $leave) use ($monthStart, $monthEnd): int {
            return $this->calculateOverlapDaysInclusive($leave->date_start, $leave->date_end, $monthStart, $monthEnd);
        });

        $activeLeaveCount = (int) Leave::query()
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $today->toDateString())
            ->whereDate('date_end', '>=', $today->toDateString())
            ->count();

        $upcomingLeaveCount = (int) Leave::query()
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '>', $today->toDateString())
            ->count();

        return [
            'approved_leave_days_this_month' => $approvedLeaveDaysThisMonth,
            'approved_leave_requests_this_month' => (int) $approvedLeavesInMonth->count(),
            'active_leave_count' => $activeLeaveCount,
            'upcoming_leave_count' => $upcomingLeaveCount,
        ];
    }

    private function buildActiveOrUpcomingLeaves(int $userId, Carbon $today): array
    {
        return Leave::query()
            ->select(['id', 'date_start', 'date_end', 'reason', 'status_1', 'approved_date', 'created_at'])
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_end', '>=', $today->toDateString())
            ->orderBy('date_start')
            ->orderBy('id')
            ->get()
            ->map(function (Leave $leave) use ($today): array {
                $isActive = $leave->date_start !== null
                    && $leave->date_end !== null
                    && $leave->date_start->lte($today)
                    && $leave->date_end->gte($today);

                return [
                    ...$this->transformLeaveSummary($leave),
                    'approved_date' => $leave->approved_date?->toDateTimeString(),
                    'created_at' => $leave->created_at?->toDateTimeString(),
                    'is_active' => $isActive,
                    'is_upcoming' => ! $isActive,
                ];
            })
            ->values()
            ->all();
    }

    private function buildHistory(int $userId, int $page, int $perPage): array
    {
        $paginator = Leave::query()
            ->select(['id', 'date_start', 'date_end', 'reason', 'status_1', 'approved_date', 'created_at'])
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->paginate(
                perPage: $perPage,
                columns: ['*'],
                pageName: 'page',
                page: $page,
            );

        $totalItems = (int) $paginator->total();
        $totalPages = $totalItems === 0 ? 0 : (int) $paginator->lastPage();
        $currentPage = (int) $paginator->currentPage();

        return [
            'items' => collect($paginator->items())
                ->map(fn (Leave $leave): array => [
                    ...$this->transformLeaveSummary($leave),
                    'approved_date' => $leave->approved_date?->toDateTimeString(),
                    'created_at' => $leave->created_at?->toDateTimeString(),
                ])
                ->values()
                ->all(),
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => (int) $paginator->perPage(),
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_more' => $totalPages > 0 && $currentPage < $totalPages,
            ],
        ];
    }

    private function transformLeaveSummary(Leave $leave): array
    {
        $status = (string) ($leave->status_1 ?? 'pending');

        return [
            'id' => (int) $leave->id,
            'status' => $status,
            'status_label' => $this->mapLeaveStatusLabel($status),
            'date_start' => $leave->date_start?->toDateString(),
            'date_end' => $leave->date_end?->toDateString(),
            'duration_days' => $this->calculateDaysInclusive($leave->date_start, $leave->date_end),
            'reason' => $leave->reason,
        ];
    }

    private function transformAttendanceContext(Attendance $attendance): array
    {
        return [
            'id' => (int) $attendance->id,
            'work_date' => $attendance->work_date?->toDateString(),
            'check_in_at' => $attendance->check_in_at?->toDateTimeString(),
            'check_out_at' => $attendance->check_out_at?->toDateTimeString(),
            'record_status' => $attendance->record_status?->value,
        ];
    }

    private function mapLeaveStatusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu Persetujuan',
        };
    }

    private function mapAttendanceStatusLabel(string $status, ?string $fallback): string
    {
        return match ($status) {
            'on_leave' => 'Sedang Cuti',
            'off_day' => 'Hari Libur',
            'complete' => 'Absensi Lengkap',
            'checked_in', 'ongoing' => 'Sudah Check-in',
            'absent' => 'Tidak Hadir',
            'suspicious' => 'Perlu Review',
            'not_checked_in_yet' => 'Belum Check-in',
            default => $fallback ?: 'Status Tidak Tersedia',
        };
    }

    private function mapAttendanceStatusNote(string $status, ?string $fallback): string
    {
        return match ($status) {
            'on_leave' => 'Anda tidak perlu check-in hari ini.',
            'off_day' => 'Hari ini bukan hari kerja.',
            'complete' => 'Absensi hari ini sudah lengkap.',
            'checked_in', 'ongoing' => 'Anda sudah check-in, jangan lupa check-out.',
            'absent' => 'Anda belum memiliki absensi hari ini.',
            'suspicious' => 'Data absensi hari ini perlu ditinjau.',
            'not_checked_in_yet' => 'Anda belum check-in hari ini.',
            default => $fallback ?: 'Konteks absensi hari ini belum tersedia.',
        };
    }

    private function calculateOverlapDaysInclusive(?Carbon $start, ?Carbon $end, Carbon $rangeStart, Carbon $rangeEnd): int
    {
        if ($start === null || $end === null) {
            return 0;
        }

        $effectiveStart = $start->copy()->startOfDay()->max($rangeStart->copy()->startOfDay());
        $effectiveEnd = $end->copy()->startOfDay()->min($rangeEnd->copy()->startOfDay());

        if ($effectiveStart->gt($effectiveEnd)) {
            return 0;
        }

        return (int) $effectiveStart->diffInDays($effectiveEnd) + 1;
    }

    private function calculateDaysInclusive(?Carbon $start, ?Carbon $end): int
    {
        if ($start === null || $end === null) {
            return 0;
        }

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        return (int) $start->diffInDays($end) + 1;
    }

    private function findApprovedLeaveForDate(int $userId, Carbon $date): ?Leave
    {
        return Leave::query()
            ->select(['id', 'employee_id', 'date_start', 'date_end', 'reason', 'status_1', 'approved_date', 'created_at'])
            ->where('employee_id', $userId)
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $date->toDateString())
            ->whereDate('date_end', '>=', $date->toDateString())
            ->orderBy('date_start')
            ->first();
    }

    private function findAttendanceForDate(int $userId, Carbon $date): ?Attendance
    {
        return Attendance::query()
            ->select(['id', 'user_id', 'work_date', 'check_in_at', 'check_out_at', 'record_status'])
            ->where('user_id', $userId)
            ->whereDate('work_date', $date->toDateString())
            ->first();
    }

    private function resolveTimezone(User $user): string
    {
        $user->loadMissing('officeLocation:id,timezone');

        return (string) ($user->officeLocation?->timezone ?: self::DEFAULT_TIMEZONE);
    }
}
