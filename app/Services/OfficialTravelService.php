<?php

namespace App\Services;

use App\Helpers\CostSettingsHelper;
use App\Enums\Roles;
use App\Models\OfficialTravel;
use App\Models\ApprovalLink;
use App\Models\Role;
use App\Models\User;
use App\Mail\SendMessage;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OfficialTravelService
{
    public function __construct(
        private readonly HolidayDateService $holidayDateService,
    ) {}

    public function store(array $data): OfficialTravel
    {
        return DB::transaction(function () use ($data) {
            $submitter = Auth::user();
            $isManager = $submitter->userHasRole('manager');
            $isTeamLeader = $submitter->userHasRole('team-leader');

            $start = Carbon::parse($data['date_start'])->startOfDay();
            $end = Carbon::parse($data['date_end'])->startOfDay();
            $days = $start->diffInDays($end) + 1;

            // Hitung biaya per hari
            $weekDayCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_DAY', 150000);
            $weekEndCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_END', 225000);

            $holidayDates = $this->holidayDateService->getDateStrings($start, $end);

            $period = CarbonPeriod::create($start, $end);

            $totalCost = 0;
            foreach ($period as $date) {
                $isWeekend = $date->isWeekend();
                $isHoliday = in_array($date->toDateString(), $holidayDates);

                if ($isWeekend || $isHoliday) {
                    $totalCost += $weekEndCost;
                } else {
                    $totalCost += $weekDayCost;
                }
            }

            $travel = OfficialTravel::create([
                'employee_id' => Auth::id(),
                'customer' => $data['customer'],
                'date_start' => $start,
                'date_end' => $end,
                'total' => $totalCost,
                'status_1' => $isManager || $isTeamLeader ? 'approved' : 'pending',
                'status_2' => $isManager ? 'approved' : 'pending',
                'approver_1_id' => ($isManager || $isTeamLeader) ? $submitter->id : null,
                'approver_2_id' => $isManager ? $submitter->id : null,
                'approved_date' => $isManager ? now() : null,
            ]);

            if ($isManager) {
                return $travel;
            }

            if ($isTeamLeader) {
                $manager = $this->resolveManager();
                if ($manager) {
                    $tokenRaw = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($travel),
                        'model_id' => $travel->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2,
                        'scope' => 'both',
                        'token' => hash('sha256', $tokenRaw),
                        'expires_at' => now()->addDays(3),
                    ]);

                    DB::afterCommit(function () use ($travel, $manager, $tokenRaw) {
                        $fresh = $travel->fresh();
                        event(new \App\Events\OfficialTravelLevelAdvanced(
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
                $fresh = $travel->fresh();
                event(new \App\Events\OfficialTravelSubmitted($fresh, Auth::user()->division_id ?? 0));
                $this->notify($travel, $days);
            }


            return $travel;
        });
    }

    public function update(OfficialTravel $travel, array $data): OfficialTravel
    {
        if ($travel->status_1 !== 'pending' || $travel->status_2 !== 'pending') {
            throw new Exception('Travel request sudah diproses, tidak bisa diupdate.');
        }

        $start = Carbon::parse($data['date_start'])->startOfDay();
        $end = Carbon::parse($data['date_end'])->startOfDay();
        $days = $start->diffInDays($end) + 1;

        $weekDayCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_DAY', 150000);
        $weekEndCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_END', 225000);

        $holidayDates = $this->holidayDateService->getDateStrings($start, $end);

        $period = CarbonPeriod::create($start, $end);

        $totalCost = 0;
        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();
            $isHoliday = in_array($date->toDateString(), $holidayDates);

            if ($isWeekend || $isHoliday) {
                $totalCost += $weekEndCost;
            } else {
                $totalCost += $weekDayCost;
            }
        }

        $submitter = Auth::user();
        $isManager = $submitter->userHasRole('manager');
        $isTeamLeader = $submitter->userHasRole('team-leader');

        $travel->update([
            'customer' => $data['customer'],
            'date_start' => $start,
            'date_end' => $end,
            'total' => $totalCost,
            'status_1' => $isManager || $isTeamLeader ? 'approved' : 'pending',
            'status_2' => $isManager ? 'approved' : 'pending',
            'approver_1_id' => ($isManager || $isTeamLeader) ? $submitter->id : null,
            'approver_2_id' => $isManager ? $submitter->id : null,
            'approved_date' => $isManager ? now() : null,
            'note_1' => null,
            'note_2' => null,
        ]);


        if ($isManager) {
            return $travel;
        }

        if ($isTeamLeader) {
            $manager = $this->resolveManager();
            if ($manager) {
                $tokenRaw = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($travel),
                    'model_id' => $travel->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2,
                    'scope' => 'both',
                    'token' => hash('sha256', $tokenRaw),
                    'expires_at' => now()->addDays(3),
                ]);

                DB::afterCommit(function () use ($travel, $manager, $tokenRaw) {
                    $fresh = $travel->fresh();
                    event(new \App\Events\OfficialTravelLevelAdvanced(
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
            $fresh = $travel->fresh();
            event(new \App\Events\OfficialTravelSubmitted($fresh, Auth::user()->division_id ?? 0));
            $this->notify($travel, $days);
        }

        return $travel;
    }

    private function notify(OfficialTravel $travel, int $days): void
    {
        if (!$travel->approver)
            return;

        $tokenRaw = Str::random(48);

        ApprovalLink::create([
            'model_type' => get_class($travel),
            'model_id' => $travel->id,
            'approver_user_id' => $travel->approver->id,
            'level' => 1,
            'scope' => 'both',
            'token' => hash('sha256', $tokenRaw),
            'expires_at' => now()->addDays(3),
        ]);

        DB::afterCommit(function () use ($travel, $tokenRaw) {
            $linkTanggapan = route('public.approval.show', $tokenRaw);

            Mail::to($travel->approver->email)->queue(
                new SendMessage(
                    namaPengaju: Auth::user()->name,
                    namaApprover: $travel->approver->name,
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
