<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\Roles;
use App\Models\AttendanceCorrection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceCorrectionQueryService
{
    public function getEmployeeCorrections(int $userId, ?string $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return AttendanceCorrection::query()
            ->with([
                'attendance:id,user_id,office_location_id,work_date,check_in_at,check_out_at',
                'attendance.officeLocation:id,name',
                'reviewer:id,name',
                'appliedBy:id,name',
            ])
            ->where('user_id', $userId)
            ->when($status !== null && $status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getEmployeeCorrectionDetail(int $userId, int $correctionId): AttendanceCorrection
    {
        return AttendanceCorrection::query()
            ->with([
                'attendance.user.division:id,name',
                'attendance.officeLocation:id,name,address,latitude,longitude,radius_meter',
                'attendance.attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active',
                'attendance.logs' => fn ($query) => $query->latest('occurred_at')->latest('id'),
                'reviewer:id,name',
                'appliedBy:id,name',
            ])
            ->where('user_id', $userId)
            ->findOrFail($correctionId);
    }

    public function getLeaderCorrections(int $leaderId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = (string) ($filters['status'] ?? 'pending');

        return AttendanceCorrection::query()
            ->with([
                'attendance:id,user_id,office_location_id,work_date,check_in_at,check_out_at',
                'attendance.user:id,name,email,division_id',
                'attendance.officeLocation:id,name',
                'reviewer:id,name',
            ])
            ->whereHas('attendance.user', function ($query) use ($leaderId) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Employee->value))
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('leader_id', $leaderId));
            })
            ->when(!empty($filters['office_location_id']), function ($query) use ($filters) {
                $query->whereHas('attendance', fn ($attendanceQuery) => $attendanceQuery->where('office_location_id', (int) $filters['office_location_id']));
            })
            ->when(!empty($filters['user_id']), fn ($query) => $query->where('user_id', (int) $filters['user_id']))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getLeaderCorrectionDetail(int $leaderId, int $correctionId): AttendanceCorrection
    {
        return AttendanceCorrection::query()
            ->with([
                'attendance.user.division:id,name,leader_id',
                'attendance.user.roles:id,name',
                'attendance.officeLocation.activeAttendanceSetting:id,office_location_id,work_start_time,work_end_time,late_tolerance_minutes,is_active',
                'attendance.attendanceQrToken:id,office_location_id,generated_at,expired_at,is_active,token',
                'attendance.logs' => fn ($query) => $query->latest('occurred_at')->latest('id'),
                'reviewer:id,name',
                'appliedBy:id,name',
            ])
            ->whereHas('attendance.user', function ($query) use ($leaderId) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Employee->value))
                    ->whereHas('division', fn ($divisionQuery) => $divisionQuery->where('leader_id', $leaderId));
            })
            ->findOrFail($correctionId);
    }
}
