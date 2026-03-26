<?php

namespace App\Models;

use App\Enums\AttendanceLogActionStatus;
use App\Enums\AttendanceLogActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'action_type',
        'action_status',
        'latitude',
        'longitude',
        'accuracy_meter',
        'ip_address',
        'device_info',
        'message',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'attendance_id' => 'integer',
        'user_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy_meter' => 'decimal:2',
        'occurred_at' => 'datetime',
        'context' => 'json',
        'action_type' => AttendanceLogActionType::class,
        'action_status' => AttendanceLogActionStatus::class,
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionTypeLabelAttribute(): string
    {
        return $this->action_type->label();
    }

    public function getActionStatusLabelAttribute(): string
    {
        return $this->action_status->label();
    }
}
