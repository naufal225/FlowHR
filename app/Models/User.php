<?php

namespace App\Models;

use App\Enums\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'division_id',
        'office_location_id',
        'url_profile',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'office_location_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'office_location_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'employee_id');
    }

    public function approvedLeaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'employee_id')
            ->where('status_1', 'approved');
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    public function officialTravels(): HasMany
    {
        return $this->hasMany(OfficialTravel::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class, 'employee_id');
    }

    public function approvedOvertimes(): HasMany
    {
        return $this->hasMany(Overtime::class, 'employee_id')
            ->where('status_1', 'approved')
            ->where('status_2', 'approved');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function leavesPending()
    {
        return $this->leaves()->where('status_1', 'pending');
    }

    public function reimbursementsPending()
    {
        return $this->reimbursements()->where('status', 'pending');
    }

    public function officialTravelsPending()
    {
        return $this->officialTravels()->where('status', 'pending');
    }

    public function overtimesPending()
    {
        return $this->overtimes()->where(function ($q) {
            $q->where('status_1', 'pending')
              ->orWhere('status_2', 'pending');
        });
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function hasActiveRole(string $roleName): bool
    {
        return Session::get('active_role') === $roleName;
    }

    public function getActiveRole(): ?string
    {
        return Session::get('active_role');
    }

    public function getRoleArray(): string
    {
        return $this->roles()
            ->pluck('name')
            ->map(fn (string $name) => Roles::tryFrom($name)?->label() ?? Str::headline($name))
            ->implode(', ');
    }
}
