<?php

namespace App\Enums;

enum AttendanceCheckOutStatus: string
{
    case NONE = 'none';
    case NORMAL = 'normal';
    case EARLY_LEAVE = 'early_leave';
    case OVERTIME = 'overtime';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Belum Check-out',
            self::NORMAL => 'Normal',
            self::EARLY_LEAVE => 'Pulang Lebih Awal',
            self::OVERTIME => 'Lembur'
        };
    }
}
