<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceQrDisplaySession extends Model
{
    protected $fillable = [
        'office_location_id',
        'name',
        'token_hash',
        'token_encrypted',
        'expires_at',
        'revoked_at',
        'last_seen_at',
        'created_by',
    ];

    protected $casts = [
        'office_location_id' => 'integer',
        'created_by' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class, 'office_location_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        return $this->expires_at?->lte($at) ?? true;
    }

    public function isActive(?CarbonInterface $at = null): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired($at);
    }
}
