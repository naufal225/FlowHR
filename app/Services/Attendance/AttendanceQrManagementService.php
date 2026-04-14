<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Exceptions\Attendance\AttendancePolicyNotFoundException;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSetting;
use App\Models\OfficeLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AttendanceQrManagementService
{
    public function ensureCurrentForOffice(OfficeLocation $officeLocation): ?AttendanceQrToken
    {
        $token = $this->currentForOffice($officeLocation);

        if ($token !== null && $token->is_active && ! $token->is_expired) {
            return $token;
        }

        if ($token !== null && ! $token->is_active) {
            return $token;
        }

        $setting = AttendanceSetting::query()
            ->where('office_location_id', $officeLocation->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($setting === null) {
            return $token;
        }

        return $this->regenerate($officeLocation);
    }

    public function currentForOffice(OfficeLocation $officeLocation): ?AttendanceQrToken
    {
        return $this->currentTokenQuery($officeLocation->id)->first();
    }

    public function ensureFreshForOffice(OfficeLocation $officeLocation, int $graceSeconds = 3): ?AttendanceQrToken
    {
        $graceSeconds = max(0, $graceSeconds);

        return DB::transaction(function () use ($officeLocation, $graceSeconds): ?AttendanceQrToken {
            $lockedOffice = $this->lockOfficeForUpdate($officeLocation->id);
            $token = $this->currentTokenQuery($lockedOffice->id)->lockForUpdate()->first();

            if ($token === null) {
                $setting = $this->resolveActiveSetting($lockedOffice->id);

                if ($setting === null) {
                    return null;
                }

                return $this->createFreshToken($lockedOffice, $setting);
            }

            if (! $token->is_active) {
                return $token;
            }

            $timezone = $lockedOffice->timezone ?? config('app.timezone', 'Asia/Jakarta');
            $threshold = now($timezone)->addSeconds($graceSeconds);

            if ($token->expired_at !== null && $token->expired_at->gt($threshold)) {
                return $token;
            }

            $setting = $this->resolveActiveSetting($lockedOffice->id);

            if ($setting === null) {
                return $token;
            }

            return $this->createFreshToken($lockedOffice, $setting);
        });
    }

    public function regenerate(OfficeLocation $officeLocation): AttendanceQrToken
    {
        return DB::transaction(function () use ($officeLocation): AttendanceQrToken {
            $lockedOffice = $this->lockOfficeForUpdate($officeLocation->id);
            $setting = $this->resolveActiveSetting($lockedOffice->id);

            if ($setting === null) {
                throw new AttendancePolicyNotFoundException([
                    'office_location_id' => $lockedOffice->id,
                ]);
            }

            return $this->createFreshToken($lockedOffice, $setting);
        });
    }

    public function invalidate(OfficeLocation $officeLocation): int
    {
        return AttendanceQrToken::query()
            ->where('office_location_id', $officeLocation->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    private function currentTokenQuery(int $officeLocationId)
    {
        return AttendanceQrToken::query()
            ->where('office_location_id', $officeLocationId)
            ->orderByDesc('is_active')
            ->orderByDesc('generated_at')
            ->orderByDesc('id');
    }

    private function resolveActiveSetting(int $officeLocationId): ?AttendanceSetting
    {
        return AttendanceSetting::query()
            ->where('office_location_id', $officeLocationId)
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    private function lockOfficeForUpdate(int $officeLocationId): OfficeLocation
    {
        /** @var OfficeLocation $office */
        $office = OfficeLocation::query()
            ->whereKey($officeLocationId)
            ->lockForUpdate()
            ->firstOrFail();

        return $office;
    }

    private function createFreshToken(OfficeLocation $officeLocation, AttendanceSetting $setting): AttendanceQrToken
    {
        AttendanceQrToken::query()
            ->where('office_location_id', $officeLocation->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $generatedAt = Carbon::now($officeLocation->timezone ?? config('app.timezone', 'Asia/Jakarta'));

        return AttendanceQrToken::query()->create([
            'office_location_id' => $officeLocation->id,
            'token' => Str::upper(Str::random(24)),
            'generated_at' => $generatedAt,
            'expired_at' => $generatedAt->copy()->addSeconds((int) $setting->qr_rotation_seconds),
            'is_active' => true,
        ]);
    }
}
