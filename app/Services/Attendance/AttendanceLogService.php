<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceLogActionStatus;
use App\Enums\AttendanceLogActionType;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class AttendanceLogService
{
    public function __construct(){}

    /**
     * Catat attempt check-in.
     * Dipakai saat request check-in masuk dan ingin audit trail percobaan awal.
     */
    public function logCheckInAttempt(
        int $userId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: null,
            userId: $userId,
            actionType: AttendanceLogActionType::CHECK_IN_ATTEMPT,
            actionStatus: AttendanceLogActionStatus::SUCCESS,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: 'Percobaan check-in diterima.',
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Catat final success check-in.
     * Kalau suspicious, tetap SUCCESS tapi diberi flag is_suspicious.
     */
    public function logCheckInSuccess(
        int $attendanceId,
        int $userId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        bool $isSuspicious = false,
        ?string $suspiciousReason = null,
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        $finalContext = array_merge($context, [
            'attendance_id' => $attendanceId,
            'is_suspicious' => $isSuspicious,
            'suspicious_reason' => $suspiciousReason,
        ]);

        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::CHECK_IN_SUCCESS,
            actionStatus: AttendanceLogActionStatus::SUCCESS,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: $isSuspicious
            ? 'Check-in berhasil direkam dengan penanda suspicious.'
            : 'Check-in berhasil direkam.',
            context: $finalContext,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Catat attempt check-out.
     */
    public function logCheckOutAttempt(
        int $userId,
        ?int $attendanceId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::CHECK_OUT_ATTEMPT,
            actionStatus: AttendanceLogActionStatus::SUCCESS,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: 'Percobaan check-out diterima.',
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Catat final success check-out.
     * Kalau suspicious, tetap SUCCESS tapi diberi flag is_suspicious.
     */
    public function logCheckOutSuccess(
        int $attendanceId,
        int $userId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        bool $isSuspicious = false,
        ?string $suspiciousReason = null,
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        $finalContext = array_merge($context, [
            'attendance_id' => $attendanceId,
            'is_suspicious' => $isSuspicious,
            'suspicious_reason' => $suspiciousReason,
        ]);

        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::CHECK_OUT_SUCCESS,
            actionStatus: AttendanceLogActionStatus::SUCCESS,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: $isSuspicious
            ? 'Check-out berhasil direkam dengan penanda suspicious.'
            : 'Check-out berhasil direkam.',
            context: $finalContext,
            occurredAt: $occurredAt,
        );
    }

    /**
     * QR ditolak.
     * Dipakai untuk final failure sebelum attendance row tercipta / berubah.
     */
    public function logQrRejected(
        int $userId,
        string $message,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: null,
            userId: $userId,
            actionType: AttendanceLogActionType::QR_REJECTED,
            actionStatus: AttendanceLogActionStatus::REJECTED,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: $message,
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Lokasi ditolak.
     */
    public function logLocationRejected(
        int $userId,
        string $message,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: null,
            userId: $userId,
            actionType: AttendanceLogActionType::LOCATION_REJECTED,
            actionStatus: AttendanceLogActionStatus::REJECTED,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: $message,
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Percobaan check-in ganda.
     */
    public function logDuplicateCheckInAttempt(
        int $userId,
        ?int $attendanceId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::DUPLICATE_CHECKIN_ATTEMPT,
            actionStatus: AttendanceLogActionStatus::REJECTED,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: 'Percobaan check-in ganda ditolak.',
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Percobaan check-out tidak valid.
     */
    public function logInvalidCheckOutAttempt(
        int $userId,
        ?int $attendanceId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::INVALID_CHECKOUT_ATTEMPT,
            actionStatus: AttendanceLogActionStatus::REJECTED,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: 'Percobaan check-out tidak valid.',
            context: $context,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Aktivitas mencurigakan.
     * Status tetap SUCCESS? Tidak.
     * Untuk event security, enum sudah menyediakan SUSPICIOUS dan itu yang lebih jujur.
     */
    public function logSuspiciousActivity(
        int $userId,
        string $message,
        ?int $attendanceId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        $finalContext = array_merge($context, [
            'is_suspicious' => true,
        ]);

        return $this->safeCreateLog(
            attendanceId: $attendanceId,
            userId: $userId,
            actionType: AttendanceLogActionType::SUSPICIOUS_ACTIVITY,
            actionStatus: AttendanceLogActionStatus::SUSPICIOUS,
            latitude: $latitude,
            longitude: $longitude,
            accuracyMeter: $accuracyMeter,
            ipAddress: $ipAddress,
            deviceInfo: $deviceInfo,
            message: $message,
            context: $finalContext,
            occurredAt: $occurredAt,
        );
    }

    /**
     * Satu pintu create log.
     * Digunakan ketika caller memang butuh fleksibilitas lebih.
     */
    public function createLog(
        ?int $attendanceId,
        int $userId,
        AttendanceLogActionType $actionType,
        AttendanceLogActionStatus $actionStatus,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        ?string $message = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): AttendanceLog {
        $occurredAt ??= now();

        $payload = [
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'action_status' => $actionStatus,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meter' => $accuracyMeter,
            'ip_address' => $this->normalizeIpAddress($ipAddress),
            'device_info' => $this->normalizeDeviceInfo($deviceInfo),
            'message' => $message ?? $this->defaultMessageFor($actionType, $actionStatus),
            'context' => $this->sanitizeContext(
                $this->buildBaseContext(
                    attendanceId: $attendanceId,
                    userId: $userId,
                    actionType: $actionType,
                    actionStatus: $actionStatus,
                    latitude: $latitude,
                    longitude: $longitude,
                    accuracyMeter: $accuracyMeter,
                    ipAddress: $ipAddress,
                    deviceInfo: $deviceInfo,
                    extraContext: $context,
                )
            ),
            'occurred_at' => $occurredAt,
        ];

        return AttendanceLog::create($payload);
    }

    /**
     * Wrapper aman:
     * kalau log gagal, flow bisnis utama jangan ikut gagal.
     */
    private function safeCreateLog(
        ?int $attendanceId,
        int $userId,
        AttendanceLogActionType $actionType,
        AttendanceLogActionStatus $actionStatus,
        ?float $latitude = null,
        ?float $longitude = null,
        ?float $accuracyMeter = null,
        ?string $ipAddress = null,
        ?string $deviceInfo = null,
        ?string $message = null,
        array $context = [],
        ?Carbon $occurredAt = null
    ): ?AttendanceLog {
        try {
            return $this->createLog(
                attendanceId: $attendanceId,
                userId: $userId,
                actionType: $actionType,
                actionStatus: $actionStatus,
                latitude: $latitude,
                longitude: $longitude,
                accuracyMeter: $accuracyMeter,
                ipAddress: $ipAddress,
                deviceInfo: $deviceInfo,
                message: $message,
                context: $context,
                occurredAt: $occurredAt,
            );
        } catch (Throwable $e) {
            $this->reportLoggingFailure(
                actionType: $actionType,
                userId: $userId,
                attendanceId: $attendanceId,
                exception: $e,
                originalContext: $context
            );

            return null;
        }
    }

    private function buildBaseContext(
        ?int $attendanceId,
        int $userId,
        AttendanceLogActionType $actionType,
        AttendanceLogActionStatus $actionStatus,
        ?float $latitude,
        ?float $longitude,
        ?float $accuracyMeter,
        ?string $ipAddress,
        ?string $deviceInfo,
        array $extraContext = []
    ): array {
        return array_merge([
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'action_type' => $actionType->value,
            'action_status' => $actionStatus->value,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meter' => $accuracyMeter,
            'ip_address' => $this->normalizeIpAddress($ipAddress),
            'device_info' => $this->normalizeDeviceInfo($deviceInfo),
        ], $extraContext);
    }

    private function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = $this->truncateString($value, 500);
                continue;
            }

            if (is_scalar($value) || is_array($value) || is_bool($value)) {
                $sanitized[$key] = $value;
                continue;
            }

            $sanitized[$key] = (string) json_encode($value);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        $sensitiveFragments = [
            'token',
            'auth',
            'authorization',
            'password',
            'secret',
            'credential',
            'bearer',
        ];

        foreach ($sensitiveFragments as $fragment) {
            if (str_contains($key, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeIpAddress(?string $ipAddress): ?string
    {
        if ($ipAddress === null) {
            return null;
        }

        $trimmed = trim($ipAddress);

        if ($trimmed === '') {
            return null;
        }

        return $this->truncateString($trimmed, 45);
    }

    private function normalizeDeviceInfo(?string $deviceInfo): ?string
    {
        if ($deviceInfo === null) {
            return null;
        }

        $trimmed = trim($deviceInfo);

        if ($trimmed === '') {
            return null;
        }

        return $this->truncateString($trimmed, 255);
    }

    private function truncateString(string $value, int $maxLength): string
    {
        return mb_strlen($value) > $maxLength
            ? mb_substr($value, 0, $maxLength)
            : $value;
    }

    private function defaultMessageFor(
        AttendanceLogActionType $actionType,
        AttendanceLogActionStatus $actionStatus
    ): string {
        return match ($actionType) {
            AttendanceLogActionType::CHECK_IN_ATTEMPT => 'Percobaan check-in dicatat.',
            AttendanceLogActionType::CHECK_IN_SUCCESS => 'Check-in berhasil direkam.',
            AttendanceLogActionType::CHECK_OUT_ATTEMPT => 'Percobaan check-out dicatat.',
            AttendanceLogActionType::CHECK_OUT_SUCCESS => 'Check-out berhasil direkam.',
            AttendanceLogActionType::QR_REJECTED => 'QR ditolak.',
            AttendanceLogActionType::LOCATION_REJECTED => 'Lokasi ditolak.',
            AttendanceLogActionType::DUPLICATE_CHECKIN_ATTEMPT => 'Percobaan check-in ganda ditolak.',
            AttendanceLogActionType::INVALID_CHECKOUT_ATTEMPT => 'Percobaan check-out tidak valid.',
            AttendanceLogActionType::SUSPICIOUS_ACTIVITY => $actionStatus === AttendanceLogActionStatus::SUSPICIOUS
            ? 'Aktivitas mencurigakan terdeteksi.'
            : 'Aktivitas dicatat.',
        };
    }

    private function reportLoggingFailure(
        AttendanceLogActionType $actionType,
        int $userId,
        ?int $attendanceId,
        Throwable $exception,
        array $originalContext = []
    ): void {
        try {
            Log::warning('Failed to persist attendance log.', [
                'action_type' => $actionType->value,
                'user_id' => $userId,
                'attendance_id' => $attendanceId,
                'error' => $exception->getMessage(),
                'context' => $this->sanitizeContext($originalContext),
            ]);
        } catch (Throwable) {
            // sengaja diam; jangan sampai failure reporting bikin flow utama ikut rusak
        }
    }
}
