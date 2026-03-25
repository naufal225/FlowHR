<?php

namespace App\Services\Attendance;

use App\Data\Attendance\AttendancePolicyData;
use App\Exceptions\Attendance\AttendanceException;
use App\Exceptions\Attendance\ExpiredQrTokenException;
use App\Exceptions\Attendance\InactiveQrTokenException;
use App\Exceptions\Attendance\InvalidQrTokenException;
use App\Exceptions\Attendance\OfficeLocationMismatchException;
use App\Models\AttendanceQrToken;
use Carbon\Carbon;

class AttendanceQrValidationService
{
    protected AttendancePolicyService $attendancePolicyService;

    public function __construct(AttendancePolicyService $attendancePolicyService) {
        $this->attendancePolicyService = $attendancePolicyService;
    }

    /**
     * Validasi token QR untuk office tertentu.
     *
     * Return model token yang valid supaya nanti bisa dipakai
     * untuk logging / audit / referensi office.
     */
    public function validateForOffice(
        int $userId,
        int $expectedOfficeLocationId,
        string $rawToken,
        ?Carbon $now = null
    ): AttendanceQrToken {
        $now ??= now();

        $normalizedToken = $this->normalizeToken($rawToken);

        if ($normalizedToken === '') {
            throw new InvalidQrTokenException(
                message: 'QR token wajib diisi.',
                context: [
                    'office_location_id' => $expectedOfficeLocationId,
                ]
            );
        }

        $qrToken = AttendanceQrToken::query()
            ->select([
                'id',
                'office_location_id',
                'token',
                'is_active',
                'expires_at',
            ])
            ->where('token', $normalizedToken)
            ->first();

        if (! $qrToken) {
            throw new InvalidQrTokenException(
                message: 'QR token tidak valid.',
                context: [
                    'office_location_id' => $expectedOfficeLocationId,
                    'token_preview' => $this->maskToken($normalizedToken),
                ]
            );
        }

        if ((int) $qrToken->office_location_id !== $expectedOfficeLocationId) {
            throw new OfficeLocationMismatchException(
                message: 'QR token tidak sesuai dengan lokasi kantor user.',
                context: [
                    'expected_office_location_id' => $expectedOfficeLocationId,
                    'actual_office_location_id' => (int) $qrToken->office_location_id,
                    'qr_token_id' => $qrToken->id,
                    'token_preview' => $this->maskToken($normalizedToken),
                ]
            );
        }

        if (! $qrToken->is_active) {
            throw new InactiveQrTokenException(
                message: 'QR token sudah tidak aktif.',
                context: [
                    'qr_token_id' => $qrToken->id,
                    'office_location_id' => $qrToken->office_location_id,
                    'token_preview' => $this->maskToken($normalizedToken),
                ]
            );
        }

        $policyData = $this->attendancePolicyService->getPolicyForUser($userId, $now);

        $expiresAt = $this->normalizeToPolicyTimezone(Carbon::parse($qrToken->expires_at), $policyData);

        if ($expiresAt && $expiresAt->lte($now)) {
            throw new ExpiredQrTokenException(
                message: 'QR token sudah expired.',
                context: [
                    'qr_token_id' => $qrToken->id,
                    'office_location_id' => $qrToken->office_location_id,
                    'expires_at' => $expiresAt->toDateTimeString(),
                    'token_preview' => $this->maskToken($normalizedToken),
                ]
            );
        }

        return $qrToken;
    }

    /**
     * Khusus kalau nanti lo butuh cek cepat:
     * return true/false tanpa exception.
     */
    public function isValidForOffice(
        int $userId,
        int $expectedOfficeLocationId,
        string $rawToken,
        ?Carbon $now = null
    ): bool {
        try {
            $this->validateForOffice($userId, $expectedOfficeLocationId, $rawToken, $now);

            return true;
        } catch (AttendanceException) {
            return false;
        }
    }

    private function normalizeToken(string $rawToken): string
    {
        return trim($rawToken);
    }

    private function normalizeToPolicyTimezone(Carbon $at, AttendancePolicyData $policy): Carbon
    {
        return $at->copy()->setTimezone($policy->timezone);
    }

    /**
     * Jangan log token full mentah.
     */
    private function maskToken(string $token): string
    {
        $length = mb_strlen($token);

        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return mb_substr($token, 0, 4)
            . str_repeat('*', $length - 8)
            . mb_substr($token, -4);
    }
}
