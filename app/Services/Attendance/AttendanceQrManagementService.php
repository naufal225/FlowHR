<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Exceptions\Attendance\AttendancePolicyNotFoundException;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSetting;
use App\Models\OfficeLocation;
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
        return AttendanceQrToken::query()
            ->where('office_location_id', $officeLocation->id)
            ->orderByDesc('is_active')
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->first();
    }

    public function regenerate(OfficeLocation $officeLocation): AttendanceQrToken
    {
        $setting = AttendanceSetting::query()
            ->where('office_location_id', $officeLocation->id)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($setting === null) {
            throw new AttendancePolicyNotFoundException([
                'office_location_id' => $officeLocation->id,
            ]);
        }

        return DB::transaction(function () use ($officeLocation, $setting): AttendanceQrToken {
            AttendanceQrToken::query()
                ->where('office_location_id', $officeLocation->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

            $generatedAt = now($officeLocation->timezone ?? config('app.timezone', 'Asia/Jakarta'));

            return AttendanceQrToken::query()->create([
                'office_location_id' => $officeLocation->id,
                'token' => Str::upper(Str::random(24)),
                'generated_at' => $generatedAt,
                'expired_at' => $generatedAt->copy()->addSeconds((int) $setting->qr_rotation_seconds),
                'is_active' => true,
            ]);
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
}
