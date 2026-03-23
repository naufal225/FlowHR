<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use App\Models\Division;
use App\Enums\Roles;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeaveApprovalService
{
    /**
     * Approve a leave request.
     */
    public function approve(Leave $leave, ?string $note = null): Leave
    {
        $this->authorizeFor($leave);

        if ($leave->status_1 !== 'pending') {
            throw ValidationException::withMessages([
                'status_1' => 'Leave sudah diproses, tidak dapat diubah lagi.'
            ]);
        }

        $leave->update([
            'status_1' => 'approved',
            'approved_date' => Carbon::now(),
            'note_1' => $note ?? null,
            'approver_1_id' => Auth::id(),
        ]);

        return $leave;
    }

    /**
     * Reject a leave request.
     */
    public function reject(Leave $leave, ?string $note = null): Leave
    {
        $this->authorizeFor($leave);

        if ($leave->status_1 !== 'pending') {
            throw ValidationException::withMessages([
                'status_1' => 'Leave sudah diproses, tidak dapat diubah lagi.'
            ]);
        }

        $leave->update([
            'status_1' => 'rejected',
            'rejected_date' => Carbon::now(),
            'note_1' => $note ?? null,
            'approver_1_id' => Auth::id(),
        ]);

        return $leave;
    }

    /**
     * Authorize approver based on applicant role.
     */
    private function authorizeFor(Leave $leave): void
    {
        $employee = $leave->employee;

        $isLeaderApplicant = Division::where('leader_id', $employee->id)->exists();
        $isApproverApplicant = $employee->roles()->where('name', Roles::Approver->value)->exists();

        // If applicant is only Employee -> Division Leader approves
        if (!$isLeaderApplicant && !$isApproverApplicant) {
            $leaderId = optional($employee->division)->leader_id;
            if ($leaderId && Auth::id() === (int) $leaderId) {
                return; // Authorized as division leader
            }
            abort(403, 'Unauthorized — only Division Leader can approve this leave.');
        }

        // If applicant is Approver or Leader -> Manager approves
        if (!Auth::user()->hasActiveRole(Roles::Manager->value)) {
            abort(403, 'Unauthorized — only Manager can approve this leave.');
        }
    }
}
