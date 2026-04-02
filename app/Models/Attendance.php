<?php

namespace App\Models;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceRecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'office_location_id',
        'attendance_qr_token_id',
        'overtime_id',
        'work_date',

        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy_meter',
        'check_in_recorded_at',
        'check_in_status',

        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy_meter',
        'check_out_recorded_at',
        'check_out_status',

        'record_status',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'is_suspicious',
        'suspicious_reason',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'office_location_id' => 'integer',
        'attendance_qr_token_id' => 'integer',
        'overtime_id' => 'integer',

        'work_date' => 'date',

        'check_in_at' => 'datetime',
        'check_in_latitude' => 'decimal:7',
        'check_in_longitude' => 'decimal:7',
        'check_in_accuracy_meter' => 'decimal:2',
        'check_in_recorded_at' => 'datetime',
        'check_in_status' => AttendanceCheckInStatus::class,

        'check_out_at' => 'datetime',
        'check_out_latitude' => 'decimal:7',
        'check_out_longitude' => 'decimal:7',
        'check_out_accuracy_meter' => 'decimal:2',
        'check_out_recorded_at' => 'datetime',
        'check_out_status' => AttendanceCheckOutStatus::class,

        'record_status' => AttendanceRecordStatus::class,

        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'overtime_minutes' => 'integer',

        'is_suspicious' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'office_location_id');
    }

    public function attendanceQrToken(): BelongsTo
    {
        return $this->belongsTo(AttendanceQrToken::class, 'attendance_qr_token_id');
    }

    public function overtime(): BelongsTo
    {
        return $this->belongsTo(Overtime::class, 'overtime_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'attendance_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'attendance_id');
    }

    public function latestCorrection(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class, 'attendance_id')
            ->latestOfMany('created_at');
    }

    public function approvedLeaves()
    {
        return $this->hasMany(Leave::class, 'employee_id', 'user_id')
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $this->work_date)
            ->whereDate('date_end', '>=', $this->work_date);
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->record_status === AttendanceRecordStatus::COMPLETE;
    }

    public function getIsIncompleteAttribute(): bool
    {
        return $this->record_status === AttendanceRecordStatus::INCOMPLETE;
    }

    public function getIsOngoingAttribute(): bool
    {
        return $this->record_status === AttendanceRecordStatus::ONGOING;
    }

    public function getIsLateAttribute(): bool
    {
        return $this->check_in_status === AttendanceCheckInStatus::LATE;
    }

    public function getIsOnTimeAttribute(): bool
    {
        return $this->check_in_status === AttendanceCheckInStatus::ON_TIME;
    }

    public function getIsCheckedInAttribute(): bool
    {
        return $this->check_in_status !== AttendanceCheckInStatus::NONE
            && $this->check_in_at !== null;
    }

    public function getIsCheckedOutAttribute(): bool
    {
        return $this->check_out_status !== AttendanceCheckOutStatus::NONE
            && $this->check_out_at !== null;
    }

    public function getIsEarlyLeaveAttribute(): bool
    {
        return $this->check_out_status === AttendanceCheckOutStatus::EARLY_LEAVE;
    }

    public function getCheckInStatusLabelAttribute(): string
    {
        return $this->check_in_status->label();
    }

    public function getCheckOutStatusLabelAttribute(): string
    {
        return $this->check_out_status->label();
    }

    public function getRecordStatusLabelAttribute(): string
    {
        return $this->record_status->label();
    }
}
