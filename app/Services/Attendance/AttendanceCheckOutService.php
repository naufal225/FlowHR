<?php

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Data\Attendance\CheckOutData;
use App\Data\Attendance\LocationValidationResultData;
use App\Enums\AttendanceRecordStatus;
use App\Exceptions\Attendance\AttendanceNotAllowedException;
use App\Exceptions\Attendance\ExpiredQrTokenException;
use App\Exceptions\Attendance\InactiveQrTokenException;
use App\Exceptions\Attendance\InvalidAttendanceLocationException;
use App\Exceptions\Attendance\InvalidQrTokenException;
use App\Exceptions\Attendance\LocationOutOfRangeException;
use App\Exceptions\Attendance\LowLocationAccuracyException;
use App\Exceptions\Attendance\OfficeLocationCoordinateMissingException;
use App\Exceptions\Attendance\OfficeLocationMismatchException;
use App\Models\Attendance;
use App\Models\AttendanceQrToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCheckOutService
{
    public function __construct(
        protected AttendancePolicyService $attendancePolicyService,
        protected AttendanceQrValidationService $attendanceQrValidationService,
        protected AttendanceLocationValidationService $attendanceLocationValidationService,
        protected AttendanceLogService $attendanceLogService,
    ) {}

    public function checkOut(CheckOutData $data): Attendance
    {
        $now = now();

        $policy = $this->attendancePolicyService->getPolicyForUser($data->userId, $now);

        $this->ensureCheckOutWindowIsOpen($policy, $now);

        $this->validateQrOrFail($data, $policy, $now);

        $locationResult = $this->validateLocationOrFail($data, $policy, $now);

        $workDate = $this->resolveWorkDate($policy, $now);
        $checkOutStatus = $this->attendancePolicyService->determineCheckOutStatus($policy, $now);

        return DB::transaction(function () use (
            $data,
            $policy,
            $locationResult,
            $workDate,
            $checkOutStatus,
            $now
        ) {
            $attendance = $this->findTodayAttendanceForUpdate(
                userId: $data->userId,
                workDate: $workDate
            );

            $this->assertCheckOutIsAllowed(
                attendance: $attendance,
                data: $data,
                workDate: $workDate
            );

            $payload = $this->buildCheckOutPayload(
                attendance: $attendance,
                data: $data,
                locationResult: $locationResult,
                checkOutStatus: $checkOutStatus,
                now: $now
            );

            $attendance->fill($payload);
            $attendance->save();

            $this->writeSuccessLogs(
                attendance: $attendance,
                data: $data,
                policy: $policy,
                locationResult: $locationResult,
                checkOutStatus: $checkOutStatus,
                now: $now
            );

            return $attendance->fresh();
        }, 3);
    }

    private function ensureCheckOutWindowIsOpen(
        AttendancePolicyData $policy,
        Carbon $now
    ): void {
        if ($this->attendancePolicyService->isWithinCheckOutWindow($policy, $now)) {
            return;
        }

        throw new AttendanceNotAllowedException(
            message: 'Saat ini tidak berada dalam window check-out yang diizinkan.',
            context: [
                'office_location_id' => $policy->officeLocationId,
                'timezone' => $policy->timezone,
            ]
        );
    }

    private function validateQrOrFail(
        CheckOutData $data,
        AttendancePolicyData $policy,
        Carbon $now
    ): AttendanceQrToken {
        try {
            return $this->attendanceQrValidationService->validateForOffice(
                userId: $data->userId,
                expectedOfficeLocationId: $policy->officeLocationId,
                rawToken: $data->qrToken,
                now: $now
            );
        } catch (
            InvalidQrTokenException |
            ExpiredQrTokenException |
            InactiveQrTokenException |
            OfficeLocationMismatchException $e
        ) {
            $this->attendanceLogService->logQrRejected(
                userId: $data->userId,
                message: $e->getMessage(),
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: array_merge([
                    'office_location_id' => $policy->officeLocationId,
                    'reason' => 'CHECK_OUT_QR_REJECTED',
                ], method_exists($e, 'getContext') ? $e->getContext() : []),
                occurredAt: $now,
            );

            throw $e;
        }
    }

    private function validateLocationOrFail(
        CheckOutData $data,
        AttendancePolicyData $policy,
        Carbon $now
    ): LocationValidationResultData {
        try {
            return $this->attendanceLocationValidationService->validateForPolicy(
                policy: $policy,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter
            );
        } catch (
            InvalidAttendanceLocationException |
            LowLocationAccuracyException |
            LocationOutOfRangeException |
            OfficeLocationCoordinateMissingException $e
        ) {
            $this->attendanceLogService->logLocationRejected(
                userId: $data->userId,
                message: $e->getMessage(),
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: array_merge([
                    'office_location_id' => $policy->officeLocationId,
                    'reason' => 'CHECK_OUT_LOCATION_REJECTED',
                ], method_exists($e, 'getContext') ? $e->getContext() : []),
                occurredAt: $now,
            );

            throw $e;
        }
    }

    private function findTodayAttendanceForUpdate(int $userId, Carbon $workDate): ?Attendance
    {
        return Attendance::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $workDate->toDateString())
            ->lockForUpdate()
            ->first();
    }

    private function assertCheckOutIsAllowed(
        ?Attendance $attendance,
        CheckOutData $data,
        Carbon $workDate
    ): void {
        if (! $attendance) {
            $this->attendanceLogService->logInvalidCheckOutAttempt(
                userId: $data->userId,
                attendanceId: null,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: [
                    'work_date' => $workDate->toDateString(),
                    'reason' => 'ATTENDANCE_NOT_FOUND',
                ],
            );

            throw new AttendanceNotAllowedException(
                message: 'Attendance hari ini tidak ditemukan untuk check-out.',
                context: [
                    'user_id' => $data->userId,
                    'work_date' => $workDate->toDateString(),
                ]
            );
        }

        if ($attendance->check_in_at === null) {
            $this->attendanceLogService->logInvalidCheckOutAttempt(
                userId: $data->userId,
                attendanceId: $attendance->id,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: [
                    'work_date' => $workDate->toDateString(),
                    'reason' => 'CHECK_IN_NOT_FOUND',
                ],
            );

            throw new AttendanceNotAllowedException(
                message: 'Check-out tidak diizinkan karena belum ada check-in.',
                context: [
                    'attendance_id' => $attendance->id,
                    'user_id' => $data->userId,
                    'work_date' => $workDate->toDateString(),
                ]
            );
        }

        if ($attendance->check_out_at !== null) {
            $this->attendanceLogService->logInvalidCheckOutAttempt(
                userId: $data->userId,
                attendanceId: $attendance->id,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: [
                    'work_date' => $workDate->toDateString(),
                    'reason' => 'ALREADY_CHECKED_OUT',
                ],
            );

            throw new AttendanceNotAllowedException(
                message: 'Check-out sudah pernah direkam untuk hari ini.',
                context: [
                    'attendance_id' => $attendance->id,
                    'user_id' => $data->userId,
                    'work_date' => $workDate->toDateString(),
                ]
            );
        }

        if (
            $attendance->record_status !== null
            && $attendance->record_status !== AttendanceRecordStatus::ONGOING
        ) {
            $this->attendanceLogService->logInvalidCheckOutAttempt(
                userId: $data->userId,
                attendanceId: $attendance->id,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: [
                    'work_date' => $workDate->toDateString(),
                    'record_status' => $attendance->record_status?->value ?? (string) $attendance->record_status,
                    'reason' => 'INVALID_RECORD_STATUS',
                ],
            );

            throw new AttendanceNotAllowedException(
                message: 'Check-out tidak diizinkan karena status record attendance tidak valid.',
                context: [
                    'attendance_id' => $attendance->id,
                    'user_id' => $data->userId,
                    'work_date' => $workDate->toDateString(),
                    'record_status' => $attendance->record_status?->value ?? (string) $attendance->record_status,
                ]
            );
        }
    }

    private function buildCheckOutPayload(
        Attendance $attendance,
        CheckOutData $data,
        LocationValidationResultData $locationResult,
        array $checkOutStatus,
        Carbon $now
    ): array {
        $existingSuspiciousReason = $attendance->suspicious_reason;
        $newSuspiciousReason = $locationResult->reason;

        $mergedSuspiciousReason = $this->mergeSuspiciousReason(
            existing: $existingSuspiciousReason,
            incoming: $newSuspiciousReason
        );

        return [
            'check_out_at' => $now,
            'check_out_recorded_at' => $now,
            'check_out_latitude' => $data->latitude,
            'check_out_longitude' => $data->longitude,
            'check_out_accuracy_meter' => $data->accuracyMeter,
            'check_out_status' => $checkOutStatus['status'],
            'early_leave_minutes' => (int) ($checkOutStatus['early_leave_minutes'] ?? 0),
            'overtime_minutes' => (int) ($checkOutStatus['overtime_minutes'] ?? 0),
            'record_status' => AttendanceRecordStatus::COMPLETE,
            'is_suspicious' => (bool) $attendance->is_suspicious || (bool) $locationResult->isSuspicious,
            'suspicious_reason' => $mergedSuspiciousReason,
        ];
    }

    private function writeSuccessLogs(
        Attendance $attendance,
        CheckOutData $data,
        AttendancePolicyData $policy,
        LocationValidationResultData $locationResult,
        array $checkOutStatus,
        Carbon $now
    ): void {
        $this->attendanceLogService->logCheckOutSuccess(
            attendanceId: $attendance->id,
            userId: $data->userId,
            latitude: $data->latitude,
            longitude: $data->longitude,
            accuracyMeter: $data->accuracyMeter,
            ipAddress: $data->ipAddress,
            deviceInfo: $data->deviceInfo,
            context: [
                'office_location_id' => $policy->officeLocationId,
                'work_date' => Carbon::parse($attendance->work_date)->toDateString(),
                'distance_meter' => $locationResult->distanceMeter,
                'check_out_status' => $checkOutStatus['status']->value,
                'early_leave_minutes' => (int) ($checkOutStatus['early_leave_minutes'] ?? 0),
                'overtime_minutes' => (int) ($checkOutStatus['overtime_minutes'] ?? 0),
            ],
            isSuspicious: (bool) $locationResult->isSuspicious,
            suspiciousReason: $locationResult->reason,
            occurredAt: $now,
        );

        if ($locationResult->isSuspicious) {
            $this->attendanceLogService->logSuspiciousActivity(
                userId: $data->userId,
                attendanceId: $attendance->id,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                message: 'Check-out berhasil tetapi terdeteksi kondisi lokasi yang mencurigakan.',
                context: [
                    'office_location_id' => $policy->officeLocationId,
                    'work_date' => Carbon::parse($attendance->work_date)->toDateString(),
                    'distance_meter' => $locationResult->distanceMeter,
                    'reason' => $locationResult->reason,
                ],
                occurredAt: $now,
            );
        }
    }

    private function resolveWorkDate(AttendancePolicyData $policy, Carbon $at): Carbon
    {
        return $at->copy()
            ->setTimezone($policy->timezone)
            ->startOfDay();
    }

    private function mergeSuspiciousReason(
        ?string $existing,
        ?string $incoming
    ): ?string {
        $parts = [];

        if ($existing !== null && trim($existing) !== '') {
            $parts[] = trim($existing);
        }

        if ($incoming !== null && trim($incoming) !== '') {
            $parts[] = trim($incoming);
        }

        $parts = array_values(array_unique($parts));

        if (empty($parts)) {
            return null;
        }

        return implode('|', $parts);
    }
}
