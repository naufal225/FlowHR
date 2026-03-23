<?php

namespace App\Services;

use App\Events\ReimbursementLevelAdvanced;
use App\Models\Reimbursement;
use App\Models\User;
use App\Models\ApprovalLink;
use App\Enums\Roles;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReimbursementApprovalService
{
    public function handleApproval(Reimbursement $reimbursement, string $status, ?string $note, string $level): void
    {
        // === LEVEL 1 (APPROVER) ===
        if ($level === 'status_1') {
            if ($reimbursement->status_1 !== 'pending') {
                throw new \Exception('Status 1 sudah final dan tidak dapat diubah.');
            }

            if ($status === 'rejected') {
                $reimbursement->update([
                    'status_1' => 'rejected',
                    'status_2' => 'rejected', // cascade reject
                    'rejected_date' => Carbon::now(),
                    'note_1' => $note,
                    'approver_1_id' => Auth::id(),
                ]);
                return;
            }

            if ($status === 'approved') {
                $reimbursement->update([
                    'status_1' => 'approved',
                    'note_1' => $note,
                    'approver_1_id' => Auth::id(),
                ]);

                $emp = $reimbursement->employee;
                $isLeader = $emp && \App\Models\Division::where('leader_id', $emp->id)->exists();
                $isApprover = $emp && $emp->roles()->where('name', \App\Enums\Roles::Approver->value)->exists();
                // if ($isLeader || $isApprover) {
                event(new ReimbursementLevelAdvanced($reimbursement->fresh(), $emp->division_id ?? 0, 'manager'));
                // }

                // kirim approval link ke Manager
                $managerRole = Role::where('name', 'manager')->first();

                $manager = User::whereHas('roles', function ($query) use ($managerRole) {
                    $query->where('roles.id', $managerRole->id);
                })->first();

                if ($manager) {
                    $token = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($reimbursement),
                        'model_id' => $reimbursement->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2,
                        'scope' => 'both',
                        'token' => hash('sha256', $token),
                        'expires_at' => now()->addDays(3),
                    ]);

                    $link = route('public.approval.show', $token);
                    Mail::to($manager->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: $reimbursement->employee->name,
                            namaApprover: $manager->name,
                            linkTanggapan: $link,
                            emailPengaju: $reimbursement->employee->email
                        )
                    );
                }
            }
        }

        // === LEVEL 2 (MANAGER) ===
        if ($level === 'status_2') {
            if ($reimbursement->status_1 !== 'approved') {
                throw new \Exception('Status 2 hanya dapat diubah setelah status 1 disetujui.');
            }

            if ($reimbursement->status_2 !== 'pending') {
                throw new \Exception('Status 2 sudah final dan tidak dapat diubah.');
            }

            $reimbursement->update([
                'status_2' => $status,
                'note_2' => $note,
                'approver_2_id' => Auth::id(),
            ]);

            if ($status === 'approved') {
                $reimbursement->update([
                    'approved_date' => Carbon::now(),
                ]);
            } else {
                $reimbursement->update([
                    'rejected_date' => Carbon::now(),
                ]);
            }
        }
    }
}
