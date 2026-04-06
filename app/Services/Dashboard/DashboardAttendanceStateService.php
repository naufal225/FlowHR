<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Data\Attendance\DailyAttendanceStatusData;
use App\Models\Attendance;
use App\Models\User;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceUiService;
use Carbon\Carbon;
use Throwable;

class DashboardAttendanceStateService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private AttendanceDailyStatusResolverService $dailyStatusResolver,
        private AttendanceUiService $attendanceUiService,
    ) {}

    public function forUser(User $user, ?Carbon $date = null): array
    {
        $date = $this->resolveDate($date);

        try {
            $status = $this->dailyStatusResolver->resolveForUser($user, $date);

            return $this->buildViewPayload($status, $date);
        } catch (Throwable $exception) {
            report($exception);

            return $this->fallback(
                $date,
                'Attendance state is temporarily unavailable. Please contact admin.'
            );
        }
    }

    public function fallback(?Carbon $date = null, ?string $description = null): array
    {
        $date = $this->resolveDate($date);
        $badge = $this->attendanceUiService->badgeFromStatus('config_issue');

        return [
            'key' => 'config_issue',
            'badge' => $badge,
            'flags' => [],
            'description' => $description ?: 'Attendance configuration needs attention before this status can be used.',
            'check_in' => '-',
            'check_out' => '-',
            'date_label' => $date->translatedFormat('D, d M Y'),
        ];
    }

    private function buildViewPayload(DailyAttendanceStatusData $status, Carbon $date): array
    {
        $uiState = $this->attendanceUiService->makeDailyStatus($status);
        $statusKey = (string) ($uiState['key'] ?? 'config_issue');
        $flags = is_array($uiState['flags'] ?? null) ? $uiState['flags'] : [];

        return [
            'key' => $statusKey,
            'badge' => $this->attendanceUiService->badgeFromStatus($statusKey),
            'flags' => $this->appendOvertimeFlag($flags, $status->attendanceId),
            'description' => (string) ($uiState['description'] ?? 'Attendance status information is currently unavailable.'),
            'check_in' => (string) ($uiState['check_in'] ?? '-'),
            'check_out' => (string) ($uiState['check_out'] ?? '-'),
            'date_label' => (string) ($uiState['date_label'] ?? $date->translatedFormat('D, d M Y')),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $flags
     * @return array<int, array<string, mixed>>
     */
    private function appendOvertimeFlag(array $flags, ?int $attendanceId): array
    {
        if ($attendanceId === null) {
            return $flags;
        }

        $overtimeMinutes = (int) (Attendance::query()
            ->whereKey($attendanceId)
            ->value('overtime_minutes') ?? 0);

        if ($overtimeMinutes <= 0) {
            return $flags;
        }

        foreach ($flags as $flag) {
            if (($flag['key'] ?? null) === 'overtime') {
                return $flags;
            }
        }

        $flags[] = $this->attendanceUiService->badgeFromStatus('overtime');

        return $flags;
    }

    private function resolveDate(?Carbon $date): Carbon
    {
        $timezone = config('app.timezone', self::DEFAULT_TIMEZONE);

        if ($date !== null) {
            return $date->copy()->timezone($timezone)->startOfDay();
        }

        return now($timezone)->startOfDay();
    }
}

