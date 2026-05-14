<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\Role;
use App\Models\User;
use App\Enums\Roles;
use App\Models\FeatureSetting;
use App\Services\Dashboard\DashboardAttendanceStateService;
use App\Services\Dashboard\DashboardLeaveCalendarService;
use App\Services\LeaveService;
use App\Traits\HelperController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use HelperController;

    public function index(
        DashboardLeaveCalendarService $dashboardLeaveCalendarService,
        LeaveService $leaveService
    ) {
        $user = Auth::user();
        $user->loadMissing('roles');
        $permissions = $user->permissions()->toArray();

        $featureActive = [
            'cuti'            => FeatureSetting::isActive('cuti'),
            'reimbursement'   => FeatureSetting::isActive('reimbursement'),
            'overtime'        => FeatureSetting::isActive('overtime'),
            'perjalanan_dinas'=> FeatureSetting::isActive('perjalanan_dinas'),
        ];

        $startOfMonth = Carbon::now()->startOfMonth();

        // ── Personal stats (all users) ──────────────────────────────────
        $myStats = [
            'leaves_pending'   => $featureActive['cuti']            ? Leave::where('employee_id', $user->id)->where('status_1', 'pending')->count() : 0,
            'reimb_pending'    => $featureActive['reimbursement']    ? Reimbursement::where('employee_id', $user->id)->filterFinalStatus('pending')->count() : 0,
            'overtime_pending' => $featureActive['overtime']         ? Overtime::where('employee_id', $user->id)->filterFinalStatus('pending')->count() : 0,
            'travel_pending'   => $featureActive['perjalanan_dinas'] ? OfficialTravel::where('employee_id', $user->id)->filterFinalStatus('pending')->count() : 0,
        ];
        $myStats['total_pending']  = array_sum($myStats);
        $myStats['total_approved'] = 0;
        $myStats['total_rejected'] = 0;

        if ($featureActive['cuti']) {
            $myStats['total_approved'] += Leave::where('employee_id', $user->id)->where('created_at', '>=', $startOfMonth)->where('status_1', 'approved')->count();
            $myStats['total_rejected'] += Leave::where('employee_id', $user->id)->where('created_at', '>=', $startOfMonth)->where('status_1', 'rejected')->count();
        }

        $myStats['sisa_cuti'] = $featureActive['cuti'] ? $leaveService->sisaCuti($user) : 0;

        // ── Org-wide stats (admin / manager / approver) ─────────────────
        $orgStats = null;
        if ($permissions['canViewAllRequests']) {
            $models = [
                'reimbursements'  => ['model' => Reimbursement::class,   'feature' => 'reimbursement'],
                'overtimes'       => ['model' => Overtime::class,         'feature' => 'overtime'],
                'leaves'          => ['model' => Leave::class,            'feature' => 'cuti'],
                'official_travels'=> ['model' => OfficialTravel::class,   'feature' => 'perjalanan_dinas'],
            ];

            $orgPending = $orgApproved = $orgRejected = 0;
            foreach ($models as $cfg) {
                if (!$featureActive[$cfg['feature']]) continue;
                $base = $cfg['model']::query()->where('created_at', '>=', $startOfMonth);
                $orgPending   += (clone $base)->filterFinalStatus('pending')->count();
                $orgApproved  += (clone $base)->filterFinalStatus('approved')->count();
                $orgRejected  += (clone $base)->filterFinalStatus('rejected')->count();
            }

            $employeeRole   = Role::where('name', Roles::Employee->value)->first();
            $totalEmployees = $employeeRole
                ? User::whereHas('roles', fn($q) => $q->where('roles.id', $employeeRole->id))->count()
                : 0;

            $orgStats = compact('orgPending', 'orgApproved', 'orgRejected', 'totalEmployees');
        }

        // ── Recent requests ─────────────────────────────────────────────
        $recentRequests = $this->getRecentRequests($user->id);

        // ── Leave calendar ───────────────────────────────────────────────
        $cutiPerTanggal = $holidayDates = $holidaysByDate = [];
        if ($featureActive['cuti']) {
            $calendarData   = $dashboardLeaveCalendarService->build(Leave::query(), now()->year);
            $cutiPerTanggal = $calendarData['approved_by_date'];
            $holidayDates   = $calendarData['holiday_dates'];
            $holidaysByDate = $calendarData['holidays_by_date'];
        }

        // ── Dashboard attendance state ───────────────────────────────────
        $dashboardAttendanceState = null;
        // Reuse existing service if available

        if (class_exists(DashboardAttendanceStateService::class)) {
            $dashboardAttendanceState = app(DashboardAttendanceStateService::class)->forUser($user);
        }

        return view('dashboard.index', compact(
            'permissions',
            'featureActive',
            'myStats',
            'orgStats',
            'recentRequests',
            'cutiPerTanggal',
            'holidayDates',
            'holidaysByDate',
            'dashboardAttendanceState'
        ));
    }
}
