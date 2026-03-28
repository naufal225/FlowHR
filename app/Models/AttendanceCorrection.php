<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrection extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_check_in_time',
        'requested_check_out_time',
        'reason',
        'status',
        'reviewer_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'attendance_id' => 'integer',
        'reviewed_by' => 'integer',
        'requested_check_in_time' => 'datetime',
        'requested_check_out_time' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getAttendanceDateAttribute()
    {
        return $this->attendance?->work_date;
    }
}
