<?php

namespace Database\Seeders;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\Attendance\AttendanceSettingManagementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceOfficeEmployeeSeeder extends Seeder
{
    private const LATE_TOLERANCE_MINUTES = 10;
    private const CHECK_IN_BEFORE_LATE_TOLERANCE_MINUTES = 4;
    private const CHECK_IN_WINDOW_MINUTES = 60;
    private const CHECK_OUT_WINDOW_MINUTES = 60;
    private const WORK_DURATION_HOURS = 8;
    private const CHECK_OUT_OFFSET_MINUTES = 30;
    private const DEFAULT_QR_ROTATION_SECONDS = 30;
    private const DEFAULT_MIN_LOCATION_ACCURACY_METER = 50;

    public function run(): void
    {
        $seededAt = now();

        $offices = OfficeLocation::query()
            ->select(['id', 'name', 'timezone'])
            ->get();

        if ($offices->isEmpty()) {
            $this->command->warn('Seeder attendance dilewati: belum ada office location.');

            return;
        }

        $settingService = app(AttendanceSettingManagementService::class);

        $scheduleByOffice = [];
        $settingsCount = 0;

        foreach ($offices as $office) {
            $officeNow = $seededAt->copy()->setTimezone($office->timezone ?: config('app.timezone', 'Asia/Jakarta'));
            $workStartAt = $officeNow->copy()->second(0);
            $workEndAt = $workStartAt->copy()->addHours(self::WORK_DURATION_HOURS);

            $settingService->upsert([
                'office_location_id' => $office->id,
                'work_start_time' => $workStartAt->format('H:i:s'),
                'work_end_time' => $workEndAt->format('H:i:s'),
                'late_tolerance_minutes' => self::LATE_TOLERANCE_MINUTES,
                'qr_rotation_seconds' => self::DEFAULT_QR_ROTATION_SECONDS,
                'min_location_accuracy_meter' => self::DEFAULT_MIN_LOCATION_ACCURACY_METER,
                'is_active' => true,
            ]);

            $scheduleByOffice[$office->id] = [
                'work_start_at' => $workStartAt,
                'work_end_at' => $workEndAt,
            ];

            $settingsCount++;
        }

        $defaultOfficeId = (int) $offices->first()->id;

        $employees = User::query()
            ->select(['id', 'name', 'office_location_id', 'is_active'])
            ->where('is_active', true)
            ->whereHas('roles', function ($query): void {
                $query->where('name', Roles::Employee->value);
            })
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn('Tidak ada employee aktif. Attendance setting tetap berhasil dibuat.');
            $this->command->info("Attendance setting di-seed untuk {$settingsCount} office.");

            return;
        }

        $attendanceCount = 0;
        $fallbackOfficeCount = 0;

        foreach ($employees as $employee) {
            $officeId = (int) ($employee->office_location_id ?? $defaultOfficeId);

            if (!isset($scheduleByOffice[$officeId])) {
                $officeId = $defaultOfficeId;
                $fallbackOfficeCount++;
            }

            if ($employee->office_location_id === null) {
                $fallbackOfficeCount++;
            }

            $schedule = $scheduleByOffice[$officeId];
            $workStartAt = $schedule['work_start_at']->copy();
            $workEndAt = $schedule['work_end_at']->copy();

            $lateDeadline = $workStartAt->copy()->addMinutes(self::LATE_TOLERANCE_MINUTES);

            $checkInAt = $this->clampToWindow(
                $lateDeadline->copy()->subMinutes(self::CHECK_IN_BEFORE_LATE_TOLERANCE_MINUTES),
                $workStartAt->copy()->subMinutes(self::CHECK_IN_WINDOW_MINUTES),
                $workStartAt->copy()->addMinutes(self::CHECK_IN_WINDOW_MINUTES),
            );

            $checkOutAt = $this->clampToWindow(
                $workEndAt->copy()->addMinutes(self::CHECK_OUT_OFFSET_MINUTES),
                $workEndAt->copy()->subMinutes(self::CHECK_OUT_WINDOW_MINUTES),
                $workEndAt->copy()->addMinutes(self::CHECK_OUT_WINDOW_MINUTES),
            );

            $checkOutStatus = AttendanceCheckOutStatus::NORMAL;
            $earlyLeaveMinutes = 0;
            $overtimeMinutes = 0;

            if ($checkOutAt->lt($workEndAt)) {
                $checkOutStatus = AttendanceCheckOutStatus::EARLY_LEAVE;
                $earlyLeaveMinutes = (int) $checkOutAt->diffInMinutes($workEndAt);
            } elseif ($checkOutAt->gt($workEndAt)) {
                $checkOutStatus = AttendanceCheckOutStatus::OVERTIME;
                $overtimeMinutes = (int) $workEndAt->diffInMinutes($checkOutAt);
            }

            Attendance::query()->updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'work_date' => $workStartAt->toDateString(),
                ],
                [
                    'office_location_id' => $officeId,
                    'check_in_at' => $checkInAt->format('Y-m-d H:i:s'),
                    'check_in_recorded_at' => $checkInAt->copy()->addSeconds(15)->format('Y-m-d H:i:s'),
                    'check_in_status' => AttendanceCheckInStatus::ON_TIME,
                    'check_out_at' => $checkOutAt->format('Y-m-d H:i:s'),
                    'check_out_recorded_at' => $checkOutAt->copy()->addSeconds(15)->format('Y-m-d H:i:s'),
                    'check_out_status' => $checkOutStatus,
                    'record_status' => AttendanceRecordStatus::COMPLETE,
                    'late_minutes' => 0,
                    'early_leave_minutes' => $earlyLeaveMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'is_suspicious' => false,
                    'suspicious_reason' => null,
                    'notes' => sprintf(
                        'Seeded: late tolerance %d menit, check-in %d menit sebelum batas terlambat, window check-in/check-out +/- %d menit.',
                        self::LATE_TOLERANCE_MINUTES,
                        self::CHECK_IN_BEFORE_LATE_TOLERANCE_MINUTES,
                        self::CHECK_IN_WINDOW_MINUTES,
                    ),
                ]
            );

            $attendanceCount++;
        }

        $this->command->info("Attendance setting di-seed untuk {$settingsCount} office.");
        $this->command->info("Attendance di-seed untuk {$attendanceCount} employee.");

        if ($fallbackOfficeCount > 0) {
            $this->command->warn("{$fallbackOfficeCount} employee menggunakan fallback office karena office tidak terpasang.");
        }
    }

    private function clampToWindow(Carbon $value, Carbon $windowStart, Carbon $windowEnd): Carbon
    {
        if ($value->lt($windowStart)) {
            return $windowStart->copy();
        }

        if ($value->gt($windowEnd)) {
            return $windowEnd->copy();
        }

        return $value->copy();
    }
}
