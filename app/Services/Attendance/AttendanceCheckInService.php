<?php

namespace App\Services\Attendance;

use App\Data\Attendance\CheckInData;
use App\Data\Attendance\AttendancePolicyData;
use App\Data\Attendance\LocationValidationResultData;
use App\Enums\AttendanceRecordStatus;
use App\Exceptions\Attendance\AlreadyCheckedInException;
use App\Exceptions\Attendance\AttendanceNotAllowedException;
use App\Exceptions\Attendance\InvalidAttendanceLocationException;
use App\Exceptions\Attendance\InvalidQrTokenException;
use App\Exceptions\Attendance\ExpiredQrTokenException;
use App\Exceptions\Attendance\InactiveQrTokenException;
use App\Exceptions\Attendance\LocationOutOfRangeException;
use App\Exceptions\Attendance\LowLocationAccuracyException;
use App\Exceptions\Attendance\OfficeLocationCoordinateMissingException;
use App\Exceptions\Attendance\OfficeLocationMismatchException;
use App\Models\Attendance;
use App\Models\AttendanceQrToken;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class AttendanceCheckInService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected AttendancePolicyService $attendancePolicyService,
        protected AttendanceQrValidationService $attendanceQrValidationService,
        protected AttendanceLocationValidationService $attendanceLocationValidationService,
        protected AttendanceLogService $attendanceLogService,
    )
    {
        //
    }

    public function checkIn(CheckInData $data): Attendance
    {
        $now = now();

        $policy = $this->attendancePolicyService->getPolicyForUser($data->userId, $now);

        $this->ensureCheckInWindowIsOpen($policy, $now, $data);

        $qrToken = $this->validateQrOrFail($data, $policy, $now);

        $locationResult = $this->validateLocationOrFail($data, $policy, $now);

        $workDate = $this->resolveWorkDate($policy, $now);
        $checkInStatus = $this->attendancePolicyService->determineCheckInStatus($policy, $now);

        try {
            /** @var Attendance $attendance */
            $attendance = DB::transaction(function () use (
                $data,
                $policy,
                $qrToken,
                $locationResult,
                $workDate,
                $checkInStatus,
                $now
            ) {
                $attendance = $this->findTodayAttendanceForUpdate(
                    userId: $data->userId,
                    workDate: $workDate
                );

                $this->assertCheckInIsAllowed($attendance, $data, $workDate);

                $payload = $this->buildAttendancePayload(
                    data: $data,
                    policy: $policy,
                    qrToken: $qrToken,
                    locationResult: $locationResult,
                    checkInStatus: $checkInStatus,
                    workDate: $workDate,
                    now: $now
                );

                $attendance = $this->createOrUpdateAttendance($attendance, $payload);

                $this->writeSuccessLogs(
                    attendance: $attendance,
                    data: $data,
                    policy: $policy,
                    locationResult: $locationResult,
                    checkInStatus: $checkInStatus,
                    now: $now
                );

                return $attendance->fresh();
            }, 3);

            return $attendance;
        } catch (AlreadyCheckedInException $e) {
            $this->attendanceLogService->logDuplicateCheckInAttempt(
                userId: $data->userId,
                attendanceId: $e->getContext()['attendance_id'] ?? null,
                latitude: $data->latitude,
                longitude: $data->longitude,
                accuracyMeter: $data->accuracyMeter,
                ipAddress: $data->ipAddress,
                deviceInfo: $data->deviceInfo,
                context: [
                    'work_date' => $workDate->toDateString(),
                    'office_location_id' => $policy->officeLocationId,
                    'reason' => 'ALREADY_CHECKED_IN',
                ],
                occurredAt: $now,
            );

            throw $e;
        } catch (QueryException $e) {
            if ($this->isUniqueAttendanceViolation($e)) {
                $existingAttendance = Attendance::query()
                    ->where('user_id', $data->userId)
                    ->whereDate('work_date', $workDate->toDateString())
                    ->first();

                $this->attendanceLogService->logDuplicateCheckInAttempt(
                    userId: $data->userId,
                    attendanceId: $existingAttendance?->id,
                    latitude: $data->latitude,
                    longitude: $data->longitude,
                    accuracyMeter: $data->accuracyMeter,
                    ipAddress: $data->ipAddress,
                    deviceInfo: $data->deviceInfo,
                    context: [
                        'work_date' => $workDate->toDateString(),
                        'office_location_id' => $policy->officeLocationId,
                        'reason' => 'UNIQUE_INDEX_COLLISION',
                    ],
                    occurredAt: $now,
                );

                throw new AlreadyCheckedInException(
                    message: 'Check-in sudah pernah direkam untuk hari ini.',
                    context: [
                        'attendance_id' => $existingAttendance?->id,
                        'user_id' => $data->userId,
                        'work_date' => $workDate->toDateString(),
                    ]
                );
            }

            throw $e;
        }
    }

    private function ensureCheckInWindowIsOpen(
        AttendancePolicyData $policy,
        Carbon $now,
        CheckInData $data
    ): void {
        if ($this->attendancePolicyService->isWithinCheckInWindow($policy, $now)) {
            return;
        }

        $this->attendanceLogService->logInvalidCheckOutAttempt( // sengaja tidak dipakai; check-in punya rejected yang lebih relevan di bawah
            userId: $data->userId,
            attendanceId: null,
            latitude: $data->latitude,
            longitude: $data->longitude,
            accuracyMeter: $data->accuracyMeter,
            ipAddress: $data->ipAddress,
            deviceInfo: $data->deviceInfo,
            context: [
                'office_location_id' => $policy->officeLocationId,
                'reason' => 'CHECK_IN_WINDOW_CLOSED',
            ],
            occurredAt: $now,
        );

        throw new AttendanceNotAllowedException(
            message: 'Saat ini tidak berada dalam window check-in yang diizinkan.',
            context: [
                'user_id' => $data->userId,
                'office_location_id' => $policy->officeLocationId,
                'reason' => 'CHECK_IN_WINDOW_CLOSED',
            ]
        );
    }

    private function validateQrOrFail(
        CheckInData $data,
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
                context: array_merge(
                    [
                        'office_location_id' => $policy->officeLocationId,
                        'work_date' => $this->resolveWorkDate($policy, $now)->toDateString(),
                    ],
                    $e->getContext()
                ),
                occurredAt: $now,
            );

            throw $e;
        }
    }

    private function validateLocationOrFail(
        CheckInData $data,
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
                context: array_merge(
                    [
                        'office_location_id' => $policy->officeLocationId,
                        'work_date' => $this->resolveWorkDate($policy, $now)->toDateString(),
                    ],
                    $e->getContext()
                ),
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

    private function assertCheckInIsAllowed(
        ?Attendance $attendance,
        CheckInData $data,
        Carbon $workDate
    ): void {
        if ($attendance === null) {
            return;
        }

        if ($attendance->check_in_at !== null) {
            throw new AlreadyCheckedInException(
                message: 'Check-in sudah pernah direkam untuk hari ini.',
                context: [
                    'attendance_id' => $attendance->id,
                    'user_id' => $data->userId,
                    'work_date' => $workDate->toDateString(),
                ]
            );
        }
    }

    private function buildAttendancePayload(
        CheckInData $data,
        AttendancePolicyData $policy,
        AttendanceQrToken $qrToken,
        LocationValidationResultData $locationResult,
        array $checkInStatus,
        Carbon $workDate,
        Carbon $now
    ): array {
        return [
            'user_id' => $data->userId,
            'office_location_id' => $policy->officeLocationId,
            'attendance_qr_token_id' => $qrToken->id,
            'work_date' => $workDate->toDateString(),

            'check_in_at' => $now,
            'check_in_recorded_at' => $now,
            'check_in_latitude' => $data->latitude,
            'check_in_longitude' => $data->longitude,
            'check_in_accuracy_meter' => $data->accuracyMeter,
            'check_in_status' => $checkInStatus['status'],
            'late_minutes' => (int) $checkInStatus['late_minutes'],

            'record_status' => AttendanceRecordStatus::ONGOING,
            'is_suspicious' => (bool) $locationResult->isSuspicious,
            'suspicious_reason' => $locationResult->reason,
        ];
    }

    private function createOrUpdateAttendance(?Attendance $attendance, array $payload): Attendance
    {
        if ($attendance === null) {
            return Attendance::query()->create($payload);
        }

        $attendance->fill($payload);
        $attendance->save();

        return $attendance;
    }

    private function writeSuccessLogs(
        Attendance $attendance,
        CheckInData $data,
        AttendancePolicyData $policy,
        LocationValidationResultData $locationResult,
        array $checkInStatus,
        Carbon $now
    ): void {
        $this->attendanceLogService->logCheckInSuccess(
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
                'check_in_status' => $checkInStatus['status']->value,
                'late_minutes' => (int) $checkInStatus['late_minutes'],
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
                message: 'Check-in berhasil tetapi terdeteksi kondisi lokasi yang mencurigakan.',
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

    private function isUniqueAttendanceViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = $e->errorInfo[1] ?? null;

        return $sqlState === '23000' || $sqlState === '23505' || $driverCode === 1062;
    }
}
