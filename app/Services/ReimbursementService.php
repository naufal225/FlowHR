<?php

namespace App\Services;

use App\Enums\Roles;
use App\Models\Role;
use App\Models\Reimbursement;
use App\Models\ApprovalLink;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReimbursementService
{
    public function store(array $data): Reimbursement
    {
        return DB::transaction(function () use ($data) {
            $submitter = Auth::user();
            $isManager = $submitter->userHasRole('manager');
            $isTeamLeader = $submitter->userHasRole('team-leader');

            $reimbursement = new Reimbursement();
            $reimbursement->employee_id = Auth::id();
            $reimbursement->customer = $data['customer'];
            $reimbursement->reimbursement_type_id = $data['reimbursement_type_id'];
            $reimbursement->total = $data['total'];
            $reimbursement->date = $data['date'];
            $reimbursement->status_1 = $isManager || $isTeamLeader ? 'approved' : 'pending';
            $reimbursement->status_2 = $isManager ? 'approved' : 'pending';
            $reimbursement->approver_1_id = ($isManager || $isTeamLeader) ? $submitter->id : null;
            $reimbursement->approver_2_id = $isManager ? $submitter->id : null;
            $reimbursement->approved_date = $isManager ? now() : null;

            if (isset($data['invoice_path'])) {
                $path = $data['invoice_path']->store('reimbursement_invoices', 'public');
                $reimbursement->invoice_path = $path;
            }

            $reimbursement->save();

            if ($isManager) {
                return $reimbursement;
            }

            if ($isTeamLeader) {
                $manager = $this->resolveManager();
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

                    DB::afterCommit(function () use ($reimbursement, $manager, $token) {
                        $fresh = $reimbursement->fresh();
                        event(new \App\Events\ReimbursementLevelAdvanced(
                            $fresh,
                            $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                            'manager'
                        ));

                        $linkTanggapan = route('public.approval.show', $token);

                        Mail::to($manager->email)->queue(
                            new \App\Mail\SendMessage(
                                namaPengaju: Auth::user()->name,
                                namaApprover: $manager->name,
                                linkTanggapan: $linkTanggapan,
                                emailPengaju: Auth::user()->email,
                                attachmentPath: $reimbursement->invoice_path
                            )
                        );
                    });
                }

                return $reimbursement;
            }

            DB::afterCommit(function () use ($reimbursement) {
                $fresh = $reimbursement->fresh();
                event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));
            });

            if ($reimbursement->approver) {
                $token = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($reimbursement),
                    'model_id' => $reimbursement->id,
                    'approver_user_id' => $reimbursement->approver->id,
                    'level' => 1,
                    'scope' => 'both',
                    'token' => hash('sha256', $token),
                    'expires_at' => now()->addDays(3),
                ]);

                DB::afterCommit(function () use ($reimbursement, $token) {
                    $linkTanggapan = route('public.approval.show', $token);

                    Mail::to($reimbursement->approver->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $reimbursement->approver->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                            attachmentPath: $reimbursement->invoice_path
                        )
                    );
                });
            }

            return $reimbursement;
        });
    }

    public function update(Reimbursement $reimbursement, array $data): Reimbursement
    {
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            throw new \Exception('Reimbursement sudah diproses, tidak bisa diupdate.');
        }

        $submitter = Auth::user();
        $isManager = $submitter->userHasRole('manager');
        $isTeamLeader = $submitter->userHasRole('team-leader');

        $reimbursement->customer = $data['customer'];
        $reimbursement->total = $data['total'];
        $reimbursement->date = $data['date'];
        $reimbursement->reimbursement_type_id = $data['reimbursement_type_id'];
        $reimbursement->status_1 = $isManager || $isTeamLeader ? 'approved' : 'pending';
        $reimbursement->status_2 = $isManager ? 'approved' : 'pending';
        $reimbursement->approver_1_id = ($isManager || $isTeamLeader) ? $submitter->id : null;
        $reimbursement->approver_2_id = $isManager ? $submitter->id : null;
        $reimbursement->approved_date = $isManager ? now() : null;
        $reimbursement->note_1 = null;
        $reimbursement->note_2 = null;

        // handle file
        if (isset($data['invoice_path'])) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
            }
            $path = $data['invoice_path']->store('reimbursement_invoices', 'public');
            $reimbursement->invoice_path = $path;
        } elseif (!empty($data['remove_invoice_path']) && $data['remove_invoice_path']) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
                $reimbursement->invoice_path = null;
            }
        }

        $reimbursement->save();

        if ($isManager) {
            return $reimbursement;
        }

        if ($isTeamLeader) {
            $manager = $this->resolveManager();
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

                DB::afterCommit(function () use ($reimbursement, $manager, $token) {
                    $fresh = $reimbursement->fresh();
                    event(new \App\Events\ReimbursementLevelAdvanced(
                        $fresh,
                        $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                        'manager'
                    ));

                    $linkTanggapan = route('public.approval.show', $token);

                    Mail::to($manager->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $manager->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                            attachmentPath: $reimbursement->invoice_path
                        )
                    );
                });
            }

            return $reimbursement;
        }

        DB::afterCommit(function () use ($reimbursement) {
            $fresh = $reimbursement->fresh();
            event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));
        });

        if ($reimbursement->approver) {
            $token = Str::random(48);
            ApprovalLink::create([
                'model_type' => get_class($reimbursement),
                'model_id' => $reimbursement->id,
                'approver_user_id' => $reimbursement->approver->id,
                'level' => 1,
                'scope' => 'both',
                'token' => hash('sha256', $token),
                'expires_at' => now()->addDays(3),
            ]);

            DB::afterCommit(function () use ($reimbursement, $token) {
                $linkTanggapan = route('public.approval.show', $token);

                Mail::to($reimbursement->approver->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: Auth::user()->name,
                        namaApprover: $reimbursement->approver->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: Auth::user()->email,
                        attachmentPath: $reimbursement->invoice_path
                    )
                );
            });
        }

        return $reimbursement;
    }

    private function resolveManager(): ?User
    {
        $managerRole = Role::query()->where('name', Roles::Manager->value)->first();
        if (!$managerRole) {
            return null;
        }

        return User::query()
            ->whereHas('roles', function ($query) use ($managerRole) {
                $query->where('roles.id', $managerRole->id);
            })
            ->first();
    }
}
