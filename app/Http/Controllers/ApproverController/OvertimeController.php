<?php

namespace App\Http\Controllers\ApproverController;

use App\Events\OvertimeLevelAdvanced;
use App\Exports\OvertimesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveOvertimeRequest;
use App\Http\Requests\StoreOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\ApprovalLink;
use App\Models\Overtime;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Services\OvertimeApprovalService;
use App\Services\OvertimeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService, private OvertimeApprovalService $overtimeApprovalService)
    {
    }

    public function index(Request $request)
    {
        // Query for user's own requests (all statuses)
        $ownRequestsQuery = Overtime::with(['employee', 'approver1','approver2'])
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Query for all users' requests (excluding own unless approved)
        $allUsersQuery = Overtime::with(['employee', 'approver1','approver2'])->forLeader(Auth::id())
            ->where(function ($q) {
                $q->where('employee_id', '!=', Auth::id())
                    ->orWhere(function ($subQ) {
                        $subQ->where('employee_id', Auth::id())
                            ->where('status_2', 'approved');
                    });
            })
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $statusFilter = function ($query) use ($request) {
                switch ($request->status) {
                    case 'approved':
                        // approved = dua-duanya approved
                        $query->where('status_1', 'approved')
                            ->where('status_2', 'approved');
                        break;

                    case 'rejected':
                        // rejected = salah satu rejected
                        $query->where(function ($q) {
                            $q->where('status_1', 'rejected')
                                ->orWhere('status_2', 'rejected');
                        });
                        break;

                    case 'pending':
                        // pending = tidak ada rejected DAN (minimal salah satu pending)
                        $query->where(function ($q) {
                            $q->where(function ($qq) {
                                $qq->where('status_1', 'pending')
                                    ->orWhere('status_2', 'pending');
                            })->where(function ($qq) {
                                $qq->where('status_1', '!=', 'rejected')
                                    ->where('status_2', '!=', 'rejected');
                            });
                        });
                        break;

                    default:
                        // nilai status tak dikenal: biarkan tanpa filter atau lempar 422
                        // optional: $query->whereRaw('1=0');
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

        $totalRequests = Overtime::count();
        $pendingRequests = Overtime::where('status_1', 'pending')
            ->orWhere('status_2', 'pending')->count();
        $approvedRequests = Overtime::where('status_1', 'approved')
            ->where('status_2', 'approved')->count();
        $rejectedRequests = Overtime::where('status_1', 'rejected')
            ->orWhere('status_2', 'rejected')->count();

        Overtime::whereNull('seen_by_approver_at')
            ->whereHas('employee', fn($q) => $q->where('division_id', auth()->user()->division_id))
            ->update(['seen_by_approver_at' => now()]);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('approver.overtime.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));
    }


    public function show($id)
    {
        $overtime = Overtime::findOrFail($id);
        $overtime->load(['employee', 'approver']);
        return view('approver.overtime.show', compact('overtime'));
    }

    public function edit(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') {
            return redirect()->route('approver.overtimes.show', $overtime->id)
                ->with('error', 'You cannot edit an overtime request that has already been processed.');
        }

        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();
        return view('approver.overtime.update', compact('overtime', 'approvers'));
    }

    public function updateSelf(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        try {
            $this->overtimeService->update($overtime, $request->validated());
            return redirect()->route('approver.overtimes.index', $overtime->id)
                ->with('success', 'Overtime updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function create()
    {
        return view('approver.overtime.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOvertimeRequest $request)
    {
        try {
            $overtime = $this->overtimeService->store($request->validated());
            return redirect()->route('approver.overtimes.index')
                ->with('success', "Overtime submitted. Total: {$overtime->total}");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }


    public function update(ApproveOvertimeRequest $request, Overtime $overtime)
    {
        try {
            $level = auth()->user()->hasActiveRole(Roles::Manager->value) ? 'status_2' : 'status_1';

            $this->overtimeApprovalService->handleApproval(
                overtime: $overtime,
                status: $request->status_1,
                note: $request->note_1,
                level: $level
            );

            return redirect()
                ->route('approver.overtimes.index')
                ->with('success', "Overtime request {$request->status_1} successfully.");

        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
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

            $filename = 'overtime-requests-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new OvertimesExport($filters), $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Export error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }



    public function destroy(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') && !$user->hasActiveRole(Roles::Admin->value)) {
            return redirect()->route('approver.overtimes.show', $overtime->id)
                ->with('error', 'You cannot delete an overtime request that has already been processed.');
        }

        $overtime->delete();

        return redirect()->route('approver.overtimes.index')
            ->with('success', 'Overtime request deleted successfully.');
    }

    public function exportPdf(Overtime $overtime)
    {
        $pdf = Pdf::loadView('Employee.overtimes.pdf', compact('overtime'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('overtime-details.pdf');
    }
}
