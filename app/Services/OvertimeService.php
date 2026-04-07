<?php

namespace App\Services;

use App\Helpers\CostSettingsHelper;
use App\Enums\Roles;
use App\Models\Overtime;
use App\Models\ApprovalLink;
use App\Models\Role;
use App\Models\User;
use App\Mail\SendMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OvertimeService
{
    public function store(array $data): Overtime
    {
        $start = Carbon::createFromFormat('Y-m-d\TH:i', $data['date_start'], 'Asia/Jakarta');
        $end = Carbon::createFromFormat('Y-m-d\TH:i', $data['date_end'], 'Asia/Jakarta');

        // Validasi jam mulai hari ini
        if ($start->isToday() && $start->lt(Carbon::today()->setTime(17, 0))) {
            throw new \Exception('Jika tanggal mulai adalah hari ini, maka waktu mulai harus setelah jam 17:00.');
        }

        $minutes = $start->diffInMinutes($end);
        $hours = $minutes / 60;

        if ($hours < 0.5) {
            throw new \Exception('Minimum overtime is 0.5 hours.');
        }

        return DB::transaction(function () use ($data, $start, $end, $minutes) {
            $submitter = Auth::user();
            $isManager = $submitter->userHasRole('manager');
            $isTeamLeader = $submitter->userHasRole('team-leader');

            $hours = floor($minutes / 60);

            $overtime = new Overtime();
            $overtime->employee_id = Auth::id();
            $overtime->customer = $data['customer'];
            $overtime->date_start = $start;
            $overtime->date_end = $end;

            // Hitung biaya overtime
            $costPerHour = (int) CostSettingsHelper::get('OVERTIME_COSTS', 25000);
            $bonusCost = (int)CostSettingsHelper::get('OVERTIME_BONUS_COSTS', 30000);

            $baseTotal = $hours * $costPerHour;
            $bonusTotal = intdiv($hours, 24) * $bonusCost;

            $overtime->total = $baseTotal + $bonusTotal;
            $overtime->status_1 = $isManager || $isTeamLeader ? 'approved' : 'pending';
            $overtime->status_2 = $isManager ? 'approved' : 'pending';
            $overtime->approver_1_id = ($isManager || $isTeamLeader) ? $submitter->id : null;
            $overtime->approver_2_id = $isManager ? $submitter->id : null;
            $overtime->approved_date = $isManager ? now() : null;
            $overtime->save();

            if ($isManager) {
                return $overtime;
            }

            if ($isTeamLeader) {
                $manager = $this->resolveManager();
                if ($manager) {
                    $tokenRaw = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($overtime),
                        'model_id' => $overtime->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2,
                        'scope' => 'both',
                        'token' => hash('sha256', $tokenRaw),
                        'expires_at' => now()->addDays(3),
                    ]);

                    DB::afterCommit(function () use ($overtime, $manager, $tokenRaw) {
                        $fresh = $overtime->fresh();
                        event(new \App\Events\OvertimeLevelAdvanced(
                            $fresh,
                            $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                            'manager'
                        ));

                        $linkTanggapan = route('public.approval.show', $tokenRaw);

                        Mail::to($manager->email)->queue(
                            new SendMessage(
                                namaPengaju: Auth::user()->name,
                                namaApprover: $manager->name,
                                linkTanggapan: $linkTanggapan,
                                emailPengaju: Auth::user()->email,
                            )
                        );
                    });
                }
            } else {
                $fresh = $overtime->fresh();
                event(new \App\Events\OvertimeSubmitted($fresh, Auth::user()->division_id));
                $this->notify($overtime, $minutes);
            }

            return $overtime;
        });
    }

    public function update(Overtime $overtime, array $data): Overtime
    {
        if ($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') {
            throw new \Exception('You cannot update an overtime request that has already been processed.');
        }

        $start = Carbon::createFromFormat('Y-m-d\TH:i', $data['date_start'], 'Asia/Jakarta');
        $end = Carbon::createFromFormat('Y-m-d\TH:i', $data['date_end'], 'Asia/Jakarta');

        // Validasi jam mulai hari ini
        if ($start->isToday() && $start->lt(Carbon::today()->setTime(17, 0))) {
            throw new \Exception('Jika tanggal mulai adalah hari ini, maka waktu mulai harus setelah jam 17:00.');
        }

        $minutes = $start->diffInMinutes($end);
        $hours = $minutes / 60;

        if ($hours < 0.5) {
            throw new \Exception('Minimum overtime is 0.5 hours.');
        }

        $submitter = Auth::user();
        $isManager = $submitter->userHasRole('manager');
        $isTeamLeader = $submitter->userHasRole('team-leader');

        $baseTotal = floor($hours) * (int) CostSettingsHelper::get('OVERTIME_COSTS', 25000);
        $bonusTotal = intdiv(floor($hours), 24) * (int)CostSettingsHelper::get('OVERTIME_BONUS_COSTS', 30000);

        $overtime->update([
            'customer' => $data['customer'],
            'date_start' => $start,
            'date_end' => $end,
            'total' => $baseTotal + $bonusTotal,
            'status_1' => $isManager || $isTeamLeader ? 'approved' : 'pending',
            'status_2' => $isManager ? 'approved' : 'pending',
            'approver_1_id' => ($isManager || $isTeamLeader) ? $submitter->id : null,
            'approver_2_id' => $isManager ? $submitter->id : null,
            'approved_date' => $isManager ? now() : null,
            'note_1' => null,
            'note_2' => null,
        ]);

        if ($isManager) {
            return $overtime;
        }

        if ($isTeamLeader) {
            $manager = $this->resolveManager();
            if ($manager) {
                $tokenRaw = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($overtime),
                    'model_id' => $overtime->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2,
                    'scope' => 'both',
                    'token' => hash('sha256', $tokenRaw),
                    'expires_at' => now()->addDays(3),
                ]);

                DB::afterCommit(function () use ($overtime, $manager, $tokenRaw) {
                    $fresh = $overtime->fresh();
                    event(new \App\Events\OvertimeLevelAdvanced(
                        $fresh,
                        $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                        'manager'
                    ));

                    $linkTanggapan = route('public.approval.show', $tokenRaw);

                    Mail::to($manager->email)->queue(
                        new SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $manager->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                        )
                    );
                });
            }
        } else {
            $fresh = $overtime->fresh();
            event(new \App\Events\OvertimeSubmitted($fresh, Auth::user()->division_id));
            $this->notify($overtime, $minutes);
        }

        return $overtime;
    }

    private function notify(Overtime $overtime, int $minutes): void
    {
        if (!$overtime->approver)
            return;

        $tokenRaw = Str::random(48);

        ApprovalLink::create([
            'model_type' => get_class($overtime),
            'model_id' => $overtime->id,
            'approver_user_id' => $overtime->approver->id,
            'level' => 1,
            'scope' => 'both',
            'token' => hash('sha256', $tokenRaw),
            'expires_at' => now()->addDays(3),
        ]);

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        DB::afterCommit(function () use ($overtime, $tokenRaw, $hours, $mins) {
            $linkTanggapan = route('public.approval.show', $tokenRaw);

            Mail::to($overtime->approver->email)->queue(
                new SendMessage(
                    namaPengaju: Auth::user()->name,
                    namaApprover: $overtime->approver->name,
                    linkTanggapan: $linkTanggapan,
                    emailPengaju: Auth::user()->email,
                )
            );
        });
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
