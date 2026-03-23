<?php

namespace App\Enums;

enum AttendanceLogActionStatus: string
{
    case SUCCESS = 'success';
    case REJECTED = 'rejected';
    case SUSPICIOUS = 'suspicious';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Berhasil',
            self::REJECTED => 'Ditolak',
            self::SUSPICIOUS => 'Mencurigakan',
        };
    }
}
