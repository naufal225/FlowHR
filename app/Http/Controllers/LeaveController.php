<?php

namespace App\Http\Controllers;

use App\Events\LeaveSubmitted;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Models\Leave;
use App\Models\FeatureSetting;
use App\Services\HolidayDateService;
use App\Services\LeaveApprovalService;
use App\Services\LeaveService;
use App\Enums\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
        private LeaveApprovalService $leaveApprovalService,
        private HolidayDateService $holidayDateService,
    ) {}

    public function index(Request $request)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        // My leaves – always
        $myQuery = Leave::where('employee_id', $user->id)
            ->with(['employee', 'approver1'])
            ->orderBy('created_at', 'desc');

        $this->applyLeaveFilters($myQuery, $request);

        $myLeaves = $myQuery->paginate(10, ['*'], 'my_page');

        // My stats
        $myLeaveBase = Leave::where('employee_id', $user->id);
        $stats = [
            'total'    => $myLeaveBase->count(),
            'pending'  => (clone $myLeaveBase)->where('status_1', 'pending')->count(),
            'approved' => (clone $myLeaveBase)->where('status_1', 'approved')->count(),
            'rejected' => (clone $myLeaveBase)->where('status_1', 'rejected')->count(),
            'sisa_cuti'=> $this->leaveService->sisaCuti($user),
        ];

        // All leaves – based on role
        $allLeaves = null;
        if ($permissions['canViewAllRequests']) {
            $allQuery = Leave::with(['employee.division', 'approver1'])
                ->orderBy('created_at', 'desc');

            if ($user->hasRole(Roles::Approver->value)) {
                $allQuery->forLeader($user->id);
            } elseif ($user->hasRole(Roles::Finance->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value])) {
                $allQuery->where('status_1', 'approved');
            }

            $this->applyLeaveFilters($allQuery, $request);
            $allLeaves = $allQuery->paginate(10, ['*'], 'all_page');
        }

        return view('leaves.index', compact('myLeaves', 'allLeaves', 'stats', 'permissions'));
    }

    public function create()
    {
        $user          = Auth::user();
        $holidayDates  = $this->holidayDateService->getHolidayDates(now()->year);
        $sisaCuti      = $this->leaveService->sisaCuti($user);

        return view('leaves.create', compact('holidayDates', 'sisaCuti'));
    }

    public function store(StoreLeaveRequest $request)
    {
        $leave = $this->leaveService->store($request->validated(), Auth::user());
        event(new LeaveSubmitted($leave, Auth::id()));

        return redirect()->route('leaves.index')->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function show(Leave $leave)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        // Access check: own leave or can view all
        if ((int) $leave->employee_id !== (int) $user->id && !$permissions['canViewAllRequests']) {
            abort(403);
        }

        $leave->load(['employee.division', 'approver1']);

        $canApprove = $permissions['canApproveLeave']
            && $leave->status_1 === 'pending'
            && (int) $leave->employee_id !== (int) $user->id;

        return view('leaves.show', compact('leave', 'canApprove', 'permissions'));
    }

    public function edit(Leave $leave)
    {
        $this->authorizeOwner($leave);
        abort_unless($leave->status_1 === 'pending', 403, 'Hanya cuti yang masih pending yang bisa diedit.');

        $holidayDates = $this->holidayDateService->getHolidayDates(now()->year);

        return view('leaves.edit', compact('leave', 'holidayDates'));
    }

    public function update(UpdateLeaveRequest $request, Leave $leave)
    {
        $this->authorizeOwner($leave);
        abort_unless($leave->status_1 === 'pending', 403, 'Hanya cuti yang masih pending yang bisa diubah.');

        $this->leaveService->update($request->validated(), $leave);

        return redirect()->route('leaves.show', $leave)->with('success', 'Cuti berhasil diperbarui.');
    }

    public function destroy(Leave $leave)
    {
        $user = Auth::user();
        $isOwner = (int) $leave->employee_id === (int) $user->id;
        $canDeleteAny = $user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value]);

        abort_unless($isOwner || $canDeleteAny, 403);
        abort_unless($leave->status_1 === 'pending' || $canDeleteAny, 403, 'Hanya cuti pending yang bisa dihapus.');

        $leave->delete();

        return redirect()->route('leaves.index')->with('success', 'Cuti berhasil dihapus.');
    }

    public function approve(Request $request, Leave $leave)
    {
        abort_unless(Auth::user()->hasRole(Roles::Manager->value), 403);

        $request->validate(['note_1' => 'nullable|string|max:100']);
        $this->leaveApprovalService->approve($leave, $request->note_1);

        return redirect()->back()->with('success', 'Cuti berhasil disetujui.');
    }

    public function reject(Request $request, Leave $leave)
    {
        abort_unless(Auth::user()->hasRole(Roles::Manager->value), 403);

        $request->validate(['note_1' => 'nullable|string|max:100']);
        $this->leaveApprovalService->reject($leave, $request->note_1);

        return redirect()->back()->with('success', 'Cuti berhasil ditolak.');
    }

    public function exportPdf(Leave $leave)
    {
        $user = Auth::user();
        abort_unless(
            (int) $leave->employee_id === (int) $user->id || $user->permissions()->canViewAllRequests(),
            403
        );

        $leave->load(['employee.division', 'approver1']);
        $pdf = Pdf::loadView('components.pdf.leave', compact('leave'));

        return $pdf->download('leave-' . $leave->id . '.pdf');
    }

    public function export(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canExport(), 403);

        return Excel::download(
            new \App\Exports\LeavesExport($request->all()),
            'leaves-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function bulkExport(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canBulkExport(), 403);

        // Reuse finance bulk export logic
        $leaves = Leave::where('status_1', 'approved')
            ->with('employee')
            ->get();

        $zipPath = storage_path('app/temp/leaves-bulk-' . now()->timestamp . '.zip');
        $zip     = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($leaves as $leave) {
            $pdf      = Pdf::loadView('components.pdf.leave', compact('leave'));
            $filename = 'leave-' . $leave->id . '.pdf';
            $zip->addFromString($filename, $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function authorizeOwner(Leave $leave): void
    {
        abort_unless((int) $leave->employee_id === (int) Auth::id(), 403);
    }

    private function applyLeaveFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $status = $request->status;
            $query->where(function ($q) use ($status) {
                if ($status === 'rejected') {
                    $q->where('status_1', 'rejected');
                } elseif ($status === 'approved') {
                    $q->where('status_1', 'approved');
                } elseif ($status === 'pending') {
                    $q->where('status_1', 'pending');
                }
            });
        }

        if ($request->filled('from_date')) {
            $query->where('date_start', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date_end', '<=', $request->to_date);
        }
    }
}
