<?php

namespace App\Enums;

enum AttendanceCheckInStatus: string
{
    case NONE = 'none';
    case ON_TIME = 'on_time';
    case LATE = 'late';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Belum Check-in',
            self::ON_TIME => 'Tepat Waktu',
            self::LATE => 'Terlambat',
        };
    }
}
