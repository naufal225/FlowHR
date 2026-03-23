<?php

namespace App\Models;

use App\Traits\HasDualStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Overtime extends Model
{
    use HasDualStatus;

    protected $fillable = [
        'employee_id',
        'approver_1_id',
        'approver_2_id',
        'date_start',
        'date_end',
        'total',
        'status_1',
        'status_2',
        'note_1',
        'note_2',
        'marked_down',
        'locked_by',
        'locked_at',
        'approved_date',
        'rejected_date',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'total' => 'decimal:2',
        'marked_down' => 'boolean',
        'locked_at' => 'datetime',
        'approved_date' => 'datetime',
        'rejected_date' => 'datetime',
        'employee_id' => 'integer',
        'approver_1_id' => 'integer',
        'approver_2_id' => 'integer',
        'locked_by' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver(): ?User
    {
        return $this->employee?->division?->leader;
    }

    public function approver1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_1_id');
    }

    public function approver2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_2_id');
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'overtime_id');
    }

    public function getApprover1NameAttribute(): ?string
    {
        return $this->approver1?->name;
    }

    public function getApprover2NameAttribute(): ?string
    {
        return $this->approver2?->name;
    }

    public function scopeApproved($query)
    {
        return $query->where('status_1', 'approved')
            ->where('status_2', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where(function ($q) {
            $q->where('status_1', 'pending')
              ->orWhere('status_2', 'pending');
        });
    }

    public function scopeRejected($query)
    {
        return $query->where(function ($q) {
            $q->where('status_1', 'rejected')
              ->orWhere('status_2', 'rejected');
        });
    }
}
