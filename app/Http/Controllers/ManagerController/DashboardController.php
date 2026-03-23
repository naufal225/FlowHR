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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use HelperController;
    public function index()
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
            $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
            $sisaCuti = $annual
                - (int) Leave::where('employee_id', Auth::id())
                    ->where('status_1', 'approved')
                    ->whereYear('date_start', now()->year)
                    ->select(DB::raw('SUM(DATEDIFF(date_end, date_start) + 1) as total_days'))
                    ->value('total_days');
        }

        $recentRequests = $this->getRecentRequests(Auth::id());

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
            'featureActive'
        ]));

    }

}
