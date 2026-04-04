<?php

namespace Tests\Feature\Api\Mobile;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class MobileLeavePageControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_leave_page_payload_with_today_context_summary_and_history(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-03 08:15:23', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation([
            'timezone' => 'Asia/Jakarta',
        ]);
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $activeLeave = Leave::query()->create([
            'employee_id' => $user->id,
            'date_start' => '2026-04-03',
            'date_end' => '2026-04-05',
            'reason' => 'Keperluan keluarga',
            'status_1' => 'approved',
            'approved_date' => '2026-04-02 09:00:00',
        ]);

        $upcomingLeave = Leave::query()->create([
            'employee_id' => $user->id,
            'date_start' => '2026-04-10',
            'date_end' => '2026-04-11',
            'reason' => 'Medical checkup',
            'status_1' => 'approved',
            'approved_date' => '2026-04-07 10:00:00',
        ]);

        Leave::query()->create([
            'employee_id' => $user->id,
            'date_start' => '2026-04-20',
            'date_end' => '2026-04-20',
            'reason' => 'Should not be counted',
            'status_1' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/employee/leave');

        $response->assertOk()
            ->assertJson([
                'message' => 'Data halaman leave berhasil diambil.',
            ])
            ->assertJsonPath('data.today_context.date', '2026-04-03')
            ->assertJsonPath('data.today_context.day_name', 'Friday')
            ->assertJsonPath('data.today_context.attendance_status', 'on_leave')
            ->assertJsonPath('data.today_context.attendance_status_label', 'Sedang Cuti')
            ->assertJsonPath('data.today_context.attendance_note', 'Anda tidak perlu check-in hari ini.')
            ->assertJsonPath('data.today_context.is_working_day', true)
            ->assertJsonPath('data.today_context.leave.id', $activeLeave->id)
            ->assertJsonPath('data.today_context.leave.status', 'approved')
            ->assertJsonPath('data.today_context.leave.status_label', 'Disetujui')
            ->assertJsonPath('data.today_context.leave.duration_days', 3)
            ->assertJsonPath('data.today_context.attendance', null)
            ->assertJsonPath('data.summary.approved_leave_days_this_month', 5)
            ->assertJsonPath('data.summary.approved_leave_requests_this_month', 2)
            ->assertJsonPath('data.summary.active_leave_count', 1)
            ->assertJsonPath('data.summary.upcoming_leave_count', 1)
            ->assertJsonPath('data.active_or_upcoming_leaves.0.id', $activeLeave->id)
            ->assertJsonPath('data.active_or_upcoming_leaves.0.is_active', true)
            ->assertJsonPath('data.active_or_upcoming_leaves.0.is_upcoming', false)
            ->assertJsonPath('data.active_or_upcoming_leaves.1.id', $upcomingLeave->id)
            ->assertJsonPath('data.active_or_upcoming_leaves.1.is_active', false)
            ->assertJsonPath('data.active_or_upcoming_leaves.1.is_upcoming', true)
            ->assertJsonPath('data.history.items.0.id', $upcomingLeave->id)
            ->assertJsonPath('data.history.items.1.id', $activeLeave->id)
            ->assertJsonPath('data.history.pagination.current_page', 1)
            ->assertJsonPath('data.history.pagination.per_page', 10)
            ->assertJsonPath('data.history.pagination.total_items', 2)
            ->assertJsonPath('data.history.pagination.total_pages', 1)
            ->assertJsonPath('data.history.pagination.has_more', false)
            ->assertJsonPath('meta.server_time', '2026-04-03T08:15:23+07:00')
            ->assertJsonPath('meta.timezone', 'Asia/Jakarta')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'today_context' => [
                        'date',
                        'day_name',
                        'attendance_status',
                        'attendance_status_label',
                        'attendance_note',
                        'is_working_day',
                        'leave' => [
                            'id',
                            'status',
                            'status_label',
                            'date_start',
                            'date_end',
                            'duration_days',
                            'reason',
                        ],
                        'attendance',
                    ],
                    'summary' => [
                        'approved_leave_days_this_month',
                        'approved_leave_requests_this_month',
                        'active_leave_count',
                        'upcoming_leave_count',
                    ],
                    'active_or_upcoming_leaves' => [
                        [
                            'id',
                            'status',
                            'status_label',
                            'date_start',
                            'date_end',
                            'duration_days',
                            'reason',
                            'approved_date',
                            'created_at',
                            'is_active',
                            'is_upcoming',
                        ],
                    ],
                    'history' => [
                        'items' => [
                            [
                                'id',
                                'status',
                                'status_label',
                                'date_start',
                                'date_end',
                                'duration_days',
                                'reason',
                                'approved_date',
                                'created_at',
                            ],
                        ],
                        'pagination' => [
                            'current_page',
                            'per_page',
                            'total_items',
                            'total_pages',
                            'has_more',
                        ],
                    ],
                ],
                'meta' => [
                    'server_time',
                    'timezone',
                ],
            ]);
    }

    public function test_it_returns_checked_in_context_when_no_approved_leave_exists_for_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-03 10:00:00', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-04-03',
            'check_in_at' => Carbon::parse('2026-04-03 09:01:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'check_out_at' => null,
            'check_out_status' => AttendanceCheckOutStatus::NONE,
            'record_status' => AttendanceRecordStatus::ONGOING,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/employee/leave');

        $response->assertOk()
            ->assertJsonPath('data.today_context.attendance_status', 'checked_in')
            ->assertJsonPath('data.today_context.attendance_status_label', 'Sudah Check-in')
            ->assertJsonPath('data.today_context.attendance_note', 'Anda sudah check-in, jangan lupa check-out.')
            ->assertJsonPath('data.today_context.leave', null)
            ->assertJsonPath('data.today_context.attendance.id', $attendance->id)
            ->assertJsonPath('data.today_context.attendance.record_status', 'ongoing');
    }

    public function test_it_validates_leave_page_query_parameters(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/employee/leave?page=0&per_page=999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['page', 'per_page']);
    }

    public function test_it_requires_authentication_for_leave_page_endpoint(): void
    {
        $response = $this->getJson('/api/mobile/employee/leave');

        $response->assertUnauthorized();
    }
}
