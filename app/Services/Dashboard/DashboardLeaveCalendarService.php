<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Services\HolidayDateService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

class DashboardLeaveCalendarService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private HolidayDateService $holidayDateService,
    ) {}

    /**
     * @return array{
     *     approved_by_date: array<string, array<int, array{employee:string,email:string,url_profile:?string}>>,
     *     holiday_dates: string[],
     *     holidays_by_date: array<string, array<int, array{name:?string,start_from:string,end_at:string}>>
     * }
     */
    public function build(Builder $leaveQuery, ?int $year = null): array
    {
        $resolvedYear = $year ?? (int) now(config('app.timezone', self::DEFAULT_TIMEZONE))->year;

        $rangeStart = Carbon::createFromDate($resolvedYear, 1, 1, config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();
        $rangeEnd = Carbon::createFromDate($resolvedYear, 12, 31, config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();

        $approvedByDate = [];
        $approvedLeaves = (clone $leaveQuery)
            ->where('status_1', 'approved')
            ->where(function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query->whereDate('date_start', '<=', $rangeEnd->toDateString())
                    ->whereDate('date_end', '>=', $rangeStart->toDateString());
            })
            ->with(['employee:id,name,email,url_profile'])
            ->get(['id', 'employee_id', 'date_start', 'date_end']);

        foreach ($approvedLeaves as $leave) {
            if ($leave->employee === null || $leave->date_start === null || $leave->date_end === null) {
                continue;
            }

            $leaveStart = Carbon::parse($leave->date_start, config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();
            $leaveEnd = Carbon::parse($leave->date_end, config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();

            if ($leaveEnd->lt($leaveStart)) {
                $leaveEnd = $leaveStart->copy();
            }

            $periodStart = $leaveStart->lt($rangeStart) ? $rangeStart->copy() : $leaveStart;
            $periodEnd = $leaveEnd->gt($rangeEnd) ? $rangeEnd->copy() : $leaveEnd;

            if ($periodEnd->lt($periodStart)) {
                continue;
            }

            foreach (CarbonPeriod::create($periodStart, $periodEnd) as $date) {
                $dateKey = $date->toDateString();
                $approvedByDate[$dateKey][] = [
                    'employee' => (string) $leave->employee->name,
                    'email' => (string) $leave->employee->email,
                    'url_profile' => $leave->employee->url_profile,
                ];
            }
        }

        ksort($approvedByDate);

        $holidaysByDate = [];
        $holidayItems = $this->holidayDateService->getHolidayItems($rangeStart, $rangeEnd);

        foreach ($holidayItems as $holidayItem) {
            $start = Carbon::parse($holidayItem['start_from'], config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();
            $end = Carbon::parse($holidayItem['end_at'], config('app.timezone', self::DEFAULT_TIMEZONE))->startOfDay();

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            $holidayName = trim((string) ($holidayItem['name'] ?? ''));
            $normalizedName = $holidayName !== '' ? $holidayName : 'Holiday';

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateKey = $date->toDateString();
                $holidaysByDate[$dateKey][] = [
                    'name' => $normalizedName,
                    'start_from' => (string) $holidayItem['start_from'],
                    'end_at' => (string) $holidayItem['end_at'],
                ];
            }
        }

        ksort($holidaysByDate);

        return [
            'approved_by_date' => $approvedByDate,
            'holiday_dates' => array_keys($holidaysByDate),
            'holidays_by_date' => $holidaysByDate,
        ];
    }
}
