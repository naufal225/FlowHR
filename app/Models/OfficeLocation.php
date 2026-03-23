<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeLocation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meter',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meter' => 'integer',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'office_location_id');
    }

    public function attendanceSettings(): HasMany
    {
        return $this->hasMany(AttendanceSetting::class, 'office_location_id');
    }

    public function activeAttendanceSettings(): HasMany
    {
        return $this->attendanceSettings()->where('is_active', true);
    }

    public function attendanceQrTokens(): HasMany
    {
        return $this->hasMany(AttendanceQrToken::class, 'office_location_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'office_location_id');
    }
}
