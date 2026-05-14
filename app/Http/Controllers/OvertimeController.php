<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\Overtime;
use App\Services\OvertimeApprovalService;
use App\Services\OvertimeService;
use App\Enums\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class OvertimeController extends Controller
{
    public function __construct(
        private OvertimeService $overtimeService,
        private OvertimeApprovalService $approvalService,
    ) {}

    public function index(Request $request)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        $myQuery = Overtime::where('employee_id', $user->id)
            ->with(['employee', 'approver1', 'approver2'])
            ->orderBy('created_at', 'desc');
        $this->applyFilters($myQuery, $request);
        $myOvertimes = $myQuery->paginate(10, ['*'], 'my_page');

        $myBase = Overtime::where('employee_id', $user->id);
        $stats  = [
            'total'    => $myBase->count(),
            'pending'  => (clone $myBase)->filterFinalStatus('pending')->count(),
            'approved' => (clone $myBase)->filterFinalStatus('approved')->count(),
            'rejected' => (clone $myBase)->filterFinalStatus('rejected')->count(),
        ];

        $allOvertimes = null;
        if ($permissions['canViewAllRequests']) {
            $allQuery = Overtime::with(['employee.division', 'approver1', 'approver2'])
                ->orderBy('created_at', 'desc');

            if ($user->hasRole(Roles::Approver->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value])) {
                $allQuery->forLeader($user->id);
            } elseif ($user->hasRole(Roles::Finance->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value, Roles::Approver->value])) {
                $allQuery->filterFinalStatus('approved');
            }

            $this->applyFilters($allQuery, $request);
            $allOvertimes = $allQuery->paginate(10, ['*'], 'all_page');
        }

        return view('overtimes.index', compact('myOvertimes', 'allOvertimes', 'stats', 'permissions'));
    }

    public function create()
    {
        return view('overtimes.create');
    }

    public function store(StoreOvertimeRequest $request)
    {
        $this->overtimeService->store($request->validated(), Auth::user());
        return redirect()->route('overtimes.index')->with('success', 'Pengajuan overtime berhasil dikirim.');
    }

    public function show(Overtime $overtime)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        abort_unless(
            (int) $overtime->employee_id === (int) $user->id || $permissions['canViewAllRequests'],
            403
        );

        $overtime->load(['employee.division', 'approver1', 'approver2']);

        $canApproveStage1 = $permissions['canApproveStage1']
            && $overtime->status_1 === 'pending'
            && (int) $overtime->employee_id !== (int) $user->id;

        $canApproveStage2 = $permissions['canApproveStage2']
            && $overtime->status_1 === 'approved'
            && $overtime->status_2 === 'pending'
            && (int) $overtime->employee_id !== (int) $user->id;

        return view('overtimes.show', compact('overtime', 'canApproveStage1', 'canApproveStage2', 'permissions'));
    }

    public function edit(Overtime $overtime)
    {
        $this->authorizeOwner($overtime);
        abort_unless($overtime->status_1 === 'pending', 403, 'Hanya overtime pending yang bisa diedit.');

        return view('overtimes.edit', compact('overtime'));
    }

    public function update(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        $this->authorizeOwner($overtime);
        abort_unless($overtime->status_1 === 'pending', 403);

        $this->overtimeService->update($request->validated(), $overtime);
        return redirect()->route('overtimes.show', $overtime)->with('success', 'Overtime berhasil diperbarui.');
    }

    public function destroy(Overtime $overtime)
    {
        $user    = Auth::user();
        $isOwner = (int) $overtime->employee_id === (int) $user->id;
        $canAny  = $user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value]);

        abort_unless($isOwner || $canAny, 403);
        abort_unless($overtime->status_1 === 'pending' || $canAny, 403);

        $overtime->delete();
        return redirect()->route('overtimes.index')->with('success', 'Overtime berhasil dihapus.');
    }

    public function approve(Request $request, Overtime $overtime)
    {
        $user = Auth::user();
        $request->validate(['note' => 'nullable|string|max:100', 'status' => 'required|in:approved,rejected']);

        if ($user->hasRole(Roles::Approver->value) && $overtime->status_1 === 'pending') {
            $this->approvalService->handleApproval($overtime, $request->status, $request->note, 'status_1');
        } elseif ($user->hasRole(Roles::Manager->value) && $overtime->status_1 === 'approved' && $overtime->status_2 === 'pending') {
            $this->approvalService->handleApproval($overtime, $request->status, $request->note, 'status_2');
        } else {
            abort(403, 'Tidak berwenang atau status tidak sesuai.');
        }

        $msg = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        return redirect()->back()->with('success', "Overtime berhasil {$msg}.");
    }

    public function reject(Request $request, Overtime $overtime)
    {
        return $this->approve($request->merge(['status' => 'rejected']), $overtime);
    }

    public function markedDone(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canMarkPayment(), 403);
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:overtimes,id']);

        Overtime::whereIn('id', $request->ids)->update(['marked_down' => true]);
        return redirect()->back()->with('success', count($request->ids) . ' overtime ditandai lunas.');
    }

    public function exportPdf(Overtime $overtime)
    {
        $user = Auth::user();
        abort_unless(
            (int) $overtime->employee_id === (int) $user->id || $user->permissions()->canViewAllRequests(),
            403
        );

        $overtime->load(['employee.division', 'approver1', 'approver2']);
        $pdf = Pdf::loadView('components.pdf.overtime', compact('overtime'));

        return $pdf->download('overtime-' . $overtime->id . '.pdf');
    }

    public function export(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canExport(), 403);

        return Excel::download(
            new \App\Exports\OvertimesExport($request->all()),
            'overtimes-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function authorizeOwner(Overtime $overtime): void
    {
        abort_unless((int) $overtime->employee_id === (int) Auth::id(), 403);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->filterFinalStatus($request->status);
        }
        if ($request->filled('from_date')) {
            $query->where('date_start', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date_end', '<=', $request->to_date);
        }
    }
}
