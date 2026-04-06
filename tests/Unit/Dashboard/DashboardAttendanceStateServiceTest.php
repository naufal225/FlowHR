<?php

namespace Tests\Unit\Dashboard;

use App\Data\Attendance\DailyAttendanceStatusData;
use App\Models\User;
use App\Services\Attendance\AttendanceDailyStatusResolverService;
use App\Services\Attendance\AttendanceUiService;
use App\Services\Dashboard\DashboardAttendanceStateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class DashboardAttendanceStateServiceTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    #[DataProvider('statusMatrixProvider')]
    public function test_it_maps_status_payload_for_dashboard_card(
        string $status,
        ?string $checkInAt,
        ?string $checkOutAt,
        string $reason,
        string $expectedKey,
        string $expectedLabel,
        string $expectedCheckIn,
        string $expectedCheckOut,
    ): void {
        $user = $this->createEmployee();
        $date = Carbon::parse('2026-04-06', 'Asia/Jakarta');

        $resolver = $this->createMock(AttendanceDailyStatusResolverService::class);
        $resolver->expects($this->once())
            ->method('resolveForUser')
            ->willReturn($this->makeStatusData(
                user: $user,
                date: $date,
                status: $status,
                reason: $reason,
                checkInAt: $checkInAt,
                checkOutAt: $checkOutAt,
            ));

        $service = new DashboardAttendanceStateService($resolver, new AttendanceUiService());
        $state = $service->forUser($user, $date);

        $this->assertSame($expectedKey, $state['key']);
        $this->assertSame($expectedLabel, $state['badge']['label']);
        $this->assertSame($reason, $state['description']);
        $this->assertSame($expectedCheckIn, $state['check_in']);
        $this->assertSame($expectedCheckOut, $state['check_out']);
    }

    public function test_it_keeps_daily_flags_and_appends_overtime_flag_when_needed(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-04-06',
            'overtime_minutes' => 45,
        ]);
        $date = Carbon::parse('2026-04-06', 'Asia/Jakarta');

        $resolver = $this->createMock(AttendanceDailyStatusResolverService::class);
        $resolver->expects($this->once())
            ->method('resolveForUser')
            ->willReturn($this->makeStatusData(
                user: $user,
                date: $date,
                status: 'complete',
                reason: 'Attendance completed with exceptions.',
                checkInAt: '2026-04-06 08:10:00',
                checkOutAt: '2026-04-06 18:20:00',
                attendanceId: $attendance->id,
                isLate: true,
                isEarlyLeave: true,
                isSuspicious: true,
            ));

        $service = new DashboardAttendanceStateService($resolver, new AttendanceUiService());
        $state = $service->forUser($user, $date);

        $flagKeys = array_map(
            static fn(array $flag): string => (string) ($flag['key'] ?? ''),
            $state['flags']
        );

        $this->assertContains('late', $flagKeys);
        $this->assertContains('early_leave', $flagKeys);
        $this->assertContains('suspicious', $flagKeys);
        $this->assertContains('overtime', $flagKeys);
    }

    public function test_it_returns_config_issue_fallback_when_resolver_throws(): void
    {
        $user = $this->createEmployee();
        $date = Carbon::parse('2026-04-06', 'Asia/Jakarta');

        $resolver = $this->createMock(AttendanceDailyStatusResolverService::class);
        $resolver->expects($this->once())
            ->method('resolveForUser')
            ->willThrowException(new RuntimeException('Resolver failed.'));

        $service = new DashboardAttendanceStateService($resolver, new AttendanceUiService());
        $state = $service->forUser($user, $date);

        $this->assertSame('config_issue', $state['key']);
        $this->assertSame('Configuration Issue', $state['badge']['label']);
        $this->assertSame('-', $state['check_in']);
        $this->assertSame('-', $state['check_out']);
        $this->assertStringContainsString('temporarily unavailable', $state['description']);
    }

    /**
     * @return array<string, array<int, string|null>>
     */
    public static function statusMatrixProvider(): array
    {
        return [
            'not_checked_in_yet' => [
                'not_checked_in_yet',
                null,
                null,
                'No attendance record has been found yet.',
                'not_checked_in',
                'Not Checked In',
                '-',
                '-',
            ],
            'checked_in' => [
                'checked_in',
                '2026-04-06 08:05:00',
                null,
                'Employee has checked in and is still working.',
                'checked_in',
                'Checked In',
                '08:05',
                '-',
            ],
            'complete' => [
                'complete',
                '2026-04-06 08:00:00',
                '2026-04-06 17:10:00',
                'Attendance has complete check-in and check-out logs.',
                'complete',
                'Complete',
                '08:00',
                '17:10',
            ],
            'on_leave' => [
                'on_leave',
                null,
                null,
                'Approved leave exists for this date.',
                'on_leave',
                'On Leave',
                '-',
                '-',
            ],
            'off_day' => [
                'off_day',
                null,
                null,
                'The selected date is a non-working day.',
                'off_day',
                'Off Day',
                '-',
                '-',
            ],
            'absent' => [
                'absent',
                null,
                null,
                'No attendance record was found after the absence threshold passed.',
                'absent',
                'Absent',
                '-',
                '-',
            ],
            'config_issue' => [
                'config_issue',
                null,
                null,
                'Attendance policy configuration is missing.',
                'config_issue',
                'Configuration Issue',
                '-',
                '-',
            ],
        ];
    }

    private function makeStatusData(
        User $user,
        Carbon $date,
        string $status,
        string $reason,
        ?string $checkInAt = null,
        ?string $checkOutAt = null,
        ?int $attendanceId = null,
        bool $isLate = false,
        bool $isEarlyLeave = false,
        bool $isSuspicious = false,
    ): DailyAttendanceStatusData {
        return DailyAttendanceStatusData::fromArray([
            'user_id' => $user->id,
            'date' => $date,
            'status' => $status,
            'reason' => $reason,
            'check_in_at' => $checkInAt !== null ? Carbon::parse($checkInAt, 'Asia/Jakarta') : null,
            'check_out_at' => $checkOutAt !== null ? Carbon::parse($checkOutAt, 'Asia/Jakarta') : null,
            'attendance_id' => $attendanceId,
            'is_late' => $isLate,
            'is_early_leave' => $isEarlyLeave,
            'is_suspicious' => $isSuspicious,
        ]);
    }
}
