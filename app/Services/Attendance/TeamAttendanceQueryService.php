<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Data\Attendance\AttendanceHistoryFilterData;
use App\Enums\Roles;
use App\Models\Attendance;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeamAttendanceQueryService
{
    public function subordinateEmployeesQuery(int $leaderId, ?int $officeLocationId = null): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'office_location_id', 'division_id', 'is_active'])
            ->with([
                'officeLocation:id,name',
                'division:id,name,leader_id',
            ])
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', Roles::Employee->value))
            ->whereHas('division', fn ($query) => $query->where('leader_id', $leaderId))
            ->when($officeLocationId !== null, fn ($query) => $query->where('office_location_id', $officeLocationId))
            ->orderBy('name');
    }

    public function subordinateEmployees(int $leaderId, ?int $officeLocationId = null): Collection
    {
        return $this->subordinateEmployeesQuery($leaderId, $officeLocationId)->get();
    }

    public function officeLocationsForLeader(int $leaderId): Collection
    {
        return OfficeLocation::query()
            ->select(['id', 'code', 'name', 'address', 'radius_meter', 'timezone', 'is_active'])
            ->whereHas('users', function ($query) use ($leaderId) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Employee->value))
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('leader_id', $leaderId));
            })
            ->orderBy('name')
            ->get();
    }

    public function getLeaderHistory(int $leaderId, AttendanceHistoryFilterData $filter): LengthAwarePaginator
    {
        $query = Attendance::query()
            ->select([
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
            ])
            ->with([
                'user:id,name,email,office_location_id,division_id',
                'officeLocation:id,name',
            ])
            ->whereHas('user', function ($userQuery) use ($leaderId) {
                $userQuery->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Employee->value))
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('leader_id', $leaderId));
            });

        if ($filter->startDate !== null) {
            $query->whereDate('work_date', '>=', $filter->startDate->toDateString());
        }

        if ($filter->endDate !== null) {
            $query->whereDate('work_date', '<=', $filter->endDate->toDateString());
        }

        if ($filter->userId !== null) {
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

        $sortDirection = $filter->sortDirection === 'asc' ? 'asc' : 'desc';
        $sortColumn = in_array($filter->sortBy, [
            'work_date',
            'check_in_at',
            'check_out_at',
            'late_minutes',
            'early_leave_minutes',
            'overtime_minutes',
            'created_at',
            'updated_at',
            'id',
        ], true) ? $filter->sortBy : 'work_date';

        $query->orderBy($sortColumn, $sortDirection);

        if ($sortColumn !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query->paginate($filter->perPage)->withQueryString();
    }

    public function getLeaderAttendanceDetail(int $leaderId, int $attendanceId): Attendance
    {
        return Attendance::query()
            ->with([
                'user:id,name,email,office_location_id,division_id',
                'user.division:id,name,leader_id',
                'officeLocation:id,name,address,latitude,longitude,radius_meter',
                'officeLocation.activeAttendanceSetting:id,office_location_id,work_start_time,work_end_time,late_tolerance_minutes,is_active',
                'attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active',
                'logs' => function ($query) {
                    $query->select([
                        'id',
                        'attendance_id',
                        'user_id',
                        'action_type',
                        'action_status',
                        'latitude',
                        'longitude',
                        'accuracy_meter',
                        'ip_address',
                        'device_info',
                        'message',
                        'occurred_at',
                        'created_at',
                    ])->orderBy('occurred_at', 'asc')
                        ->orderBy('id', 'asc');
                },
            ])
            ->where('id', $attendanceId)
            ->whereHas('user', function ($query) use ($leaderId) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Employee->value))
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('leader_id', $leaderId));
            })
            ->firstOrFail();
    }
}
