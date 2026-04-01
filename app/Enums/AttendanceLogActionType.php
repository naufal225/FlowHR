<?php

namespace App\Enums;

enum AttendanceLogActionType: string
{
    case CHECK_IN_ATTEMPT = 'check_in_attempt';
    case CHECK_IN_SUCCESS = 'check_in_success';
    case CHECK_OUT_ATTEMPT = 'check_out_attempt';
    case CHECK_OUT_SUCCESS = 'check_out_success';
    case QR_REJECTED = 'qr_rejected';
    case LOCATION_REJECTED = 'location_rejected';
    case DUPLICATE_CHECKIN_ATTEMPT = 'duplicate_checkin_attempt';
    case INVALID_CHECKOUT_ATTEMPT = 'invalid_checkout_attempt';
    case SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    case CORRECTION_SUBMITTED = 'correction_submitted';
    case CORRECTION_APPROVED = 'correction_approved';
    case CORRECTION_REJECTED = 'correction_rejected';
    case CORRECTION_APPLIED = 'correction_applied';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::CHECK_IN_ATTEMPT => 'Percobaan Check-in',
            self::CHECK_IN_SUCCESS => 'Check-in Berhasil',
            self::CHECK_OUT_ATTEMPT => 'Percobaan Check-out',
            self::CHECK_OUT_SUCCESS => 'Check-out Berhasil',
            self::QR_REJECTED => 'QR Ditolak',
            self::LOCATION_REJECTED => 'Lokasi Ditolak',
            self::DUPLICATE_CHECKIN_ATTEMPT => 'Percobaan Check-in Ganda',
            self::INVALID_CHECKOUT_ATTEMPT => 'Percobaan Check-out Tidak Valid',
            self::SUSPICIOUS_ACTIVITY => 'Aktivitas Mencurigakan',
            self::CORRECTION_SUBMITTED => 'Koreksi Diajukan',
            self::CORRECTION_APPROVED => 'Koreksi Disetujui',
            self::CORRECTION_REJECTED => 'Koreksi Ditolak',
            self::CORRECTION_APPLIED => 'Koreksi Diterapkan',
        };
    }
}
