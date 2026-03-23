<?php

namespace App\Http\Controllers\FinanceController;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Enums\Roles;
use App\Enums\TypeRequest;
use App\Models\Reimbursement;
use App\Models\Overtime;
use App\Models\OfficialTravel;
use App\Models\FeatureSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        // Hanya user dengan role Finance yang boleh masuk
        if (!Auth::user()->roles()->where('name', Roles::Finance->value)->exists()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();
        $userId = $user->id;
        $thisYear = now()->year;
        $thisMonth = now()->month;

        $featureActive = [
            'cuti' => FeatureSetting::isActive('cuti'),
            'reimbursement' => FeatureSetting::isActive('reimbursement'),
            'overtime' => FeatureSetting::isActive('overtime'),
            'perjalanan_dinas' => FeatureSetting::isActive('perjalanan_dinas'),
        ];

        $holidays = \App\Models\Holiday::pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->toArray();

        // =========================
        // DATA PRIBADI FINANCE (YOURS)
        // =========================
        $pendingYoursLeaves = $featureActive['cuti']
            ? Leave::where('employee_id', $userId)->where('status_1', 'pending')->count()
            : 0;

        $pendingYoursOvertimes = $featureActive['overtime']
            ? Overtime::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'pending')->orWhere('status_2', 'pending'))->count()
            : 0;

        $pendingYoursReimbursements = $featureActive['reimbursement']
            ? Reimbursement::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'pending')->orWhere('status_2', 'pending'))->count()
            : 0;

        $pendingYoursTravels = $featureActive['perjalanan_dinas']
            ? OfficialTravel::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'pending')->orWhere('status_2', 'pending'))->count()
            : 0;

        // Approved this month
        $approvedYoursLeaves = $featureActive['cuti']
            ? Leave::where('employee_id', $userId)->where('status_1', 'approved')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $approvedYoursOvertimes = $featureActive['overtime']
            ? Overtime::where('employee_id', $userId)->where('status_1', 'approved')->where('status_2', 'approved')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $approvedYoursReimbursements = $featureActive['reimbursement']
            ? Reimbursement::where('employee_id', $userId)->where('status_1', 'approved')->where('status_2', 'approved')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $approvedYoursTravels = $featureActive['perjalanan_dinas']
            ? OfficialTravel::where('employee_id', $userId)->where('status_1', 'approved')->where('status_2', 'approved')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        // Rejected this month
        $rejectedYoursLeaves = $featureActive['cuti']
            ? Leave::where('employee_id', $userId)->where('status_1', 'rejected')->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $rejectedYoursOvertimes = $featureActive['overtime']
            ? Overtime::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'rejected')->orWhere('status_2', 'rejected'))->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $rejectedYoursReimbursements = $featureActive['reimbursement']
            ? Reimbursement::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'rejected')->orWhere('status_2', 'rejected'))->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        $rejectedYoursTravels = $featureActive['perjalanan_dinas']
            ? OfficialTravel::where('employee_id', $userId)->where(fn($q) => $q->where('status_1', 'rejected')->orWhere('status_2', 'rejected'))->whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count()
            : 0;

        // =========================
        // DATA UNTUK CARD (SEMUA EMPLOYEE)
        // =========================
        $leaveCount = $featureActive['cuti'] ? Leave::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')
            ->count() : 0;

        $overtimeCount = $featureActive['overtime'] ? Overtime::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->count() : 0;

        $reimbursementCount = $featureActive['reimbursement'] ? Reimbursement::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->count() : 0;

        $officialTravelCount = $featureActive['perjalanan_dinas'] ? OfficialTravel::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->count() : 0;

        // =========================
        // CHART DATA BULANAN
        // =========================
        $months = collect(range(1, 12))->map(fn($m) => Carbon::create()->month($m)->format('M'));

        $leavesChartData = [];
        $overtimesChartData = [];
        $reimbursementsChartData = [];
        $reimbursementsRupiahChartData = [];
        $officialTravelsChartData = [];

        foreach (range(1, 12) as $month) {
            $start = Carbon::create(null, $month, 1)->startOfMonth();
            $end = Carbon::create(null, $month, 1)->endOfMonth();

            $leavesChartData[] = $featureActive['cuti'] ? Leave::whereHas('employee', function ($q) {
                $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
            })
                ->where('status_1', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->count() : 0;

            $overtimesChartData[] = $featureActive['overtime'] ? Overtime::whereHas('employee', function ($q) {
                $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
            })
                ->where('status_1', 'approved')->where('status_2', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->count() : 0;

            $reimbursementsChartData[] = $featureActive['reimbursement'] ? Reimbursement::whereHas('employee', function ($q) {
                $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
            })
                ->where('status_1', 'approved')->where('status_2', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->count() : 0;

            $reimbursementsRupiahChartData[] = $featureActive['reimbursement'] ? Reimbursement::whereHas('employee', function ($q) {
                $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
            })
                ->where('status_1', 'approved')->where('status_2', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->sum('total') : 0;

            $officialTravelsChartData[] = $featureActive['perjalanan_dinas'] ? OfficialTravel::whereHas('employee', function ($q) {
                $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
            })
                ->where('status_1', 'approved')->where('status_2', 'approved')
                ->whereBetween('created_at', [$start, $end])
                ->count() : 0;
        }

        // =========================
        // CUTI PER TANGGAL
        // =========================
        $cutiPerTanggal = [];
        if ($featureActive['cuti']) {
            $karyawanCuti = Leave::with(['employee:id,name,email,url_profile'])
                ->where('status_1', 'approved')
                ->where(function ($q) use ($thisYear) {
                    $q->whereYear('date_start', $thisYear)
                        ->orWhereYear('date_end', $thisYear);
                })
                ->get(['id', 'employee_id', 'date_start', 'date_end']);

            foreach ($karyawanCuti as $cuti) {
                $period = \Carbon\CarbonPeriod::create($cuti->date_start, $cuti->date_end);

                foreach ($period as $date) {
                    if (!$date->isWeekend() && !in_array($date->toDateString(), $holidays)) {
                        $tanggal = $date->format('Y-m-d');
                        $cutiPerTanggal[$tanggal][] = [
                            'employee' => $cuti->employee->name,
                            'email' => $cuti->employee->email,
                            'url_profile' => $cuti->employee->url_profile,
                        ];
                    }
                }
            }
        }

        // =========================
        // TOTAL CUTI & SISA CUTI USER FINANCE
        // =========================
        $sisaCuti = 0;
        if ($featureActive['cuti']) {
            $totalHariCuti = Leave::where('employee_id', $userId)
                ->where('status_1', 'approved')
                ->where(function ($q) use ($thisYear) {
                    $q->whereYear('date_start', $thisYear)
                        ->orWhereYear('date_end', $thisYear);
                })
                ->get()
                ->sum(function ($cuti) use ($thisYear, $holidays) {
                    $start = \Carbon\Carbon::parse($cuti->date_start);
                    $end = \Carbon\Carbon::parse($cuti->date_end);

                    if ($start->year < $thisYear)
                        $start = \Carbon\Carbon::create($thisYear, 1, 1);
                    if ($end->year > $thisYear)
                        $end = \Carbon\Carbon::create($thisYear, 12, 31);

                    if ($start->lte($end)) {
                        $period = \Carbon\CarbonPeriod::create($start, $end);
                        return collect($period)->filter(
                            fn($d) =>
                            !$d->isWeekend() &&
                            !in_array($d->toDateString(), $holidays)
                        )->count();
                    }
                    return 0;
                });

            $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
            $sisaCuti = $annual - $totalHariCuti;
        }
        $recentRequests = $this->getRecentRequests();

        // =========================
        // RETURN VIEW
        // =========================
        return view('Finance.index', compact(
            'leaveCount',
            'overtimeCount',
            'reimbursementCount',
            'officialTravelCount',
            'months',
            'leavesChartData',
            'overtimesChartData',
            'reimbursementsChartData',
            'reimbursementsRupiahChartData',
            'officialTravelsChartData',
            'cutiPerTanggal',
            'pendingYoursLeaves',
            'pendingYoursOvertimes',
            'pendingYoursReimbursements',
            'pendingYoursTravels',
            'approvedYoursLeaves',
            'approvedYoursOvertimes',
            'approvedYoursReimbursements',
            'approvedYoursTravels',
            'rejectedYoursLeaves',
            'rejectedYoursOvertimes',
            'rejectedYoursReimbursements',
            'rejectedYoursTravels',
            'sisaCuti',
            'recentRequests',
            'featureActive'
        ));
    }

    private function getRecentRequests()
    {
        // Get recent leaves
        $leaves = Leave::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'type' => TypeRequest::Leaves->value,
                    'title' => 'Leave Request: ' . Carbon::parse($leave->date_start)->format('M d') . ' - ' . Carbon::parse($leave->date_end)->format('M d'),
                    'name_owner' => $leave->employee->name,
                    'email_owner' => $leave->employee->email,
                    'url_photo' => $leave->employee->url_profile,
                    'date' => Carbon::parse($leave->created_at)->format('M d, Y'),
                    'status_1' => $leave->status_1,
                    'url' => route('finance.leaves.show', $leave->id),
                    'created_at' => $leave->created_at
                ];
            });

        // Get recent reimbursements
        $reimbursements = Reimbursement::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($reimbursement) {
                return [
                    'id' => $reimbursement->id,
                    'type' => TypeRequest::Reimbursements->value,
                    'title' => 'Reimbursement: Rp ' . number_format($reimbursement->total),
                    'name_owner' => $reimbursement->employee->name,
                    'email_owner' => $reimbursement->employee->email,
                    'url_photo' => $reimbursement->employee->url_profile,
                    'date' => Carbon::parse($reimbursement->created_at)->format('M d, Y'),
                    'status_1' => $reimbursement->status_1,
                    'status_2' => $reimbursement->status_2,
                    'url' => route('finance.reimbursements.show', $reimbursement->id),
                    'created_at' => $reimbursement->created_at
                ];
            });

        // Get recent overtimes
        $overtimes = Overtime::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'type' => TypeRequest::Overtimes->value,
                    'title' => 'Overtime: ' . Carbon::parse($overtime->date_start)->format('M d'),
                    'date' => Carbon::parse($overtime->created_at)->format('M d, Y'),
                    'name_owner' => $overtime->employee->name,
                    'email_owner' => $overtime->employee->email,
                    'url_photo' => $overtime->employee->url_profile,
                    'status_1' => $overtime->status_1,
                    'status_2' => $overtime->status_2,
                    'url' => route('finance.overtimes.show', $overtime->id),
                    'created_at' => $overtime->created_at
                ];
            });

        // Get recent official travels
        $travels = OfficialTravel::whereHas('employee', function ($q) {
            $q->whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value));
        })
            ->where('status_1', 'approved')->where('status_2', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($travel) {
                return [
                    'id' => $travel->id,
                    'type' => TypeRequest::Travels->value,
                    'title' => 'Official Travel: ' . Carbon::parse($travel->date_start)->format('M d') . ' - ' . Carbon::parse($travel->date_end)->format('M d'),
                    'name_owner' => $travel->employee->name,
                    'email_owner' => $travel->employee->email,
                    'url_photo' => $travel->employee->url_profile,
                    'date' => Carbon::parse($travel->created_at)->format('M d, Y'),
                    'status_1' => $travel->status_1,
                    'status_2' => $travel->status_2,
                    'url' => route('finance.official-travels.show', $travel->id),
                    'created_at' => $travel->created_at
                ];
            });

        // Combine all requests and sort by date
        $allRequests = $leaves->concat($reimbursements)
            ->concat($overtimes)
            ->concat($travels)
            ->sortByDesc('created_at')
            ->take(4)
            ->values()
            ->all();

        return $allRequests;
    }
}
