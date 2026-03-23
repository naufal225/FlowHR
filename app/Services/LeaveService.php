<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\User;
use App\Models\ApprovalLink;
use App\Models\Holiday;
use App\Enums\Roles;
use App\Models\Role;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LeaveService
{
    public function sisaCutiForYear(User $user, int $tahun, $excludeLeaveId = null): int
    {
        $hariLibur = Holiday::whereYear('holiday_date', $tahun)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $cutiList = Leave::where('employee_id', $user->id)
            ->where('status_1', 'approved')
            ->when($excludeLeaveId, fn($q) => $q->where('id', '!=', $excludeLeaveId))
            ->where(function ($q) use ($tahun) {
                $q->whereYear('date_start', $tahun)
                    ->orWhereYear('date_end', $tahun);
            })
            ->get();

        $total = $cutiList->sum(
            fn($cuti) => $this->hitungHariCuti($cuti->date_start, $cuti->date_end, $tahun, $hariLibur)
        );

        $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        return max(0, $annual - $total);
    }
    public function hitungHariCuti($dateStart, $dateEnd, int $tahun, array $hariLibur): int
    {
        $start = Carbon::parse($dateStart)->copy();
        $end = Carbon::parse($dateEnd)->copy();

        // Batas tahun
        if ($start->year < $tahun) {
            $start = Carbon::create($tahun, 1, 1);
        }
        if ($end->year > $tahun) {
            $end = Carbon::create($tahun, 12, 31);
        }

        if ($start->gt($end)) {
            return 0;
        }

        // pastikan format holiday sesuai Y-m-d
        $holidayDates = array_map(fn($d) => Carbon::parse($d)->format('Y-m-d'), $hariLibur);

        $hariCuti = 0;
        while ($start->lte($end)) {
            // skip weekend
            if ($start->isWeekend()) {
                $start->addDay();
                continue;
            }

            // skip holiday
            if (in_array($start->format('Y-m-d'), $holidayDates, true)) {
                $start->addDay();
                continue;
            }

            $hariCuti++;
            $start->addDay();
        }

        return $hariCuti;
    }


    public function sisaCuti(User $user, $excludeLeaveId = null): int
    {
        $tahunSekarang = now()->year;

        $hariLibur = Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $cutiList = Leave::where('employee_id', $user->id)
            ->where('status_1', 'approved')
            ->when($excludeLeaveId, fn($q) => $q->where('id', '!=', $excludeLeaveId))
            ->where(function ($q) use ($tahunSekarang) {
                $q->whereYear('date_start', $tahunSekarang)
                    ->orWhereYear('date_end', $tahunSekarang);
            })
            ->get();

        $total = $cutiList->sum(
            fn($cuti) =>
            $this->hitungHariCuti($cuti->date_start, $cuti->date_end, $tahunSekarang, $hariLibur)
        );

        // dd($total);

        $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        return max(0, $annual - $total);
    }

    public function store(array $data): Leave
    {
        $user = Auth::user();
        $tahunSekarang = now()->year;

        $hariLibur = Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $hariBaru = $this->hitungHariCuti($data['date_start'], $data['date_end'], $tahunSekarang, $hariLibur);
        $sisaCuti = $this->sisaCuti($user);

        if ($hariBaru > $sisaCuti) {
            throw new Exception("Sisa cuti hanya {$sisaCuti} hari, tidak bisa ajukan {$hariBaru} hari.");
        }

        return DB::transaction(function () use ($data, $user) {
            $leave = new Leave();
            $leave->employee_id = $user->id;
            $leave->date_start = $data['date_start'];
            $leave->date_end = $data['date_end'];
            $leave->reason = $data['reason'];
            $leave->status_1 = 'pending';
            $leave->save();

            $fresh = $leave->fresh();

            [$approverUser, $newLevel] = $this->resolveApprover(Auth::user());

            event(new \App\Events\LeaveLevelAdvanced($fresh, Auth::user()->division_id, $newLevel));

            $this->notify($leave, $approverUser);

            return $leave;
        });
    }

    public function update(Leave $leave, array $data): Leave
    {
        if ($leave->status_1 !== 'pending') {
            throw new Exception('Leave request sudah diproses, tidak bisa diupdate.');
        }

        $user = Auth::user();
        $tahunSekarang = now()->year;

        $hariLibur = Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Hitung cuti baru
        $hariBaru = $this->hitungHariCuti($data['date_start'], $data['date_end'], $tahunSekarang, $hariLibur);

        // Hitung cuti lama
        $hariLama = $this->hitungHariCuti($leave->date_start, $leave->date_end, $tahunSekarang, $hariLibur);

        $sisaCuti = $this->sisaCuti($user, $leave->id);

        // Jika cuti baru lebih panjang, cek tambahan
        if ($hariBaru > $sisaCuti) {
            throw new Exception("Sisa cuti tidak cukup untuk memperpanjang cuti. Tersisa {$sisaCuti} hari.");
        }

        $leave->update([
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'],
            'reason' => $data['reason'],
            'status_1' => 'pending',
            'note_1' => null,
        ]);

        $fresh = $leave->fresh();

        [$approverUser, $newLevel] = $this->resolveApprover(Auth::user());

        event(new \App\Events\LeaveLevelAdvanced($fresh, Auth::user()->division_id, $newLevel));


        $this->notify($leave, $approverUser);

        return $leave;
    }

    private function notify(Leave $leave, ?User $approver): void
    {
        if (!$approver) return;

        $tokenRaw = Str::random(48);

        ApprovalLink::create([
            'model_type' => get_class($leave),
            'model_id' => $leave->id,
            'approver_user_id' => $approver->id,
            'level' => 1,
            'scope' => 'both',
            'token' => hash('sha256', $tokenRaw),
            'expires_at' => now()->addDays(3),
        ]);

        DB::afterCommit(function () use ($leave, $approver, $tokenRaw) {
            $linkTanggapan = route('public.approval.show', $tokenRaw);

            Mail::to($approver->email)->queue(
                new \App\Mail\SendMessage(
                    namaPengaju: $leave->employee->name,
                    namaApprover: $approver->name,
                linkTanggapan: $linkTanggapan,
                emailPengaju: $leave->employee->email
                )
            );
        });
    }

    /**
     * Resolve approver for applicant.
     */
    private function resolveApprover(User $applicant): array
    {
        $isLeader = \App\Models\Division::where('leader_id', $applicant->id)->exists();
        $isApprover = $applicant->roles()->where('name', Roles::Approver->value)->exists();

        if (!$isLeader && !$isApprover) {
            $leader = $applicant->division?->leader;
            if ($leader) {
                return [$leader, 'approver'];
            }
        }

        $managerUser = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
        return [$managerUser, 'manager'];
    }
}
