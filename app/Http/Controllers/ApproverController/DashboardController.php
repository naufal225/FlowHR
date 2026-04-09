<?php

namespace App\Http\Controllers\ApproverController;

use App\Http\Controllers\Controller;
use App\Traits\HelperController;
use App\Models\Leave;
use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Division;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Models\FeatureSetting;
use App\Services\Dashboard\DashboardLeaveCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use HelperController;

    public function index(DashboardLeaveCalendarService $dashboardLeaveCalendarService)
    {
        $approverId = (int) Auth::id();
        $divisionIds = $this->resolveApproverDivisionIds($approverId);

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
            $base = $model::query()
                ->forLeader($approverId)
                ->where('created_at', '>=', $startOfMonth);

            $pendings[$key] = (clone $base)->filterFinalStatus('pending')->count();
            $rejecteds[$key] = (clone $base)->filterFinalStatus('rejected')->count();
            $approveds[$key] = (clone $base)->filterFinalStatus('approved')->count();
        }

        $total_pending = array_sum($pendings);
        $total_rejected = array_sum($rejecteds);
        $total_approved = array_sum($approveds);

        $employeeRole = Role::where('name', Roles::Employee->value)->first();

        $total_employees = User::query()
            ->whereIn('division_id', $divisionIds)
            ->where('id', '!=', $approverId)
            ->whereHas('roles', function ($q) use ($employeeRole) {
                $q->where('roles.id', $employeeRole->id);
            })->count();

        // Generate chart data per bulan dengan filter forLeader
        $reimbursementsChartData = $overtimesChartData = $leavesChartData = $officialTravelsChartData = $reimbursementsRupiahChartData = [];
        $months = [];

        $year = now()->year;

        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::create($year, $i, 1);
            $monthName = $date->translatedFormat('F');
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $months[] = $monthName;

            $reimbursementsChartData[] = $featureActive['reimbursement'] ? Reimbursement::forLeader($approverId)->whereBetween('created_at', [$start, $end])->count() : 0;
            $reimbursementsRupiahChartData[] = $featureActive['reimbursement'] ? Reimbursement::forLeader($approverId)->whereBetween('created_at', [$start, $end])->sum('total') : 0;
            $overtimesChartData[] = $featureActive['overtime'] ? Overtime::forLeader($approverId)->whereBetween('created_at', [$start, $end])->count() : 0;
            $leavesChartData[] = $featureActive['cuti'] ? Leave::forLeader($approverId)->whereBetween('created_at', [$start, $end])->count() : 0;
            $officialTravelsChartData[] = $featureActive['perjalanan_dinas'] ? OfficialTravel::forLeader($approverId)->whereBetween('created_at', [$start, $end])->count() : 0;
        }

        // Sisa cuti untuk approver (jika needed)
        $sisaCuti = 0; // Approver mungkin tidak perlu sisa cuti, tapi bisa diisi jika diperlukan

        // Recent requests dengan filter forLeader
        $recentRequests = $this->getRecentRequestsForLeader($approverId);
        // Filter recent requests by active features
        $recentRequests = $recentRequests->filter(function ($item) use ($featureActive) {
            return ($item['type'] === \App\Enums\TypeRequest::Leaves->value && $featureActive['cuti'])
                || ($item['type'] === \App\Enums\TypeRequest::Reimbursements->value && $featureActive['reimbursement'])
                || ($item['type'] === \App\Enums\TypeRequest::Overtimes->value && $featureActive['overtime'])
                || ($item['type'] === \App\Enums\TypeRequest::Travels->value && $featureActive['perjalanan_dinas']);
        })->values();

        // Data cuti per tanggal untuk kalender (hanya dari divisi leader)
        $cutiPerTanggal = [];
        $holidayDates = [];
        $holidaysByDate = [];
        if ($featureActive['cuti']) {
            $calendarData = $dashboardLeaveCalendarService->build(
                Leave::query()->forLeader($approverId),
                now()->year,
            );
            $cutiPerTanggal = $calendarData['approved_by_date'];
            $holidayDates = $calendarData['holiday_dates'];
            $holidaysByDate = $calendarData['holidays_by_date'];
        }

        return view('approver.dashboard.index', compact([
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

    // Method khusus untuk mendapatkan recent requests dengan filter leader
    private function getRecentRequestsForLeader($leaderId)
    {
        // Implementasi similar to HelperController but with forLeader scope
        $recentLeaves = Leave::forLeader($leaderId)
            ->with(['employee:id,name,division_id,url_profile', 'employee.division:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => \App\Enums\TypeRequest::Leaves->value,
                    'title' => 'Leave Request',
                    'employee_name' => $item->employee->name,
                    'division_name' => $item->employee->division->name,
                    'url_profile' => $item->employee->url_profile,
                    'date' => $item->created_at->format('M d, Y'),
                    'created_at' => $item->created_at,
                    'status_1' => $item->status_1,
                    'status_2' => $item->status_2,
                    'url' => route('approver.leaves.show', $item->id)
                ];
            });

        $recentReimbursements = Reimbursement::forLeader($leaderId)
            ->with(['employee:id,name,division_id,url_profile', 'employee.division:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => \App\Enums\TypeRequest::Reimbursements->value,
                    'title' => 'Reimbursement Request',
                    'employee_name' => $item->employee->name,
                    'division_name' => $item->employee->division->name,
                    'url_profile' => $item->employee->url_profile,
                    'date' => $item->created_at->format('M d, Y'),
                    'created_at' => $item->created_at,
                    'status_1' => $item->status_1,
                    'status_2' => $item->status_2,
                    'url' => route('approver.reimbursements.show', $item->id)
                ];
            });

        $recentOvertimes = Overtime::forLeader($leaderId)
            ->with(['employee:id,name,division_id,url_profile', 'employee.division:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => \App\Enums\TypeRequest::Overtimes->value,
                    'title' => 'Overtime Request',
                    'employee_name' => $item->employee->name,
                    'division_name' => $item->employee->division->name,
                    'url_profile' => $item->employee->url_profile,
                    'date' => $item->created_at->format('M d, Y'),
                    'created_at' => $item->created_at,
                    'status_1' => $item->status_1,
                    'status_2' => $item->status_2,
                    'url' => route('approver.overtimes.show', $item->id)
                ];
            });

        $recentTravels = OfficialTravel::forLeader($leaderId)
            ->with(['employee:id,name,division_id,url_profile', 'employee.division:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($item) {
                return [
                    'type' => \App\Enums\TypeRequest::Travels->value,
                    'title' => 'Official Travel Request',
                    'employee_name' => $item->employee->name,
                    'division_name' => $item->employee->division->name,
                    'url_profile' => $item->employee->url_profile,
                    'date' => $item->created_at->format('M d, Y'),
                    'created_at' => $item->created_at,
                    'status_1' => $item->status_1,
                    'status_2' => $item->status_2,
                    'url' => route('approver.official-travels.show', $item->id)
                ];
            });

        return $recentLeaves->concat($recentReimbursements)
            ->concat($recentOvertimes)
            ->concat($recentTravels)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();
    }

    private function resolveApproverDivisionIds(int $approverId): array
    {
        $divisionIds = Division::query()
            ->where('leader_id', $approverId)
            ->pluck('id');

        $memberDivisionId = User::query()
            ->whereKey($approverId)
            ->value('division_id');

        if ($memberDivisionId !== null) {
            $divisionIds->push((int) $memberDivisionId);
        }

        return $divisionIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
