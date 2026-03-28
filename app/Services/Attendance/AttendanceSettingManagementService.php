<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Models\AttendanceSetting;
use Illuminate\Support\Facades\DB;

class AttendanceSettingManagementService
{
    public function upsert(array $attributes): AttendanceSetting
    {
        return DB::transaction(function () use ($attributes): AttendanceSetting {
            $setting = AttendanceSetting::query()
                ->where('office_location_id', $attributes['office_location_id'])
                ->latest('id')
                ->first();

            if ($setting === null) {
                $setting = AttendanceSetting::query()->create($attributes);
            } else {
                $setting->fill($attributes);
                $setting->save();
            }

            if ($setting->is_active) {
                AttendanceSetting::query()
                    ->where('office_location_id', $setting->office_location_id)
                    ->where('id', '!=', $setting->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }

            return $setting->fresh(['officeLocation']);
        });
    }
}
