<?php

namespace Database\Seeders;

use App\Enums\AttendanceCheckInStatus;
use App\Enums\AttendanceCheckOutStatus;
use App\Enums\AttendanceLogActionStatus;
use App\Enums\AttendanceLogActionType;
use App\Enums\AttendanceRecordStatus;
use App\Enums\Roles;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSetting;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\OfficeLocation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceOfficeEmployeeSeeder extends Seeder
{
    private const TIMEZONE = 'Asia/Jakarta';
    private const WORK_START = '09:00:00';
    private const WORK_END = '17:00:00';
    private const DATE_START = '2026-02-01';
    private const DATE_END = '2026-04-08';
    private const DEMO_TODAY_EMPTY = '2026-04-09';

    public function run(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        DB::table('attendance_logs')->truncate();
        DB::table('attendance_corrections')->truncate();
        DB::table('attendances')->truncate();
        DB::table('attendance_qr_tokens')->truncate();
        DB::table('attendance_settings')->truncate();
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $office = OfficeLocation::query()->where('is_active', true)->first();
        if ($office === null) {
            $this->command->warn('Seeder attendance dilewati: office location aktif tidak ditemukan.');

            return;
        }

        AttendanceSetting::query()->create([
            'office_location_id' => $office->id,
            'work_start_time' => self::WORK_START,
            'work_end_time' => self::WORK_END,
            'late_tolerance_minutes' => 15,
            'qr_rotation_seconds' => 30,
            'min_location_accuracy_meter' => 50,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AttendanceQrToken::query()->create([
            'office_location_id' => $office->id,
            'token' => sha1('showcase-attendance-token-2026'),
            'generated_at' => now()->subMinute(),
            'expired_at' => now()->addHour(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employees = User::query()
            ->select(['id', 'name', 'email', 'office_location_id', 'is_active'])
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', Roles::Employee->value))
            ->orderBy('id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn('Seeder attendance dilewati: employee aktif tidak ditemukan.');

            return;
        }

        $rangeStart = CarbonImmutable::parse(self::DATE_START, self::TIMEZONE)->startOfDay();
        $rangeEnd = CarbonImmutable::parse(self::DATE_END, self::TIMEZONE)->startOfDay();
        $holidayDates = $this->holidayDateSet($rangeStart, $rangeEnd);
        $dates = [];
        for ($cursor = $rangeStart; $cursor->lte($rangeEnd); $cursor = $cursor->addDay()) {
            if (! $cursor->isWeekend() && ! isset($holidayDates[$cursor->toDateString()])) {
                $dates[] = $cursor;
            }
        }

        $approvedLeavesByUser = $this->buildApprovedLeaveRanges($rangeStart, $rangeEnd);

        $attendanceRows = [];
        foreach ($employees as $employee) {
            $isAkbar = strtolower($employee->email) === 'akbar@gmail.com' || strtolower($employee->name) === 'akbar';
            $officeLocationId = (int) ($employee->office_location_id ?? $office->id);

            foreach ($dates as $workDate) {
                if ($this->isOnApprovedLeave($approvedLeavesByUser[$employee->id] ?? [], $workDate)) {
                    continue;
                }

                if ($this->shouldBeAbsent($employee->id, $workDate, $isAkbar)) {
                    continue;
                }

                $attendanceRows[] = $this->buildAttendanceRow(
                    userId: (int) $employee->id,
                    officeLocationId: $officeLocationId,
                    workDate: $workDate,
                    isExemplary: $isAkbar,
                );
            }
        }

        if ($attendanceRows !== []) {
            DB::table('attendances')->insert($attendanceRows);
        }

        $seededAttendances = Attendance::query()
            ->select([
                'id',
                'user_id',
                'work_date',
                'check_in_at',
                'check_out_at',
                'check_in_latitude',
                'check_in_longitude',
                'check_in_accuracy_meter',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_accuracy_meter',
                'is_suspicious',
            ])
            ->whereBetween('work_date', [self::DATE_START, self::DATE_END])
            ->orderBy('id')
            ->get();

        $logRows = [];
        foreach ($seededAttendances as $attendance) {
            $checkInAt = CarbonImmutable::parse($attendance->check_in_at, self::TIMEZONE);
            $logRows[] = $this->makeLogRow(
                attendanceId: (int) $attendance->id,
                userId: (int) $attendance->user_id,
                actionType: AttendanceLogActionType::CHECK_IN_ATTEMPT->value,
                actionStatus: AttendanceLogActionStatus::SUCCESS->value,
                occurredAt: $checkInAt->subSeconds(20),
                latitude: $attendance->check_in_latitude,
                longitude: $attendance->check_in_longitude,
                accuracyMeter: $attendance->check_in_accuracy_meter,
                message: 'Percobaan check-in berhasil diproses.',
            );
            $logRows[] = $this->makeLogRow(
                attendanceId: (int) $attendance->id,
                userId: (int) $attendance->user_id,
                actionType: AttendanceLogActionType::CHECK_IN_SUCCESS->value,
                actionStatus: AttendanceLogActionStatus::SUCCESS->value,
                occurredAt: $checkInAt,
                latitude: $attendance->check_in_latitude,
                longitude: $attendance->check_in_longitude,
                accuracyMeter: $attendance->check_in_accuracy_meter,
                message: 'Check-in tercatat.',
            );

            if ($attendance->check_out_at !== null) {
                $checkOutAt = CarbonImmutable::parse($attendance->check_out_at, self::TIMEZONE);
                $logRows[] = $this->makeLogRow(
                    attendanceId: (int) $attendance->id,
                    userId: (int) $attendance->user_id,
                    actionType: AttendanceLogActionType::CHECK_OUT_ATTEMPT->value,
                    actionStatus: AttendanceLogActionStatus::SUCCESS->value,
                    occurredAt: $checkOutAt->subSeconds(20),
                    latitude: $attendance->check_out_latitude,
                    longitude: $attendance->check_out_longitude,
                    accuracyMeter: $attendance->check_out_accuracy_meter,
                    message: 'Percobaan check-out berhasil diproses.',
                );
                $logRows[] = $this->makeLogRow(
                    attendanceId: (int) $attendance->id,
                    userId: (int) $attendance->user_id,
                    actionType: AttendanceLogActionType::CHECK_OUT_SUCCESS->value,
                    actionStatus: AttendanceLogActionStatus::SUCCESS->value,
                    occurredAt: $checkOutAt,
                    latitude: $attendance->check_out_latitude,
                    longitude: $attendance->check_out_longitude,
                    accuracyMeter: $attendance->check_out_accuracy_meter,
                    message: 'Check-out tercatat.',
                );
            }

            if ((bool) $attendance->is_suspicious) {
                $suspiciousAt = $attendance->check_out_at !== null
                    ? CarbonImmutable::parse($attendance->check_out_at, self::TIMEZONE)->addSeconds(15)
                    : $checkInAt->addSeconds(15);

                $logRows[] = $this->makeLogRow(
                    attendanceId: (int) $attendance->id,
                    userId: (int) $attendance->user_id,
                    actionType: AttendanceLogActionType::SUSPICIOUS_ACTIVITY->value,
                    actionStatus: AttendanceLogActionStatus::SUSPICIOUS->value,
                    occurredAt: $suspiciousAt,
                    latitude: $attendance->check_in_latitude,
                    longitude: $attendance->check_in_longitude,
                    accuracyMeter: $attendance->check_in_accuracy_meter,
                    message: 'Aktivitas ditandai mencurigakan oleh sistem.',
                );
            }
        }

        if ($logRows !== []) {
            DB::table('attendance_logs')->insert($logRows);
        }

        $todayCount = Attendance::query()->whereDate('work_date', self::DEMO_TODAY_EMPTY)->count();
        $this->command->info(sprintf(
            'Attendance showcase data seeded: %d attendance rows, %d logs. work_date %s = %d (harus kosong).',
            count($attendanceRows),
            count($logRows),
            self::DEMO_TODAY_EMPTY,
            $todayCount
        ));
    }

    private function buildApprovedLeaveRanges(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $leaves = Leave::query()
            ->select(['employee_id', 'date_start', 'date_end'])
            ->where('status_1', 'approved')
            ->whereDate('date_start', '<=', $end->toDateString())
            ->whereDate('date_end', '>=', $start->toDateString())
            ->get();

        $ranges = [];
        foreach ($leaves as $leave) {
            $ranges[(int) $leave->employee_id][] = [
                'start' => CarbonImmutable::parse($leave->date_start, self::TIMEZONE)->startOfDay(),
                'end' => CarbonImmutable::parse($leave->date_end, self::TIMEZONE)->startOfDay(),
            ];
        }

        return $ranges;
    }

    private function holidayDateSet(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = Holiday::query()
            ->select(['start_from', 'end_at'])
            ->whereDate('start_from', '<=', $end->toDateString())
            ->where(function ($query) use ($start) {
                $query->whereNull('end_at')
                    ->orWhereDate('end_at', '>=', $start->toDateString());
            })
            ->get();

        $set = [];
        foreach ($rows as $row) {
            $rangeStart = CarbonImmutable::parse($row->start_from, self::TIMEZONE)->startOfDay();
            $rangeEnd = $row->end_at !== null
                ? CarbonImmutable::parse($row->end_at, self::TIMEZONE)->startOfDay()
                : $rangeStart;

            if ($rangeEnd->lt($rangeStart)) {
                $rangeEnd = $rangeStart;
            }

            for ($cursor = $rangeStart; $cursor->lte($rangeEnd); $cursor = $cursor->addDay()) {
                if ($cursor->betweenIncluded($start, $end)) {
                    $set[$cursor->toDateString()] = true;
                }
            }
        }

        return $set;
    }

    private function isOnApprovedLeave(array $ranges, CarbonImmutable $date): bool
    {
        foreach ($ranges as $range) {
            if ($date->betweenIncluded($range['start'], $range['end'])) {
                return true;
            }
        }

        return false;
    }

    private function shouldBeAbsent(int $userId, CarbonImmutable $workDate, bool $isExemplary): bool
    {
        $absentChance = $isExemplary ? 1 : 8;

        return $this->seededBool("att-absent-{$userId}-{$workDate->toDateString()}", $absentChance);
    }

    private function buildAttendanceRow(int $userId, int $officeLocationId, CarbonImmutable $workDate, bool $isExemplary): array
    {
        $workStart = $workDate->setTime(9, 0);
        $workEnd = $workDate->setTime(17, 0);
        $keyBase = "att-{$userId}-{$workDate->toDateString()}";

        $isLate = $this->seededBool($keyBase . '-late', $isExemplary ? 2 : 17);
        if ($isLate) {
            $checkInAt = $workStart->addMinutes($this->seededInt($keyBase . '-late-minutes', 16, 46));
        } else {
            $checkInAt = $workStart->subMinutes($this->seededInt($keyBase . '-early-minutes', 0, 12));
        }

        $isIncomplete = $this->seededBool($keyBase . '-incomplete', $isExemplary ? 1 : 5);
        $mode = 'normal';
        if (! $isIncomplete) {
            if ($this->seededBool($keyBase . '-overtime', $isExemplary ? 18 : 13)) {
                $mode = 'overtime';
            } elseif ($this->seededBool($keyBase . '-early-leave', $isExemplary ? 1 : 9)) {
                $mode = 'early_leave';
            }
        }

        $checkOutAt = null;
        $checkOutStatus = AttendanceCheckOutStatus::NONE->value;
        $recordStatus = AttendanceRecordStatus::INCOMPLETE->value;
        $earlyLeaveMinutes = 0;
        $overtimeMinutes = 0;

        if (! $isIncomplete) {
            if ($mode === 'overtime') {
                $checkOutAt = $workEnd->addMinutes($this->seededInt($keyBase . '-ot-min', 20, 150));
                $checkOutStatus = AttendanceCheckOutStatus::OVERTIME->value;
                $overtimeMinutes = (int) $workEnd->diffInMinutes($checkOutAt);
            } elseif ($mode === 'early_leave') {
                $checkOutAt = $workEnd->subMinutes($this->seededInt($keyBase . '-el-min', 10, 70));
                $checkOutStatus = AttendanceCheckOutStatus::EARLY_LEAVE->value;
                $earlyLeaveMinutes = (int) $checkOutAt->diffInMinutes($workEnd);
            } else {
                $checkOutAt = $workEnd;
                $checkOutStatus = AttendanceCheckOutStatus::NORMAL->value;
            }
            $recordStatus = AttendanceRecordStatus::COMPLETE->value;
        }

        $lateMinutes = 0;
        if ($isLate) {
            $lateMinutes = (int) $workStart->diffInMinutes($checkInAt);
        }

        $isSuspicious = $this->seededBool($keyBase . '-suspicious', $isExemplary ? 0 : 2);
        $checkInLat = $this->jitterCoordinate(-6.2614920, $keyBase . '-lat-in', 2200);
        $checkInLng = $this->jitterCoordinate(106.8106000, $keyBase . '-lng-in', 2200);
        $checkInAcc = $this->seededInt($keyBase . '-acc-in', 8, 35);

        $checkOutLat = null;
        $checkOutLng = null;
        $checkOutAcc = null;
        if ($checkOutAt !== null) {
            $checkOutLat = $this->jitterCoordinate(-6.2614920, $keyBase . '-lat-out', 2400);
            $checkOutLng = $this->jitterCoordinate(106.8106000, $keyBase . '-lng-out', 2400);
            $checkOutAcc = $this->seededInt($keyBase . '-acc-out', 7, 38);
        }

        $note = match (true) {
            $recordStatus === AttendanceRecordStatus::INCOMPLETE->value => 'Belum melakukan check-out sampai akhir jam kerja.',
            $checkOutStatus === AttendanceCheckOutStatus::OVERTIME->value => 'Menyelesaikan pekerjaan tambahan setelah jam kerja.',
            $checkOutStatus === AttendanceCheckOutStatus::EARLY_LEAVE->value => 'Pulang lebih awal dengan catatan operasional.',
            default => 'Kehadiran normal sesuai jadwal kerja.',
        };

        return [
            'user_id' => $userId,
            'office_location_id' => $officeLocationId,
            'attendance_qr_token_id' => null,
            'overtime_id' => null,
            'work_date' => $workDate->toDateString(),
            'check_in_at' => $checkInAt->format('Y-m-d H:i:s'),
            'check_in_latitude' => $checkInLat,
            'check_in_longitude' => $checkInLng,
            'check_in_accuracy_meter' => $checkInAcc,
            'check_in_recorded_at' => $checkInAt->addSeconds($this->seededInt($keyBase . '-in-rec', 6, 25))->format('Y-m-d H:i:s'),
            'check_in_status' => $isLate ? AttendanceCheckInStatus::LATE->value : AttendanceCheckInStatus::ON_TIME->value,
            'check_out_at' => $checkOutAt?->format('Y-m-d H:i:s'),
            'check_out_latitude' => $checkOutLat,
            'check_out_longitude' => $checkOutLng,
            'check_out_accuracy_meter' => $checkOutAcc,
            'check_out_recorded_at' => $checkOutAt?->addSeconds($this->seededInt($keyBase . '-out-rec', 5, 20))->format('Y-m-d H:i:s'),
            'check_out_status' => $checkOutStatus,
            'record_status' => $recordStatus,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'is_suspicious' => $isSuspicious,
            'suspicious_reason' => $isSuspicious ? 'Akurasi GPS tidak stabil saat pengambilan lokasi.' : null,
            'notes' => $note,
            'created_at' => $checkInAt->format('Y-m-d H:i:s'),
            'updated_at' => ($checkOutAt ?? $checkInAt)->addMinutes($this->seededInt($keyBase . '-upd', 3, 35))->format('Y-m-d H:i:s'),
        ];
    }

    private function makeLogRow(
        int $attendanceId,
        int $userId,
        string $actionType,
        string $actionStatus,
        CarbonImmutable $occurredAt,
        ?float $latitude,
        ?float $longitude,
        ?float $accuracyMeter,
        string $message,
    ): array {
        return [
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'action_type' => $actionType,
            'action_status' => $actionStatus,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meter' => $accuracyMeter,
            'ip_address' => '192.168.100.' . $this->seededInt("log-ip-{$attendanceId}-{$actionType}", 2, 230),
            'device_info' => 'Android Employee App',
            'message' => $message,
            'context' => json_encode([
                'source' => 'showcase-seeder',
                'timezone' => self::TIMEZONE,
            ], JSON_UNESCAPED_SLASHES),
            'occurred_at' => $occurredAt->format('Y-m-d H:i:s'),
            'created_at' => $occurredAt->format('Y-m-d H:i:s'),
            'updated_at' => $occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    private function jitterCoordinate(float $base, string $key, int $maxOffset): float
    {
        $offset = $this->seededInt($key . '-offset', -$maxOffset, $maxOffset);

        return round($base + ($offset / 10000000), 7);
    }

    private function seededInt(string $key, int $min, int $max): int
    {
        $hash = sprintf('%u', crc32('flowhr-showcase|' . $key));

        return $min + ((int) $hash % (($max - $min) + 1));
    }

    private function seededBool(string $key, int $percentageTrue): bool
    {
        return $this->seededInt($key, 1, 100) <= $percentageTrue;
    }
}
