<?php

namespace App\Http\Controllers\EmployeeController;

use App\Http\Controllers\Controller;
use App\Models\FeatureSetting;
use App\Models\Leave;
use App\Enums\Roles;
use App\Enums\TypeRequest;
use App\Models\Reimbursement;
use App\Models\Overtime;
use App\Models\OfficialTravel;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $userId = $user->id;

        $employeeRole = Role::where('name', Roles::Employee->value)->first();

        $employeeCount = User::whereHas('roles', function ($query) use ($employeeRole) {
            $query->where('roles.id', $employeeRole->id);
        })->count();

        // Feature flags
        $featureActive = [
            'cuti' => FeatureSetting::isActive('cuti'),
            'reimbursement' => FeatureSetting::isActive('reimbursement'),
            'overtime' => FeatureSetting::isActive('overtime'),
            'perjalanan_dinas' => FeatureSetting::isActive('perjalanan_dinas'),
        ];

        // Query untuk list data (pakai orderBy) - hanya digunakan bila fitur aktif
        $queryLeave = $featureActive['cuti']
            ? Leave::where('employee_id', $userId)
                ->with(['employee', 'approver1','approver2'])
                ->orderBy('created_at', 'desc')
            : null;

        $queryClone = $queryLeave ? (clone $queryLeave) : null;

        $queryReimbursement = $featureActive['reimbursement']
            ? Reimbursement::where('employee_id', $userId)
                ->with(['employee', 'approver1','approver2'])
                ->orderBy('created_at', 'desc')
            : null;

        $queryOvertime = $featureActive['overtime']
            ? Overtime::where('employee_id', $userId)
                ->with(['employee', 'approver1','approver2'])
                ->orderBy('created_at', 'desc')
            : null;

        $queryTravel = $featureActive['perjalanan_dinas']
            ? OfficialTravel::where('employee_id', $userId)
                ->with(['employee', 'approver1','approver2'])
                ->orderBy('created_at', 'desc')
            : null;

        // 🔹 Query khusus untuk count (tanpa orderBy)
        $leaveCounts = $featureActive['cuti']
            ? Leave::where('employee_id', $userId)->withFinalStatusCount()->first()
            : null;
        $reimCounts = $featureActive['reimbursement']
            ? Reimbursement::where('employee_id', $userId)->withFinalStatusCount()->first()
            : null;
        $overtimeCounts = $featureActive['overtime']
            ? Overtime::where('employee_id', $userId)->withFinalStatusCount()->first()
            : null;
        $travelCounts = $featureActive['perjalanan_dinas']
            ? OfficialTravel::where('employee_id', $userId)->withFinalStatusCount()->first()
            : null;

        // Ambil hasil count (pakai null coalescing untuk aman)
        $pendingLeaves = $leaveCounts->pending ?? 0;
        $approvedLeaves = $leaveCounts->approved ?? 0;
        $rejectedLeaves = $leaveCounts->rejected ?? 0;

        $pendingReimbursements = $reimCounts->pending ?? 0;
        $approvedReimbursements = $reimCounts->approved ?? 0;
        $rejectedReimbursements = $reimCounts->rejected ?? 0;

        $pendingOvertimes = $overtimeCounts->pending ?? 0;
        $approvedOvertimes = $overtimeCounts->approved ?? 0;
        $rejectedOvertimes = $overtimeCounts->rejected ?? 0;

        $pendingTravels = $travelCounts->pending ?? 0;
        $approvedTravels = $travelCounts->approved ?? 0;
        $rejectedTravels = $travelCounts->rejected ?? 0;

        // Get recent requests (combined from all types)
        $recentRequests = $this->getRecentRequests($userId, $featureActive);

        // Hitung total cuti tahun berjalan
        $tahunSekarang = now()->year;

        // Ambil daftar libur dari tabel holidays
        $holidays = $featureActive['cuti']
            ? \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
                ->pluck('holiday_date')
                ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
                ->toArray()
            : [];

        $sisaCuti = 0;
        if ($featureActive['cuti'] && $queryClone) {
            $totalHariCuti = $queryClone
                ->where('status_1', 'approved')
                ->where(function ($q) use ($tahunSekarang) {
                    $q->whereYear('date_start', $tahunSekarang)
                        ->orWhereYear('date_end', $tahunSekarang);
                })
                ->get()
                ->sum(function ($cuti) use ($tahunSekarang, $holidays) {
                    $start = \Carbon\Carbon::parse($cuti->date_start);
                    $end = \Carbon\Carbon::parse($cuti->date_end);

                    if ($start->year < $tahunSekarang) {
                        $start = \Carbon\Carbon::create($tahunSekarang, 1, 1);
                    }
                    if ($end->year > $tahunSekarang) {
                        $end = \Carbon\Carbon::create($tahunSekarang, 12, 31);
                    }

                    return $start->lte($end)
                        ? collect(\Carbon\CarbonPeriod::create($start, $end))->filter(function ($date) use ($holidays) {
                            return !$date->isWeekend() && !in_array($date->toDateString(), $holidays);
                        })->count()
                        : 0;

                });

            $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
            $sisaCuti = $annual - $totalHariCuti;
        }

        // Data cuti semua karyawan untuk kalender
        $cutiPerTanggal = [];
        if ($featureActive['cuti']) {
            $karyawanCuti = Leave::with(['employee:id,name,email,url_profile'])
                ->where('status_1', 'approved')
                ->where(function ($q) {
                    $q->whereYear('date_start', now()->year)
                        ->orWhereYear('date_end', now()->year);
                })
                ->get(['id', 'employee_id', 'date_start', 'date_end']);

            foreach ($karyawanCuti as $cuti) {
                $start = \Carbon\Carbon::parse($cuti->date_start);
                $end = \Carbon\Carbon::parse($cuti->date_end);
                while ($start->lte($end)) {
                    $tanggal = $start->format('Y-m-d');

                    // Skip weekend & holiday
                    if (!$start->isWeekend() && !in_array($tanggal, $holidays)) {
                        $cutiPerTanggal[$tanggal][] = [
                            'employee' => $cuti->employee->name,
                            'email' => $cuti->employee->email,
                            'url_profile' => $cuti->employee->url_profile,
                        ];
                    }

                    $start->addDay();
                }
            }
        }

        return view('Employee.index', compact(
            'employeeCount',
            'pendingLeaves',
            'pendingReimbursements',
            'pendingOvertimes',
            'pendingTravels',
            'approvedLeaves',
            'approvedReimbursements',
            'approvedOvertimes',
            'approvedTravels',
            'rejectedLeaves',
            'rejectedReimbursements',
            'rejectedOvertimes',
            'rejectedTravels',
            'recentRequests',
            'sisaCuti',
            'cutiPerTanggal',
            'featureActive'
        ));
    }

    private function getRecentRequests($userId, array $featureActive)
    {
        $collections = collect();

        if ($featureActive['cuti']) {
            $collections = $collections->concat(
                Leave::where('employee_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($leave) {
                        return [
                            'id' => $leave->id,
                            'type' => TypeRequest::Leaves->value,
                            'title' => 'Leave Request: ' . Carbon::parse($leave->date_start)->format('M d') . ' - ' . Carbon::parse($leave->date_end)->format('M d'),
                            'date' => Carbon::parse($leave->created_at)->format('M d, Y'),
                            'status_1' => $leave->status_1,
                            'url' => route('employee.leaves.show', $leave->id),
                            'created_at' => $leave->created_at
                        ];
                    })
            );
        }

        if ($featureActive['reimbursement']) {
            $collections = $collections->concat(
                Reimbursement::where('employee_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($reimbursement) {
                        return [
                            'id' => $reimbursement->id,
                            'type' => TypeRequest::Reimbursements->value,
                            'title' => 'Reimbursement: Rp ' . number_format($reimbursement->total),
                            'date' => Carbon::parse($reimbursement->created_at)->format('M d, Y'),
                            'status_1' => $reimbursement->status_1,
                            'status_2' => $reimbursement->status_2,
                            'url' => route('employee.reimbursements.show', $reimbursement->id),
                            'created_at' => $reimbursement->created_at
                        ];
                    })
            );
        }

        if ($featureActive['overtime']) {
            $collections = $collections->concat(
                Overtime::where('employee_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($overtime) {
                        return [
                            'id' => $overtime->id,
                            'type' => TypeRequest::Overtimes->value,
                            'title' => 'Overtime: ' . Carbon::parse($overtime->date_start)->format('M d'),
                            'date' => Carbon::parse($overtime->created_at)->format('M d, Y'),
                            'status_1' => $overtime->status_1,
                            'status_2' => $overtime->status_2,
                            'url' => route('employee.overtimes.show', $overtime->id),
                            'created_at' => $overtime->created_at
                        ];
                    })
            );
        }

        if ($featureActive['perjalanan_dinas']) {
            $collections = $collections->concat(
                OfficialTravel::where('employee_id', $userId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($travel) {
                        return [
                            'id' => $travel->id,
                            'type' => TypeRequest::Travels->value,
                            'title' => 'Official Travel: ' . Carbon::parse($travel->date_start)->format('M d') . ' - ' . Carbon::parse($travel->date_end)->format('M d'),
                            'date' => Carbon::parse($travel->created_at)->format('M d, Y'),
                            'status_1' => $travel->status_1,
                            'status_2' => $travel->status_2,
                            'url' => route('employee.official-travels.show', $travel->id),
                            'created_at' => $travel->created_at
                        ];
                    })
            );
        }

        return $collections->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->all();
    }
}
