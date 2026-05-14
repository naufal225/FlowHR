<?php

namespace App\Support;

use App\Models\User;

class UserPermissions
{
    public function __construct(private User $user) {}

    public function canViewAllRequests(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin', 'manager', 'approver', 'finance']);
    }

    public function canViewDivisionRequests(): bool
    {
        return $this->user->hasRole('approver');
    }

    /** Approve stage 1 (Leave: manager; Overtime/Reimbursement/Travel: approver) */
    public function canApproveLeave(): bool
    {
        return $this->user->hasRole('manager');
    }

    /** Approve stage 1 for dual-approval requests (Overtime, Reimbursement, OfficialTravel) */
    public function canApproveStage1(): bool
    {
        return $this->user->hasRole('approver');
    }

    /** Approve stage 2 for dual-approval requests */
    public function canApproveStage2(): bool
    {
        return $this->user->hasRole('manager');
    }

    public function canExport(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin', 'manager', 'approver']);
    }

    public function canBulkExport(): bool
    {
        return $this->user->hasRole('finance');
    }

    public function canMarkPayment(): bool
    {
        return $this->user->hasRole('finance');
    }

    public function canManageUsers(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canManageDivisions(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canManageSettings(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canManageLeaveBalances(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canManageHolidays(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canManageAttendanceSettings(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin']);
    }

    public function canReviewAttendanceCorrections(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin', 'approver']);
    }

    public function canViewAllAttendance(): bool
    {
        return $this->user->hasRole(['admin', 'superAdmin', 'approver']);
    }

    public function toArray(): array
    {
        return [
            'canViewAllRequests'            => $this->canViewAllRequests(),
            'canViewDivisionRequests'       => $this->canViewDivisionRequests(),
            'canApproveLeave'               => $this->canApproveLeave(),
            'canApproveStage1'              => $this->canApproveStage1(),
            'canApproveStage2'              => $this->canApproveStage2(),
            'canExport'                     => $this->canExport(),
            'canBulkExport'                 => $this->canBulkExport(),
            'canMarkPayment'                => $this->canMarkPayment(),
            'canManageUsers'                => $this->canManageUsers(),
            'canManageDivisions'            => $this->canManageDivisions(),
            'canManageSettings'             => $this->canManageSettings(),
            'canManageLeaveBalances'        => $this->canManageLeaveBalances(),
            'canManageHolidays'             => $this->canManageHolidays(),
            'canManageAttendanceSettings'   => $this->canManageAttendanceSettings(),
            'canReviewAttendanceCorrections'=> $this->canReviewAttendanceCorrections(),
            'canViewAllAttendance'          => $this->canViewAllAttendance(),
        ];
    }
}
