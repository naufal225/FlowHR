<?php

namespace Tests\Support;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceLogActionStatus;
use App\Enums\AttendanceLogActionType;
use App\Enums\AttendanceRecordStatus;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSetting;
use App\Models\Division;
use App\Models\OfficeLocation;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait CreatesAttendanceTestData
{
    protected function createDivision(array $overrides = []): Division
    {
        return Division::query()->create(array_merge([
            'name' => 'Division ' . Str::uuid(),
        ], $overrides));
    }

    protected function createOfficeLocation(array $overrides = []): OfficeLocation
    {
        return OfficeLocation::query()->create(array_merge([
            'code' => 'OFF-' . Str::upper(Str::random(6)),
            'name' => 'Office ' . Str::random(8),
            'address' => 'Jl. Jend. Sudirman No. 1',
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'timezone' => 'Asia/Jakarta',
            'radius_meter' => 100,
            'is_active' => true,
        ], $overrides));
    }

    protected function createAttendanceSetting(OfficeLocation $office, array $overrides = []): AttendanceSetting
    {
        return AttendanceSetting::query()->create(array_merge([
            'office_location_id' => $office->id,
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'qr_rotation_seconds' => 30,
            'min_location_accuracy_meter' => 50,
            'is_active' => true,
        ], $overrides));
    }

    protected function createEmployee(
        array $overrides = [],
        ?OfficeLocation $office = null,
        ?Division $division = null
    ): User {
        $office ??= $this->createOfficeLocation();
        $division ??= $this->createDivision();

        return User::factory()->create(array_merge([
            'division_id' => $division->id,
            'office_location_id' => $office->id,
            'is_active' => true,
        ], $overrides));
    }

    protected function assignRole(User $user, string $roleName): Role
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        return $role;
    }

    protected function createAttendanceQrToken(
        OfficeLocation $office,
        array $overrides = []
    ): AttendanceQrToken {
        $now = Carbon::now('Asia/Jakarta');

        return AttendanceQrToken::query()->create(array_merge([
            'office_location_id' => $office->id,
            'token' => 'QR-' . Str::upper(Str::random(12)),
            'generated_at' => $now->copy()->subMinute(),
            'expired_at' => $now->copy()->addMinutes(5),
            'is_active' => true,
        ], $overrides));
    }

    protected function createAttendance(
        User $user,
        OfficeLocation $office,
        ?AttendanceQrToken $qrToken = null,
        array $overrides = []
    ): Attendance {
        return Attendance::query()->create(array_merge([
            'user_id' => $user->id,
            'office_location_id' => $office->id,
            'attendance_qr_token_id' => $qrToken?->id,
            'work_date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'check_in_status' => AttendanceCheckInStatus::NONE,
            'check_out_status' => AttendanceCheckOutStatus::NONE,
            'record_status' => AttendanceRecordStatus::ONGOING,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'is_suspicious' => false,
        ], $overrides));
    }

    protected function createAttendanceLog(
        User $user,
        ?Attendance $attendance = null,
        array $overrides = []
    ): AttendanceLog {
        return AttendanceLog::query()->create(array_merge([
            'attendance_id' => $attendance?->id,
            'user_id' => $user->id,
            'action_type' => AttendanceLogActionType::CHECK_IN_SUCCESS,
            'action_status' => AttendanceLogActionStatus::SUCCESS,
            'latitude' => -6.2000000,
            'longitude' => 106.8166667,
            'accuracy_meter' => 10.5,
            'ip_address' => '127.0.0.1',
            'device_info' => 'PHPUnit Device',
            'message' => 'Attendance log created for testing.',
            'context' => ['source' => 'phpunit'],
            'occurred_at' => Carbon::now('Asia/Jakarta'),
        ], $overrides));
    }
}
