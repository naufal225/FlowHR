<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Exceptions\Attendance\AttendanceException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TeamAttendanceOverviewService
{
    public function __construct(
        private readonly AttendanceDailyStatusResolverService $dailyStatusResolverService,
        private readonly AttendanceUiService $attendanceUiService,
        private readonly TeamAttendanceQueryService $teamAttendanceQueryService,
    ) {}

    public function build(int $leaderId, ?int $officeLocationId, Carbon $date, string $quickFilter = 'all'): array
    {
        $employees = $this->teamAttendanceQueryService->subordinateEmployees($leaderId, $officeLocationId);

        $rows = $employees->map(function (User $employee) use ($date): array {
            try {
                $status = $this->dailyStatusResolverService->resolveForUser($employee, $date);

                return $this->attendanceUiService->makeMonitorRow($employee, $status);
            } catch (AttendanceException $exception) {
                return $this->attendanceUiService->makeMonitorErrorRow($employee, $exception->getMessage());
            }
        });

        return [
            'employees' => $employees,
            'rows' => $this->applyQuickFilter($rows, $quickFilter),
            'all_rows' => $rows,
            'stats' => [
                'total' => $rows->count(),
                'checked_in' => $rows->filter(fn (array $row) => $row['has_check_in'])->count(),
                'late' => $rows->filter(fn (array $row) => $row['is_late'])->count(),
                'not_checked_in' => $rows->filter(fn (array $row) => in_array($row['status_key'], ['not_checked_in', 'absent'], true))->count(),
                'complete' => $rows->filter(fn (array $row) => $row['status_key'] === 'complete')->count(),
                'suspicious' => $rows->filter(fn (array $row) => $row['is_suspicious'] || $row['status_key'] === 'config_issue')->count(),
            ],
            'priority_issues' => $rows
                ->filter(fn (array $row) => $row['is_suspicious']
                    || $row['is_late']
                    || in_array($row['status_key'], ['not_checked_in', 'absent', 'config_issue'], true))
                ->take(6)
                ->values(),
        ];
    }

    private function applyQuickFilter(Collection $rows, string $quickFilter): Collection
    {
        return match ($quickFilter) {
            'checked_in' => $rows->filter(fn (array $row) => $row['has_check_in'])->values(),
            'late' => $rows->filter(fn (array $row) => $row['is_late'])->values(),
            'not_checked_in' => $rows->filter(fn (array $row) => in_array($row['status_key'], ['not_checked_in', 'absent'], true))->values(),
            'suspicious' => $rows->filter(fn (array $row) => $row['is_suspicious'] || $row['status_key'] === 'config_issue')->values(),
            default => $rows->values(),
        };
    }
}
