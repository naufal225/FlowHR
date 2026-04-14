<?php

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Exceptions\Attendance\AttendanceNotAllowedException;
use App\Exceptions\Attendance\AttendancePolicyNotFoundException;
use App\Exceptions\Attendance\OfficeLocationInactiveException;
use App\Exceptions\Attendance\OfficeLocationNotAssignedException;
use App\Models\User;
use App\Models\OfficeLocation;
use App\Models\AttendanceSetting;
use Carbon\Carbon;

class AttendancePolicyService
{
    public function __construct() {}

    private function normalizeToPolicyTimezone(Carbon $at, AttendancePolicyData $policy): Carbon
    {
        return $at->copy()->setTimezone($policy->timezone);
    }

    public function getPolicyForUser(int $userId, ?Carbon $now = null): AttendancePolicyData
    {
        $now ??= now();

        $user = User::query()
            ->select(['id', 'office_location_id'])
            ->find($userId);

        if (!$user) {
            throw new AttendanceNotAllowedException(
                message: "Absensi tidak diizinkan, user tidak ditemukan"
            );
        }

        if (!$user->office_location_id) {
            throw new OfficeLocationNotAssignedException([]);
        }

        $office = OfficeLocation::query()
            ->select(['id', 'latitude', 'longitude', 'radius_meter', 'is_active', 'timezone'])
            ->find($user->office_location_id);

        if (!$office) {
            throw new OfficeLocationNotAssignedException([]);
        }

        if (!$office->is_active) {
            throw new OfficeLocationInactiveException([]);
        }

        $setting = AttendanceSetting::query()
            ->where('office_location_id', $office->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if (!$setting) {
            throw new AttendancePolicyNotFoundException([]);
        }

        return AttendancePolicyData::fromArray([
            'office_location_id'         => $office->id,
            'work_start_time'            => $setting->work_start_time,
            'work_end_time'              => $setting->work_end_time,
            'office_latitude'            => $office->latitude,
            'office_longitude'           => $office->longitude,
            'late_tolerance_minutes'     => $setting->late_tolerance_minutes,
            'qr_rotation_seconds'        => $setting->qr_rotation_seconds,
            'min_location_accuracy_meter'=> $setting->min_location_accuracy_meter,
            'allowed_radius_meter'       => $office->radius_meter,
            'timezone'                   => $office->timezone ?? config('app.timezone', 'Asia/Jakarta'),
        ]);
    }

    public function resolveWorkSchedule(AttendancePolicyData $policy, Carbon $dateTime): array
    {
        $date = $this->normalizeToPolicyTimezone($dateTime, $policy);

        $workStartAt = Carbon::parse(
            $date->toDateString() . ' ' . $policy->workStartTime,
            $policy->timezone
        );

        $workEndAt = Carbon::parse(
            $date->toDateString() . ' ' . $policy->workEndTime,
            $policy->timezone
        );

        if ($workEndAt->lessThanOrEqualTo($workStartAt)) {
            $workEndAt->addDay();
        }

        $lateToleranceDeadline = $workStartAt->copy()->addMinutes($policy->lateToleranceMinutes);

        return [
            'work_start_at'          => $workStartAt,
            'work_end_at'            => $workEndAt,
            'late_tolerance_deadline'=> $lateToleranceDeadline,
        ];
    }

    public function determineCheckInStatus(AttendancePolicyData $policy, Carbon $checkInAt): array
    {
        $checkInAt = $this->normalizeToPolicyTimezone($checkInAt, $policy);
        $schedule  = $this->resolveWorkSchedule($policy, $checkInAt);

        if ($checkInAt->lte($schedule['late_tolerance_deadline'])) {
            return [
                'status'       => AttendanceCheckInStatus::ON_TIME,
                'late_minutes' => 0,
            ];
        }

        return [
            'status'       => AttendanceCheckInStatus::LATE,
            'late_minutes' => (int) $schedule['work_start_at']->diffInMinutes($checkInAt),
        ];
    }

    public function determineCheckOutStatus(AttendancePolicyData $policy, Carbon $checkOutAt): array
    {
        $checkOutAt = $this->normalizeToPolicyTimezone($checkOutAt, $policy);
        $schedule   = $this->resolveWorkSchedule($policy, $checkOutAt);
        $workEndAt  = $schedule['work_end_at'];

        if ($checkOutAt->lt($workEndAt)) {
            return [
                'status'             => AttendanceCheckOutStatus::EARLY_LEAVE,
                'early_leave_minutes'=> (int) $checkOutAt->diffInMinutes($workEndAt),
                'overtime_minutes'   => 0,
            ];
        }

        if ($checkOutAt->eq($workEndAt)) {
            return [
                'status'             => AttendanceCheckOutStatus::NORMAL,
                'early_leave_minutes'=> 0,
                'overtime_minutes'   => 0,
            ];
        }

        return [
            'status'             => AttendanceCheckOutStatus::OVERTIME,
            'early_leave_minutes'=> 0,
            'overtime_minutes'   => (int) $workEndAt->diffInMinutes($checkOutAt),
        ];
    }

    public function isWithinCheckInWindow(AttendancePolicyData $policy, Carbon $now): bool
    {
        $now      = $this->normalizeToPolicyTimezone($now, $policy);
        $schedule = $this->resolveWorkSchedule($policy, $now);

        $startWindow = $schedule['work_start_at']->copy()->subHours(2);
        $endWindow   = $schedule['work_start_at']->copy()->addHours(4);

        return $now->betweenIncluded($startWindow, $endWindow);
    }

    public function isWithinCheckOutWindow(AttendancePolicyData $policy, Carbon $now): bool
    {
        $now      = $this->normalizeToPolicyTimezone($now, $policy);
        $schedule = $this->resolveWorkSchedule($policy, $now);

        $startWindow = $schedule['work_end_at']->copy()->subHours(2);
        $endWindow   = $schedule['work_end_at']->copy()->addHours(6);

        return $now->betweenIncluded($startWindow, $endWindow);
    }
}


