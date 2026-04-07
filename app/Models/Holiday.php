<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $table = 'holidays';

    protected $fillable = [
        'start_from',
        'end_at',
        'name'
    ];

    protected $casts = [
        'start_from' => 'date',
        'end_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Holiday $holiday): void {
            if ($holiday->start_from === null) {
                return;
            }

            $startDate = Carbon::parse($holiday->start_from)->startOfDay();
            $endDate = $holiday->end_at !== null
                ? Carbon::parse($holiday->end_at)->startOfDay()
                : $startDate->copy();

            if ($endDate->lt($startDate)) {
                $endDate = $startDate->copy();
            }

            $holiday->start_from = $startDate->toDateString();
            $holiday->end_at = $endDate->toDateString();
        });
    }
}
