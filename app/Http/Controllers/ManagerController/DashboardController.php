<?php

namespace App\Http\Controllers\ManagerController;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Traits\HelperController;
use App\Models\FeatureSetting;
use App\Services\Dashboard\DashboardLeaveCalendarService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use HelperController;
    public function index(
        DashboardLeaveCalendarService $dashboardLeaveCalendarService,
        LeaveService $leaveService
    )
    {
        $featureActive = [
            'cuti' => FeatureSetting::isActive('cuti'),
            'reimbursement' => FeatureSetting::isActive('reimbursement'),
            'overtime' => FeatureSetting::isActive('overtime'),
            'perjalanan_dinas' => FeatureSetting::isActive('perjalanan_dinas'),
        ];
        $models = [
            "reimbursements" => Reimbursement::class,
            "overtimes" => Overtime::class,
            "leaves" => Leave::class,
            "official_travels" => OfficialTravel::class
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $pendings = $approveds = $rejecteds = [];

        $mapFeature = [
            'reimbursements' => 'reimbursement',
            'overtimes' => 'overtime',
            'leaves' => 'cuti',
            'official_travels' => 'perjalanan_dinas',
        ];
        foreach ($models as $key => $model) {
            if (isset($mapFeature[$key]) && !$featureActive[$mapFeature[$key]]) {
                $pendings[$key] = 0;
                $rejecteds[$key] = 0;
                $approveds[$key] = 0;
                continue;
            }
            $base = $model::query()->where('created_at', '>=', $startOfMonth);

            // Aman untuk single/dual status karena pakai scope trait
            $pendings[$key] = (clone $base)->filterFinalStatus('pending')->count();
            $rejecteds[$key] = (clone $base)->filterFinalStatus('rejected')->count();
            $approveds[$key] = (clone $base)->filterFinalStatus('approved')->count();
        }

        $total_pending = array_sum($pendings);
        $total_rejected = array_sum($rejecteds);
        $total_approved = array_sum($approveds);

        $employeeRole = Role::where('name', Roles::Employee->value)->firstOr();

        $total_employees = User::whereHas('roles', function ($q) use ($employeeRole) {
            $q->where('roles.id', $employeeRole->id);
        })->count();

        // Generate chart data per bulan
        $reimbursementsChartData = $overtimesChartData = $leavesChartData = $officialTravelsChartData = $reimbursementsRupiahChartData = [];
        $months = [];

        $year = now()->year;

        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::create($year, $i, 1);
            $monthName = $date->translatedFormat('F');
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $months[] = $monthName;
            $reimbursementsChartData[] = $featureActive['reimbursement'] ? Reimbursement::whereBetween('created_at', [$start, $end])->count() : 0;
            $reimbursementsRupiahChartData[] = $featureActive['reimbursement'] ? Reimbursement::whereBetween('created_at', [$start, $end])->sum('total') : 0;
            $overtimesChartData[] = $featureActive['overtime'] ? Overtime::whereBetween('created_at', [$start, $end])->count() : 0;
            $leavesChartData[] = $featureActive['cuti'] ? Leave::whereBetween('created_at', [$start, $end])->count() : 0;
            $officialTravelsChartData[] = $featureActive['perjalanan_dinas'] ? OfficialTravel::whereBetween('created_at', [$start, $end])->count() : 0;
        }

        $sisaCuti = 0;
        if ($featureActive['cuti']) {
            $sisaCuti = $leaveService->sisaCuti(Auth::user());
        }

        $recentRequests = $this->getRecentRequests(Auth::id());

        $cutiPerTanggal = [];
        $holidayDates = [];
        $holidaysByDate = [];
        if ($featureActive['cuti']) {
            $calendarData = $dashboardLeaveCalendarService->build(Leave::query(), now()->year);
            $cutiPerTanggal = $calendarData['approved_by_date'];
            $holidayDates = $calendarData['holiday_dates'];
            $holidaysByDate = $calendarData['holidays_by_date'];
        }
        return view('manager.dashboard.index', compact([
            'total_employees',
            'total_pending',
            'total_approved',
            'total_rejected',
            'reimbursementsChartData',
            'overtimesChartData',
            'leavesChartData',
            'officialTravelsChartData',
            'months',
            'reimbursementsRupiahChartData',
            'sisaCuti',
            'recentRequests',
            'cutiPerTanggal',
            'holidayDates',
            'holidaysByDate',
            'featureActive'
        ]));

    }

}
