<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'office_location_id',
        'work_start_time',
        'work_end_time',
        'late_tolerance_minutes',
        'qr_rotation_seconds',
        'min_location_accuracy_meter',
        'is_active',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',
        'qr_rotation_seconds' => 'integer',
        'min_location_accuracy_meter' => 'integer',
        'is_active' => 'boolean',
    ];

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'office_location_id');
    }
}
