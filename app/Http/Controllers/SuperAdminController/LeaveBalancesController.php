<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Leave;
use App\Models\User;
use App\Services\LeaveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaveBalancesController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $divisionId = $request->get('division_id');
        $totalCutiTahunan = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));

        $divisions = Division::orderBy('name')->get();

        $employees = User::query()
            ->with('division')
            ->when($divisionId, fn($q) => $q->where('division_id', $divisionId))
            ->orderBy('name')
            ->get();

        $leaveService = app(LeaveService::class);

        // Hitung data agregat dengan memperhitungkan hari libur dan akhir pekan
        $leaveBalances = $employees->map(function ($emp) use ($leaveService, $totalCutiTahunan, $year) {
            $sisa = $leaveService->sisaCutiForYear($emp, (int) $year);
            $used = max(0, $totalCutiTahunan - $sisa);
            $percentage = $totalCutiTahunan > 0 ? ($used / $totalCutiTahunan) * 100 : 0;
            return [
                'employee' => $emp,
                'total_cuti' => $totalCutiTahunan,
                'used_cuti' => $used,
                'sisa_cuti' => $sisa,
                'percentage' => round($percentage, 1),
            ];
        });

        $totalEmployees = $leaveBalances->count();
        $avgUsed = $totalEmployees > 0 ? round($leaveBalances->sum('used_cuti') / $totalEmployees, 1) : 0;
        $avgRemain = $totalEmployees > 0 ? round($leaveBalances->sum('sisa_cuti') / $totalEmployees, 1) : 0;

        return view('super-admin.leave-balances.index', compact(
            'leaveBalances',
            'divisions',
            'year',
            'divisionId',
            'totalEmployees',
            'avgUsed',
            'avgRemain',
            'totalCutiTahunan'
        ));
    }


    public function exportLeaveBalances(Request $request)
    {
        $year = $request->get('year', now()->year);
        $divisionId = $request->get('division_id');

        // Get all divisions for filter
        $divisions = Division::all();

        // Get employees with their leave balances
        $employeesQuery = User::with(['division'])
            ->orderBy('name');

        // Apply division filter if selected
        if ($divisionId) {
            $employeesQuery->where('division_id', $divisionId);
        }

        $employees = $employeesQuery->get();

        // Calculate leave balances for each employee
        $leaveBalances = [];
        $totalCutiTahunan = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));

        foreach ($employees as $employee) {
            $sisaCuti = app(LeaveService::class)->sisaCutiForYear($employee, (int) $year);
            $usedLeaves = max(0, $totalCutiTahunan - $sisaCuti);

            $leaveBalances[] = [
                'employee' => $employee,
                'total_cuti' => $totalCutiTahunan,
                'used_cuti' => $usedLeaves,
                'sisa_cuti' => $sisaCuti,
                'percentage' => $totalCutiTahunan > 0 ? ($usedLeaves / $totalCutiTahunan) * 100 : 0
            ];
        }

        // Get division name for filename
        $divisionName = 'All_Divisions';
        if ($divisionId) {
            $division = Division::find($divisionId);
            $divisionName = $division ? str_replace(' ', '_', $division->name) : 'Unknown_Division';
        }

        // Generate filename
        $filename = "Leave_Balances_{$year}_{$divisionName}.pdf";

        // Generate PDF
        $pdf = Pdf::loadView('super-admin.leave-balances.export', compact('leaveBalances', 'year', 'divisionId', 'divisions'))
            ->setOptions(['isPhpEnabled' => true])
            ->setPaper('A4', 'landscape');

        return $pdf->download($filename);
    }
}
