<?php

namespace App\Services;

use App\Enums\Roles;
use App\Models\Leave;
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
                'status_1' => 'Leave sudah diproses, tidak dapat diubah lagi.',
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
                'status_1' => 'Leave sudah diproses, tidak dapat diubah lagi.',
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
     * Leave approval flow is single-step and manager-only.
     */
    private function authorizeFor(Leave $leave): void
    {
        $actor = Auth::user();

        if (! $actor || ! $actor->hasActiveRole(Roles::Manager->value)) {
            abort(403, 'Unauthorized - only Manager can approve this leave.');
        }

        if ((int) $leave->employee_id === (int) Auth::id()) {
            abort(403, 'Unauthorized - you cannot approve your own leave request.');
        }
    }
}
