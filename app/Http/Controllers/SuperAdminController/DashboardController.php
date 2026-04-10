<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Http\Controllers\Controller;
use App\Traits\HelperController;
use App\Models\Leave;
use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Services\Dashboard\DashboardLeaveCalendarService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use HelperController;

    public function index(
        DashboardLeaveCalendarService $dashboardLeaveCalendarService,
        LeaveService $leaveService
    )
    {
        $models = [
            "reimbursements" => Reimbursement::class,
            "overtimes" => Overtime::class,
            "leaves" => Leave::class,
            "official_travels" => OfficialTravel::class
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $pendings = $approveds = $rejecteds = [];

        foreach ($models as $key => $model) {
            $base = $model::query()->where('created_at', '>=', $startOfMonth);

            // Aman untuk single/dual status karena pakai scope trait
            $pendings[$key] = (clone $base)->filterFinalStatus('pending')->count();
            $rejecteds[$key] = (clone $base)->filterFinalStatus('rejected')->count();
            $approveds[$key] = (clone $base)->filterFinalStatus('approved')->count();
        }

        $total_pending = array_sum($pendings);
        $total_rejected = array_sum($rejecteds);
        $total_approved = array_sum($approveds);
        // Ambil ID role Employee (hindari akses properti pada builder)
        $employeeRoleId = Role::where('name', Roles::Employee->value)->value('id');

        $total_employees = User::whereHas('roles', function ($q) use ($employeeRoleId) {
            $q->where('roles.id', $employeeRoleId);
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
            $reimbursementsChartData[] = Reimbursement::whereBetween('created_at', [$start, $end])->count();
            $reimbursementsRupiahChartData[] = Reimbursement::whereBetween('created_at', [$start, $end])->sum('total');
            $overtimesChartData[] = Overtime::whereBetween('created_at', [$start, $end])->count();
            $leavesChartData[] = Leave::whereBetween('created_at', [$start, $end])->count();
            $officialTravelsChartData[] = OfficialTravel::whereBetween('created_at', [$start, $end])->count();
        }

        $sisaCuti = $leaveService->sisaCuti(Auth::user());

        $recentRequests = $this->getRecentRequests(Auth::id());

        $calendarData = $dashboardLeaveCalendarService->build(Leave::query(), now()->year);
        $cutiPerTanggal = $calendarData['approved_by_date'];
        $holidayDates = $calendarData['holiday_dates'];
        $holidaysByDate = $calendarData['holidays_by_date'];

        return view('super-admin.dashboard.index', compact([
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
        ]));

    }
}
