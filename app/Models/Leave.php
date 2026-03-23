<?php

namespace App\Models;

use App\Traits\HasDualStatus;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasDualStatus;
    protected $fillable = [
        'employee_id',
        'approver_1_id',
        'date_start',
        'date_end',
        'reason',
        'status_1',
        'note_1',
        'approved_date',
        'rejected_date'
    ];

    protected function finalStatusColumns(): array
    {
        return ['status_1'];
    }


    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'approved_date' => 'datetime',
        'rejected_date' => 'datetime',
        'employee_id' => 'integer',
        'approver_1_id' => 'integer',
        'approver_2_id' => 'integer',
    ];

    public function approver()
    {
        return $this->hasOneThrough(
            User::class,       // Tujuan: user leader
            Division::class,   // Perantara: division
            'id',              // PK di divisions
            'id',              // PK di users (leader)
            'employee_id',     // FK di leaves → users.id (employee)
            'leader_id'        // FK di divisions → users.id (leader)
        );
    }

    public function getApproverAttribute()
    {
        return $this->employee?->division?->leader; // bisa null-safe
    }

    public function approver1()
    {
        return $this->belongsTo(User::class, 'approver_1_id');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approver_2_id');
    }

    public function getApprover1NameAttribute(): ?string
    {
        return $this->approver1?->name;
    }

}
