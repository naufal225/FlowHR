<?php

namespace Tests\Feature\Api\Mobile;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Exceptions\Attendance\AttendanceNotAllowedException;
use App\Services\Attendance\MobileDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Support\CreatesAttendanceTestData;
use Tests\TestCase;

class MobileDashboardControllerTest extends TestCase
{
    use CreatesAttendanceTestData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_mobile_dashboard_payload_for_authenticated_employee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 17:30:00', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation([
            'radius_meter' => 150,
        ]);
        $this->createAttendanceSetting($office, [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'min_location_accuracy_meter' => 50,
        ]);

        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $attendance = $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-30',
            'check_in_at' => Carbon::parse('2026-03-30 09:02:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'check_in_latitude' => -6.2000500,
            'check_in_longitude' => 106.8166200,
            'check_in_accuracy_meter' => 15,
            'check_out_at' => null,
            'check_out_status' => AttendanceCheckOutStatus::NONE,
            'record_status' => AttendanceRecordStatus::ONGOING,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'is_suspicious' => false,
            'suspicious_reason' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Mobile dashboard retrieved successfully.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'office_location_id' => $office->id,
                    ],
                    'today_status' => [
                        'date' => '2026-03-30',
                        'status' => 'ongoing',
                        'attendance_id' => $attendance->id,
                    ],
                    'attendance_summary' => [
                        'record_status' => AttendanceRecordStatus::ONGOING->value,
                    ],
                    'action_state' => [
                        'next_action' => 'check_out',
                        'can_check_in' => false,
                        'can_check_out' => true,
                    ],
                    'policy' => [
                        'work_start_time' => '09:00:00',
                        'work_end_time' => '17:00:00',
                        'late_tolerance_minutes' => 15,
                        'timezone' => 'Asia/Jakarta',
                    ],
                    'day_context' => [
                        'is_off_day' => false,
                        'is_on_leave' => false,
                    ],
                ],
            ])
            ->assertJsonPath('data.location_readiness.has_location_fix', true)
            ->assertJsonPath('data.location_readiness.status', 'valid')
            ->assertJsonPath('data.location_readiness.status_label', 'Dalam Radius')
            ->assertJsonPath('data.location_readiness.accuracy_level', 'good')
            ->assertJsonPath('data.location_readiness.accuracy_label', 'GPS Baik')
            ->assertJsonPath('data.location_readiness.is_valid', true)
            ->assertJsonPath('data.location_readiness.is_suspicious', false)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'office_location_id',
                        'office_location_name',
                        'active_role',
                    ],
                    'today_status' => [
                        'date',
                        'status',
                        'label',
                        'attendance_id',
                        'check_in_at',
                        'check_out_at',
                        'is_late',
                        'is_early_leave',
                        'is_suspicious',
                        'reason',
                    ],
                    'attendance_summary' => [
                        'record_status',
                        'record_status_label',
                        'check_in_status',
                        'check_in_status_label',
                        'check_out_status',
                        'check_out_status_label',
                        'late_minutes',
                        'early_leave_minutes',
                        'overtime_minutes',
                        'notes',
                        'avg_start' => [
                            'time',
                            'delta_from_shift_start_minutes',
                        ],
                        'this_week' => [
                            'total_minutes',
                            'total_hours',
                        ],
                        'recent_activity' => [
                            ['label', 'type', 'title', 'at'],
                        ],
                        'insight' => [
                            'type',
                            'minutes',
                            'message',
                        ],
                    ],
                    'action_state' => [
                        'next_action',
                        'can_check_in',
                        'can_check_out',
                        'action_disabled_reason',
                    ],
                    'policy' => [
                        'work_start_time',
                        'work_end_time',
                        'late_tolerance_minutes',
                        'timezone',
                    ],
                    'location_readiness' => [
                        'office_radius_meter',
                        'min_location_accuracy_meter',
                        'gps_required',
                        'last_known_distance_meter',
                        'last_known_accuracy_meter',
                        'location_status',
                        'location_reason',
                        'has_location_fix',
                        'accuracy_meter',
                        'distance_meter',
                        'status',
                        'status_label',
                        'accuracy_level',
                        'accuracy_label',
                        'reason',
                        'is_valid',
                        'is_suspicious',
                    ],
                    'day_context' => [
                        'is_off_day',
                        'is_on_leave',
                        'message',
                    ],
                    'recent_attendances' => [
                        ['id', 'work_date', 'check_in_at', 'check_out_at', 'record_status', 'is_suspicious'],
                    ],
                    'alerts',
                ],
            ]);
    }

    public function test_it_returns_strict_unknown_location_readiness_when_no_location_fix_is_available(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 10:00:00', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office);

        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.location_readiness.has_location_fix', false)
            ->assertJsonPath('data.location_readiness.status', null)
            ->assertJsonPath('data.location_readiness.status_label', 'Status lokasi tidak tersedia')
            ->assertJsonPath('data.location_readiness.accuracy_meter', null)
            ->assertJsonPath('data.location_readiness.accuracy_label', 'Akurasi tidak tersedia')
            ->assertJsonPath('data.location_readiness.is_valid', null)
            ->assertJsonPath('data.location_readiness.is_suspicious', null);
    }

    public function test_it_returns_suspicious_location_status_when_accuracy_is_fair_but_not_invalid(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 10:00:00', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'min_location_accuracy_meter' => 50,
        ]);

        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-30',
            'check_in_at' => Carbon::parse('2026-03-30 09:05:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'check_in_latitude' => -6.2000500,
            'check_in_longitude' => 106.8166200,
            'check_in_accuracy_meter' => 75,
            'check_out_at' => null,
            'check_out_status' => AttendanceCheckOutStatus::NONE,
            'record_status' => AttendanceRecordStatus::ONGOING,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.location_readiness.has_location_fix', true)
            ->assertJsonPath('data.location_readiness.status', 'suspicious')
            ->assertJsonPath('data.location_readiness.status_label', 'Perlu Validasi')
            ->assertJsonPath('data.location_readiness.accuracy_meter', 75)
            ->assertJsonPath('data.location_readiness.accuracy_level', 'fair')
            ->assertJsonPath('data.location_readiness.accuracy_label', 'GPS Cukup')
            ->assertJsonPath('data.location_readiness.is_valid', false)
            ->assertJsonPath('data.location_readiness.is_suspicious', true);
    }

    public function test_it_returns_invalid_location_status_when_accuracy_is_too_low(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 10:00:00', 'Asia/Jakarta'));

        $office = $this->createOfficeLocation();
        $this->createAttendanceSetting($office, [
            'min_location_accuracy_meter' => 50,
        ]);

        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        $this->createAttendance($user, $office, null, [
            'work_date' => '2026-03-30',
            'check_in_at' => Carbon::parse('2026-03-30 09:05:00', 'Asia/Jakarta'),
            'check_in_status' => AttendanceCheckInStatus::ON_TIME,
            'check_in_latitude' => -6.2000500,
            'check_in_longitude' => 106.8166200,
            'check_in_accuracy_meter' => 120,
            'check_out_at' => null,
            'check_out_status' => AttendanceCheckOutStatus::NONE,
            'record_status' => AttendanceRecordStatus::ONGOING,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.location_readiness.has_location_fix', true)
            ->assertJsonPath('data.location_readiness.status', 'invalid')
            ->assertJsonPath('data.location_readiness.status_label', 'Di Luar Radius')
            ->assertJsonPath('data.location_readiness.accuracy_meter', 120)
            ->assertJsonPath('data.location_readiness.accuracy_level', 'poor')
            ->assertJsonPath('data.location_readiness.accuracy_label', 'GPS Lemah')
            ->assertJsonPath('data.location_readiness.is_valid', false)
            ->assertJsonPath('data.location_readiness.is_suspicious', false);
    }

    public function test_it_returns_predictable_error_shape_when_dashboard_service_throws_attendance_exception(): void
    {
        $office = $this->createOfficeLocation();
        $user = $this->createEmployee([], $office);
        $this->assignRole($user, Roles::Employee->value);

        /** @var MobileDashboardService&MockObject $service */
        $service = $this->createMock(MobileDashboardService::class);
        $service->expects($this->once())
            ->method('buildForUser')
            ->willThrowException(new AttendanceNotAllowedException(
                message: 'Absensi tidak diizinkan.',
                context: ['token_hash' => 'secret-value', 'office_location_id' => $office->id],
            ));

        $this->app->instance(MobileDashboardService::class, $service);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/mobile/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Absensi tidak diizinkan.',
                'error_code' => 'ATTENDANCE_NOT_ALLOWED',
                'status_code' => 403,
                'context' => [
                    'token_hash' => '[REDACTED]',
                    'office_location_id' => $office->id,
                ],
            ]);
    }
}
