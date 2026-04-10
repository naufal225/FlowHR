<?php

namespace App\Http\Controllers\ApproverController;

use App\Events\LeaveLevelAdvanced;
use App\Exports\LeavesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Models\ApprovalLink;
use App\Models\Leave;
use App\Models\User;
use App\Models\Role;
use App\Services\HolidayDateService;
use App\Services\LeaveService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class LeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
        private HolidayDateService $holidayDateService,
    )
    {
    }
    public function index(Request $request)
    {
        $approverId = (int) Auth::id();

        // Query for user's own requests (all statuses)
        $ownRequestsQuery = Leave::with(['employee', 'approver1'])
            ->where('employee_id', $approverId)
            ->orderBy('created_at', 'desc');

        // Query for all requests inside approver division scope.
        $allUsersQuery = Leave::with(['employee', 'approver1'])
            ->forLeader($approverId)
            ->where('employee_id', '!=', $approverId)
            ->orderBy('created_at', 'desc');

        // Apply filters to both queries
        if ($request->filled('status')) {
            $statusFilter = function ($query) use ($request) {
                switch ($request->status) {
                    case 'approved':
                        $query->where('status_1', 'approved');
                        break;
                    case 'rejected':
                        $query->where('status_1', 'rejected');
                        break;
                    case 'pending':
                        $query->where('status_1', 'pending');
                        break;
                }
            };

            $ownRequestsQuery->where($statusFilter);
            $allUsersQuery->where($statusFilter);
        }

        if ($request->filled('from_date')) {
            $fromDate = Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta');
            $ownRequestsQuery->where('created_at', '>=', $fromDate);
            $allUsersQuery->where('created_at', '>=', $fromDate);
        }

        if ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta');
            $ownRequestsQuery->where('created_at', '<=', $toDate);
            $allUsersQuery->where('created_at', '<=', $toDate);
        }

        $ownRequests = $ownRequestsQuery->paginate(10, ['*'], 'own_page');
        $allUsersRequests = $allUsersQuery->paginate(10, ['*'], 'all_page');


        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $scopedStatsQuery = Leave::query()->forLeader($approverId);
        $totalRequests = (clone $scopedStatsQuery)->count();
        $pendingRequests = (clone $scopedStatsQuery)->filterFinalStatus('pending')->count();
        $approvedRequests = (clone $scopedStatsQuery)->filterFinalStatus('approved')->count();
        $rejectedRequests = (clone $scopedStatsQuery)->filterFinalStatus('rejected')->count();

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('approver.leave-request.index', compact(
            'ownRequests',
            'allUsersRequests',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'sisaCuti',
            'manager'
        ));
    }

    public function show(Leave $leave)
    {
        $leave->load(['employee.division', 'approver']);
        $approverId = (int) Auth::id();

        $canAccess = (int) $leave->employee_id === $approverId
            || Leave::query()->whereKey($leave->id)->forLeader($approverId)->exists();

        if (! $canAccess) {
            return abort(403, 'Unauthorized');
        }

        $canApprove = false;
        return view('approver.leave-request.show', compact('leave', 'canApprove'));
    }

    public function create()
    {
        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $holidays = $this->holidayDateService->getDateStringsForForm();

        if ($sisaCuti <= 0) {
            abort(422, 'Sisa cuti tidak cukup.');
        }

        return view('approver.leave-request.create', compact('sisaCuti', 'holidays'));
    }

    public function store(StoreLeaveRequest $request)
    {
        try {
            $this->leaveService->store($request->validated());

            return redirect()->route('approver.leaves.index')
                ->with('success', 'Leave request submitted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }



    public function edit(Leave $leave)
    {
        $user = Auth::user();
        if ($user->id !== $leave->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $holidays = $this->holidayDateService->getDateStringsForForm();

        if ($leave->status_1 !== 'pending') {
            return redirect()->route('approver.leaves.show', $leave->id)
                ->with('error', 'You cannot edit a leave request that has already been processed.');
        }

        return view('approver.leave-request.update', compact('leave', 'sisaCuti', 'holidays'));
    }

    public function update(UpdateLeaveRequest $request, Leave $leave)
    {
        try {
            $this->leaveService->update($leave, $request->validated());

            return redirect()->route('approver.leaves.index')
                ->with('success', 'Leave request updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    public function destroy(Leave $leave)
    {
        // Check if the user has permission to delete this leave
        $user = Auth::user();
        if ($user->id !== $leave->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow deleting if the leave is still pending
        if (($leave->status_1 !== 'pending')) {
            return redirect()->route('approver.leaves.show', $leave->id)
                ->with('error', 'You cannot delete a leave request that has already been processed.');
        }

        $leave->delete();
        return redirect()->route('approver.leaves.index')
            ->with('success', 'Leave request deleted successfully.');
    }

    public function export(Request $request)
    {
        try {
            // (opsional) disable debugbar yang suka nyisipin output
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            // bersihkan buffer agar XLSX tidak ketimpa
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $filters = [
                'status' => $request->status,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            $filename = 'leave-requests-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new LeavesExport($filters), $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Export error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportPdf(Leave $leave)
    {
        $pdf = Pdf::loadView('Employee.leaves.pdf', compact('leave'))->setOptions(["isPhpEnabled" => true]);
        return $pdf->download('leave-details.pdf');
    }

}
