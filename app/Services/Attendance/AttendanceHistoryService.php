<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AttendanceHistoryService
{
    /**
     * Whitelist kolom sort yang boleh dipakai dari request.
     * Jangan percaya sort_by mentah dari client.
     */
    private const ALLOWED_SORT_COLUMNS = [
        'work_date',
        'check_in_at',
        'check_out_at',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'created_at',
        'updated_at',
        'id',
    ];

    /**
     * Riwayat absensi untuk employee tertentu.
     * User scope dikunci dari parameter method, bukan dari filter request.
     */
    public function getEmployeeHistory(
        int $userId,
        AttendanceHistoryFilterData $filter,
    ): LengthAwarePaginator {
        $query = $this->baseEmployeeHistoryQuery($userId);

        $this->applyHistoryFilters($query, $filter, allowUserFilter: false);
        $this->applySorting($query, $filter);

        return $query->paginate(
            perPage: $filter->perPage,
            pageName: 'page'
        )->withQueryString();
    }

    /**
     * Riwayat absensi lintas employee untuk admin.
     * Boleh pakai user_id dari filter karena ini memang admin scope.
     */
    public function getAllEmployeeHistory(
        AttendanceHistoryFilterData $filter,
    ): LengthAwarePaginator {
        $query = $this->baseAdminHistoryQuery();

        $this->applyHistoryFilters($query, $filter, allowUserFilter: true);
        $this->applySorting($query, $filter);

        return $query->paginate(
            perPage: $filter->perPage,
            pageName: 'page'
        )->withQueryString();
    }

    private function baseEmployeeHistoryQuery(int $userId): Builder
    {
        return Attendance::query()
            ->select($this->historySelectColumns())
            ->with([
                'officeLocation:id,name',
                'latestCorrection' => function ($query): void {
                    $query->select([
                        'attendance_corrections.id',
                        'attendance_corrections.attendance_id',
                        'attendance_corrections.status',
                        'attendance_corrections.updated_at',
                    ]);
                },
            ])
            ->where('user_id', $userId);
    }

    private function baseAdminHistoryQuery(): Builder
    {
        return Attendance::query()
            ->select($this->historySelectColumns())
            ->with([
                'user:id,name,email,office_location_id',
                'officeLocation:id,name',
                'latestCorrection' => function ($query): void {
                    $query->select([
                        'attendance_corrections.id',
                        'attendance_corrections.attendance_id',
                        'attendance_corrections.status',
                        'attendance_corrections.updated_at',
                    ]);
                },
            ]);
    }

    /**
     * Kolom list dibuat secukupnya.
     * Jangan ambil semua kolom kalau list page tidak butuh.
     */
    private function historySelectColumns(): array
    {
        return [
            'id',
            'user_id',
            'office_location_id',
            'attendance_qr_token_id',
            'work_date',
            'check_in_at',
            'check_in_recorded_at',
            'check_in_status',
            'check_out_at',
            'check_out_recorded_at',
            'check_out_status',
            'record_status',
            'late_minutes',
            'early_leave_minutes',
            'overtime_minutes',
            'is_suspicious',
            'suspicious_reason',
            'created_at',
            'updated_at',
        ];
    }

    private function applyHistoryFilters(
        Builder $query,
        AttendanceHistoryFilterData $filter,
        bool $allowUserFilter = false,
    ): void {
        if ($filter->startDate !== null) {
            $query->whereDate('work_date', '>=', $filter->startDate->toDateString());
        }

        if ($filter->endDate !== null) {
            $query->whereDate('work_date', '<=', $filter->endDate->toDateString());
        }

        if ($allowUserFilter && $filter->userId !== null) {
            $query->where('user_id', $filter->userId);
        }

        if ($filter->officeLocationId !== null) {
            $query->where('office_location_id', $filter->officeLocationId);
        }

        if ($filter->recordStatus !== null) {
            $query->where('record_status', $filter->recordStatus);
        }

        if ($filter->checkInStatus !== null) {
            $query->where('check_in_status', $filter->checkInStatus);
        }

        if ($filter->checkOutStatus !== null) {
            $query->where('check_out_status', $filter->checkOutStatus);
        }

        if ($filter->isSuspicious !== null) {
            $query->where('is_suspicious', $filter->isSuspicious);
        }
    }

    private function applySorting(
        Builder $query,
        AttendanceHistoryFilterData $filter,
    ): void {
        $sortColumn = $this->resolveSortColumn($filter->sortBy);
        $sortDirection = $filter->sortDirection === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        /**
         * Secondary sort biar stabil saat value kolom utama sama.
         * Contoh: banyak row dengan work_date yang sama.
         */
        if ($sortColumn !== 'id') {
            $query->orderBy('id', 'desc');
        }
    }

    private function resolveSortColumn(string $sortBy): string
    {
        return in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true)
            ? $sortBy
            : 'work_date';
    }
}
