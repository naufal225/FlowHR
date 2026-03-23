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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use HelperController;

    public function index()
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

        $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        $sisaCuti = $annual
            - (int) Leave::where('employee_id', Auth::id())
                ->where('status_1', 'approved')
                ->whereYear('date_start', now()->year)
                ->select(DB::raw('SUM(DATEDIFF(date_end, date_start) + 1) as total_days'))
                ->value('total_days');

        $recentRequests = $this->getRecentRequests(Auth::id());

        $cutiPerTanggal = [];

        $karyawanCuti = Leave::with(['employee:id,name,email,url_profile'])
            ->where('status_1', 'approved')
            ->where(function ($q) {
                $q->whereYear('date_start', now()->year)
                    ->orWhereYear('date_end', now()->year);
            })
            ->get(['id', 'employee_id', 'date_start', 'date_end']);

        foreach ($karyawanCuti as $cuti) {
            $start = Carbon::parse($cuti->date_start);
            $end = Carbon::parse($cuti->date_end);
            while ($start->lte($end)) {
                $tanggal = $start->format('Y-m-d');
                $cutiPerTanggal[$tanggal][] = [
                    'employee' => $cuti->employee->name,
                    'email' => $cuti->employee->email,
                    'url_profile' => $cuti->employee->url_profile,
                ];
                $start->addDay();
            }

        }
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
            'cutiPerTanggal'
        ]));

    }
}
