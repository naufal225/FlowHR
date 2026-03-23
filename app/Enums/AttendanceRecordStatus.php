<?php

namespace App\Enums;

enum AttendanceRecordStatus: string
{
    case ONGOING = 'ongoing';
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::ONGOING => 'Sedang Berjalan',
            self::COMPLETE => 'Lengkap',
            self::INCOMPLETE => 'Tidak Lengkap',
        };
    }
}
