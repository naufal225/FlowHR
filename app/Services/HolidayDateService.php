<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;

class HolidayDateService
{
    private const DEFAULT_TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array<int, array{id:int,name:?string,start_from:string,end_at:string}>
     */
    public function getHolidayItems(?Carbon $from = null, ?Carbon $to = null): array
    {
        [$fromDate, $toDate] = $this->normalizeRange($from, $to);

        return $this->queryOverlappingRange($fromDate, $toDate)
            ->orderBy('start_from')
            ->orderBy('id')
            ->get(['id', 'name', 'start_from', 'end_at'])
            ->map(function (Holiday $holiday) use ($fromDate, $toDate): ?array {
                $start = $holiday->start_from?->copy();

                if ($start === null) {
                    return null;
                }

                $end = $holiday->end_at?->copy() ?? $start->copy();

                if ($end->lt($start)) {
                    $end = $start->copy();
                }

                if ($start->lt($fromDate)) {
                    $start = $fromDate->copy();
                }

                if ($end->gt($toDate)) {
                    $end = $toDate->copy();
                }

                $name = trim((string) $holiday->name);

                return [
                    'id' => (int) $holiday->id,
                    'name' => $name !== '' ? $name : null,
                    'start_from' => $start->toDateString(),
                    'end_at' => $end->toDateString(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    public function getDateStringsForYear(int $year): array
    {
        $from = Carbon::createFromDate($year, 1, 1, self::DEFAULT_TIMEZONE)->startOfDay();
        $to = Carbon::createFromDate($year, 12, 31, self::DEFAULT_TIMEZONE)->startOfDay();

        return $this->getDateStrings($from, $to);
    }

    /**
     * @return string[]
     */
    public function getDateStringsForForm(): array
    {
        $now = now(self::DEFAULT_TIMEZONE);

        return $this->getDateStrings(
            $now->copy()->startOfYear(),
            $now->copy()->addYear()->endOfYear(),
        );
    }

    /**
     * @return string[]
     */
    public function getDateStrings(?Carbon $from = null, ?Carbon $to = null): array
    {
        [$fromDate, $toDate] = $this->normalizeRange($from, $to);

        $holidayRows = $this->queryOverlappingRange($fromDate, $toDate)
            ->get(['id', 'name', 'start_from', 'end_at']);

        $dateMap = [];

        foreach ($holidayRows as $holiday) {
            $start = $holiday->start_from?->copy();

            if ($start === null) {
                continue;
            }

            $end = $holiday->end_at?->copy() ?? $start->copy();

            if ($end->lt($start)) {
                $end = $start->copy();
            }

            if ($start->lt($fromDate)) {
                $start = $fromDate->copy();
            }

            if ($end->gt($toDate)) {
                $end = $toDate->copy();
            }

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateMap[$date->toDateString()] = true;
            }
        }

        ksort($dateMap);

        return array_keys($dateMap);
    }

    public function isHolidayDate(Carbon $date): bool
    {
        return $this->findHolidayForDate($date) !== null;
    }

    public function findHolidayForDate(Carbon $date): ?Holiday
    {
        $day = $date->copy()->setTimezone(self::DEFAULT_TIMEZONE)->startOfDay();

        return $this->queryOverlappingRange($day, $day)
            ->orderBy('start_from')
            ->first();
    }

    private function queryOverlappingRange(Carbon $fromDate, Carbon $toDate): Builder
    {
        $from = $fromDate->toDateString();
        $to = $toDate->toDateString();

        return Holiday::query()
            ->where(function (Builder $query) use ($from, $to): void {
                $query
                    ->whereDate('start_from', '<=', $to)
                    ->where(function (Builder $endQuery) use ($from): void {
                        $endQuery
                            ->whereNull('end_at')
                            ->orWhereDate('end_at', '>=', $from);
                    });
            });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function normalizeRange(?Carbon $from, ?Carbon $to): array
    {
        $fromDate = $from?->copy()->setTimezone(self::DEFAULT_TIMEZONE)->startOfDay();
        $toDate = $to?->copy()->setTimezone(self::DEFAULT_TIMEZONE)->startOfDay();

        if ($fromDate === null && $toDate === null) {
            $now = now(self::DEFAULT_TIMEZONE);
            $fromDate = $now->copy()->subYear()->startOfYear();
            $toDate = $now->copy()->addYears(2)->endOfYear();
        } elseif ($fromDate === null) {
            $fromDate = $toDate->copy()->subYear()->startOfYear();
        } elseif ($toDate === null) {
            $toDate = $fromDate->copy()->addYear()->endOfYear();
        }

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        return [$fromDate, $toDate];
    }
}
