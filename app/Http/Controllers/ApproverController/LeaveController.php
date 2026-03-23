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
use App\Enums\Roles;
use App\Models\Role;
use App\Services\LeaveApprovalService;
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
    public function __construct(private LeaveService $leaveService, private LeaveApprovalService $leaveApprovalService)
    {
    }
    public function index(Request $request)
    {

        // Query for user's own requests (all statuses)
        $ownRequestsQuery = Leave::with(['employee', 'approver1'])
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Query for all users' requests (excluding own unless approved)
        $allUsersQuery = Leave::with(['employee', 'approver1'])->forLeader(Auth::id())
            // Only employees (not approver role and not division leaders)
            ->whereHas('employee', function ($q) {
                $q->whereDoesntHave('roles', fn($r) => $r->where('name', Roles::Approver->value));
            })
            ->whereHas('employee.division', function ($q) {
                $q->whereColumn('leader_id', '!=', 'leaves.employee_id');
            })
            ->where(function ($q) {
                $q->where('employee_id', '!=', Auth::id())
                    ->orWhere(function ($subQ) {
                        $subQ->where('employee_id', Auth::id())
                            ->where('status_1', 'approved');
                    });
            })
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

        $totalRequests = Leave::count();
        $pendingRequests = Leave::where('status_1', 'pending')->count();
        $approvedRequests = Leave::where('status_1', 'approved')->count();
        $rejectedRequests = Leave::where('status_1', 'rejected')->count();

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        Leave::whereNull('seen_by_approver_at')
            ->whereHas('employee', fn($q) => $q->where('division_id', auth()->user()->division_id))
            ->update(['seen_by_approver_at' => now()]);

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
        if ($leave->employee->division->leader->id !== Auth::id()) {
            return abort(403, 'Unauthorized');
        }

        $leave->load(['employee', 'approver']);
        $isLeaderApplicant = \App\Models\Division::where('leader_id', $leave->employee_id)->exists();
        $isApproverApplicant = $leave->employee->roles()->where('name', Roles::Approver->value)->exists();
        $canApprove = !$isLeaderApplicant && !$isApproverApplicant && $leave->status_1 === 'pending';
        return view('approver.leave-request.show', compact('leave', 'canApprove'));
    }

    public function create()
    {
        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $holidays = \App\Models\Holiday::pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

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

        $holidays = \App\Models\Holiday::pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

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

    public function approval(\App\Http\Requests\ApproveLeaveRequest $request, Leave $leave)
    {
        try {
            if ($request->status_1 === 'approved') {
                $this->leaveApprovalService->approve($leave, $request->note_1 ?? null);
            } else {
                $this->leaveApprovalService->reject($leave, $request->note_1 ?? null);
            }
            return redirect()->route('approver.leaves.index')->with('success', 'Leave request '.$request->status_1.' successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
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
