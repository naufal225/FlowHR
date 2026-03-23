<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceQrToken extends Model
{
    protected $fillable = [
        'office_location_id',
        'token',
        'generated_at',
        'expired_at',
        'is_active',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expired_at' => 'datetime',
        'is_active' => 'boolean',
        'office_location_id' => 'integer',
    ];

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'office_location_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'attendance_qr_token_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_at?->isPast() ?? false;
    }

    public function getIsCurrentlyValidAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired;
    }
}
