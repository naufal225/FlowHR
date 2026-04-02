<?php

namespace App\Data\Attendance;

class MobileDashboardData
{
    public function __construct(
        public readonly array $user,
        public readonly array $todayStatus,
        public readonly array $attendanceSummary,
        public readonly array $actionState,
        public readonly array $policy,
        public readonly array $locationReadiness,
        public readonly array $dayContext,
        public readonly array $recentAttendances,
        public readonly array $alerts,
    ) {}

    public function toArray(): array
    {
        return [
            'user' => $this->user,
            'today_status' => $this->todayStatus,
            'attendance_summary' => $this->attendanceSummary,
            'action_state' => $this->actionState,
            'policy' => $this->policy,
            'location_readiness' => $this->locationReadiness,
            'day_context' => $this->dayContext,
            'recent_attendances' => $this->recentAttendances,
            'alerts' => $this->alerts,
        ];
    }
}
