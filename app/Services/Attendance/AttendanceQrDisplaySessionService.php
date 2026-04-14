<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\AttendanceQrDisplaySession;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AttendanceQrDisplaySessionService
{
    public function create(OfficeLocation $office, User $actor, string $name, ?int $ttlDays = null): array
    {
        $ttlDays ??= (int) config('attendance.qr_display.session_ttl_days', 30);
        $ttlDays = max(1, $ttlDays);

        $token = Str::random(64);
        $expiresAt = now()->addDays($ttlDays);

        $session = AttendanceQrDisplaySession::query()->create([
            'office_location_id' => $office->id,
            'name' => trim($name),
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => Crypt::encryptString($token),
            'expires_at' => $expiresAt,
            'created_by' => $actor->id,
        ]);

        return [
            'session' => $session,
            'display_url' => $this->temporarySignedDisplayUrl($session, $token, $expiresAt),
        ];
    }

    public function revoke(AttendanceQrDisplaySession $session): void
    {
        if ($session->revoked_at !== null) {
            return;
        }

        $session->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function makeStatusUrlFromRequest(AttendanceQrDisplaySession $session, Request $request): ?string
    {
        $rawKey = trim((string) $request->query('key', ''));
        if ($rawKey === '') {
            return null;
        }

        return $this->temporarySignedStatusUrl($session, $rawKey, $session->expires_at ?? now()->addHour());
    }

    public function makeDisplayUrlForSession(AttendanceQrDisplaySession $session): ?string
    {
        $token = $this->decryptToken($session);

        if ($token === null) {
            return null;
        }

        $expiresAt = $session->expires_at ?? now()->addHour();

        return $this->temporarySignedDisplayUrl($session, $token, $expiresAt);
    }

    private function temporarySignedDisplayUrl(AttendanceQrDisplaySession $session, string $token, Carbon $expiresAt): string
    {
        return URL::temporarySignedRoute(
            'office-display.attendance.qr.show',
            $expiresAt,
            [
                'session' => $session->id,
                'key' => $token,
            ],
        );
    }

    private function temporarySignedStatusUrl(AttendanceQrDisplaySession $session, string $token, Carbon $expiresAt): string
    {
        return URL::temporarySignedRoute(
            'office-display.attendance.qr.status',
            $expiresAt,
            [
                'session' => $session->id,
                'key' => $token,
            ],
        );
    }

    private function decryptToken(AttendanceQrDisplaySession $session): ?string
    {
        $encrypted = trim((string) $session->token_encrypted);

        if ($encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}
