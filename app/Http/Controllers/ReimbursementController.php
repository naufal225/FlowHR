<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Services\ReimbursementApprovalService;
use App\Services\ReimbursementService;
use App\Enums\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReimbursementController extends Controller
{
    public function __construct(
        private ReimbursementService $reimbursementService,
        private ReimbursementApprovalService $approvalService,
    ) {}

    public function index(Request $request)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        // My reimbursements
        $myQuery = Reimbursement::where('employee_id', $user->id)
            ->with(['employee', 'type'])
            ->orderBy('created_at', 'desc');
        $this->applyFilters($myQuery, $request);
        $myReimbursements = $myQuery->paginate(10, ['*'], 'my_page');

        $myBase = Reimbursement::where('employee_id', $user->id);
        $stats  = [
            'total'    => $myBase->count(),
            'pending'  => (clone $myBase)->filterFinalStatus('pending')->count(),
            'approved' => (clone $myBase)->filterFinalStatus('approved')->count(),
            'rejected' => (clone $myBase)->filterFinalStatus('rejected')->count(),
        ];

        // All reimbursements – based on role
        $allReimbursements = null;
        if ($permissions['canViewAllRequests']) {
            $allQuery = Reimbursement::with(['employee.division', 'type', 'approver1', 'approver2'])
                ->orderBy('created_at', 'desc');

            if ($user->hasRole(Roles::Approver->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value])) {
                $allQuery->forLeader($user->id);
            } elseif ($user->hasRole(Roles::Finance->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value, Roles::Approver->value])) {
                $allQuery->filterFinalStatus('approved');
            }

            $this->applyFilters($allQuery, $request);
            $allReimbursements = $allQuery->paginate(10, ['*'], 'all_page');
        }

        return view('reimbursements.index', compact('myReimbursements', 'allReimbursements', 'stats', 'permissions'));
    }

    public function create()
    {
        $types = ReimbursementType::orderBy('name')->get();
        return view('reimbursements.create', compact('types'));
    }

    public function store(StoreReimbursementRequest $request)
    {
        $this->reimbursementService->store($request->validated(), Auth::user());
        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement berhasil diajukan.');
    }

    public function show(Reimbursement $reimbursement)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        abort_unless(
            (int) $reimbursement->employee_id === (int) $user->id || $permissions['canViewAllRequests'],
            403
        );

        $reimbursement->load(['employee.division', 'type', 'approver1', 'approver2']);

        $canApproveStage1 = $permissions['canApproveStage1']
            && $reimbursement->status_1 === 'pending'
            && (int) $reimbursement->employee_id !== (int) $user->id;

        $canApproveStage2 = $permissions['canApproveStage2']
            && $reimbursement->status_1 === 'approved'
            && $reimbursement->status_2 === 'pending'
            && (int) $reimbursement->employee_id !== (int) $user->id;

        return view('reimbursements.show', compact('reimbursement', 'canApproveStage1', 'canApproveStage2', 'permissions'));
    }

    public function edit(Reimbursement $reimbursement)
    {
        $this->authorizeOwner($reimbursement);
        abort_unless($reimbursement->status_1 === 'pending', 403, 'Hanya reimbursement pending yang bisa diedit.');

        $types = ReimbursementType::orderBy('name')->get();
        return view('reimbursements.edit', compact('reimbursement', 'types'));
    }

    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        $this->authorizeOwner($reimbursement);
        abort_unless($reimbursement->status_1 === 'pending', 403);

        $this->reimbursementService->update($request->validated(), $reimbursement);
        return redirect()->route('reimbursements.show', $reimbursement)->with('success', 'Reimbursement berhasil diperbarui.');
    }

    public function destroy(Reimbursement $reimbursement)
    {
        $user = Auth::user();
        $isOwner    = (int) $reimbursement->employee_id === (int) $user->id;
        $canAny     = $user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value]);

        abort_unless($isOwner || $canAny, 403);
        abort_unless($reimbursement->status_1 === 'pending' || $canAny, 403);

        $reimbursement->delete();
        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement berhasil dihapus.');
    }

    public function approve(Request $request, Reimbursement $reimbursement)
    {
        $user = Auth::user();
        $request->validate([
            'note'   => 'nullable|string|max:100',
            'status' => 'required|in:approved,rejected',
        ]);

        if ($user->hasRole(Roles::Approver->value) && $reimbursement->status_1 === 'pending') {
            $this->approvalService->handleApproval($reimbursement, $request->status, $request->note, 'status_1');
        } elseif ($user->hasRole(Roles::Manager->value) && $reimbursement->status_1 === 'approved' && $reimbursement->status_2 === 'pending') {
            $this->approvalService->handleApproval($reimbursement, $request->status, $request->note, 'status_2');
        } else {
            abort(403, 'Tidak berwenang atau status tidak sesuai.');
        }

        $msg = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        return redirect()->back()->with('success', "Reimbursement berhasil {$msg}.");
    }

    public function reject(Request $request, Reimbursement $reimbursement)
    {
        return $this->approve($request->merge(['status' => 'rejected']), $reimbursement);
    }

    public function markedDone(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canMarkPayment(), 403);

        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:reimbursements,id']);

        Reimbursement::whereIn('id', $request->ids)->update(['marked_down' => true]);

        return redirect()->back()->with('success', count($request->ids) . ' reimbursement ditandai lunas.');
    }

    public function exportPdf(Reimbursement $reimbursement)
    {
        $user = Auth::user();
        abort_unless(
            (int) $reimbursement->employee_id === (int) $user->id || $user->permissions()->canViewAllRequests(),
            403
        );

        $reimbursement->load(['employee.division', 'type', 'approver1', 'approver2']);
        $pdf = Pdf::loadView('components.pdf.reimbursement', compact('reimbursement'));

        return $pdf->download('reimbursement-' . $reimbursement->id . '.pdf');
    }

    public function export(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canExport(), 403);

        return Excel::download(
            new \App\Exports\ReimbursementsExport($request->all()),
            'reimbursements-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function authorizeOwner(Reimbursement $reimbursement): void
    {
        abort_unless((int) $reimbursement->employee_id === (int) Auth::id(), 403);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->filterFinalStatus($request->status);
        }
        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }
    }
}
