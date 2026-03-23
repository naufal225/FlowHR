<?php

namespace App\Services;

use App\Models\ApprovalLink;
use App\Models\OfficialTravel;
use App\Models\User;
use App\Enums\Roles;
use App\Events\OfficialTravelLevelAdvanced;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OfficialTravelApprovalService
{
    public function handleApproval(OfficialTravel $travel, string $status, ?string $note, string $level): OfficialTravel
    {
        // === APPROVER (Level 1) ===
        if ($level === 'status_1') {
            if ($travel->status_1 !== 'pending') {
                throw new \Exception('Status 1 sudah final dan tidak dapat diubah.');
            }

            if ($status === 'rejected') {
                $travel->update([
                    'status_1' => 'rejected',
                    'status_2' => 'rejected', // cascade reject
                    'rejected_date' => Carbon::now(),
                    'note_1' => $note,
                    'approver_1_id' => Auth::id(),
                ]);
                return $travel;
            }

            if ($status === 'approved') {
                $travel->update([
                    'status_1' => 'approved',
                    'note_1' => $note,
                    'approver_1_id' => Auth::id(),
                ]);

                $emp = $travel->employee;
                $isLeader = $emp && \App\Models\Division::where('leader_id', $emp->id)->exists();
                $isApprover = $emp && $emp->roles()->where('name', \App\Enums\Roles::Approver->value)->exists();
                // if ($isLeader || $isApprover) {
                event(new OfficialTravelLevelAdvanced($travel->fresh(), $emp->division_id ?? 0, 'manager'));
                // }

                // Buat approval link untuk Manager
                $managerRole = Role::where('name', 'manager')->first();

                $manager = User::whereHas('roles', function ($query) use ($managerRole) {
                    $query->where('roles.id', $managerRole->id);
                })->first();

                if ($manager) {
                    $token = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($travel),
                        'model_id' => $travel->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2,
                        'scope' => 'both',
                        'token' => hash('sha256', $token),
                        'expires_at' => now()->addDays(3),
                    ]);

                    $link = route('public.approval.show', $token);
                    Mail::to($manager->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: $travel->employee->name,
                            namaApprover: $manager->name,
                            linkTanggapan: $link,
                            emailPengaju: $travel->employee->email
                        )
                    );
                }

                return $travel;
            }
        }

        // === MANAGER (Level 2) ===
        if ($level === 'status_2') {
            if ($travel->status_1 !== 'approved') {
                dd($travel);
                throw new \Exception('Manager hanya dapat memproses jika Approver sudah menyetujui.');
            }

            if ($travel->status_2 !== 'pending') {
                throw new \Exception('Status 2 sudah final dan tidak dapat diubah.');
            }

            $travel->update([
                'status_2' => $status,
                'note_2' => $note,
                'approver_2_id' => Auth::id(),
            ]);

            if ($status === 'approved') {
                $travel->update([
                    'approved_date' => Carbon::now(),
                ]);
            } else {
                $travel->update([
                    'rejected_date' => Carbon::now(),
                ]);
            }
        }

        return $travel;
    }
}
