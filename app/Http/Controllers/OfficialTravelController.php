<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficialTravelRequest;
use App\Http\Requests\UpdateOfficialTravelRequest;
use App\Models\OfficialTravel;
use App\Services\OfficialTravelApprovalService;
use App\Services\OfficialTravelService;
use App\Enums\Roles;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class OfficialTravelController extends Controller
{
    public function __construct(
        private OfficialTravelService $travelService,
        private OfficialTravelApprovalService $approvalService,
    ) {}

    public function index(Request $request)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        $myQuery = OfficialTravel::where('employee_id', $user->id)
            ->with(['employee', 'approver1', 'approver2'])
            ->orderBy('created_at', 'desc');
        $this->applyFilters($myQuery, $request);
        $myTravels = $myQuery->paginate(10, ['*'], 'my_page');

        $myBase = OfficialTravel::where('employee_id', $user->id);
        $stats  = [
            'total'    => $myBase->count(),
            'pending'  => (clone $myBase)->filterFinalStatus('pending')->count(),
            'approved' => (clone $myBase)->filterFinalStatus('approved')->count(),
            'rejected' => (clone $myBase)->filterFinalStatus('rejected')->count(),
        ];

        $allTravels = null;
        if ($permissions['canViewAllRequests']) {
            $allQuery = OfficialTravel::with(['employee.division', 'approver1', 'approver2'])
                ->orderBy('created_at', 'desc');

            if ($user->hasRole(Roles::Approver->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value])) {
                $allQuery->forLeader($user->id);
            } elseif ($user->hasRole(Roles::Finance->value) && !$user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value, Roles::Manager->value, Roles::Approver->value])) {
                $allQuery->filterFinalStatus('approved');
            }

            $this->applyFilters($allQuery, $request);
            $allTravels = $allQuery->paginate(10, ['*'], 'all_page');
        }

        return view('official-travels.index', compact('myTravels', 'allTravels', 'stats', 'permissions'));
    }

    public function create()
    {
        return view('official-travels.create');
    }

    public function store(StoreOfficialTravelRequest $request)
    {
        $this->travelService->store($request->validated(), Auth::user());
        return redirect()->route('official-travels.index')->with('success', 'Pengajuan perjalanan dinas berhasil dikirim.');
    }

    public function show(OfficialTravel $officialTravel)
    {
        $user        = Auth::user();
        $permissions = $user->permissions()->toArray();

        abort_unless(
            (int) $officialTravel->employee_id === (int) $user->id || $permissions['canViewAllRequests'],
            403
        );

        $officialTravel->load(['employee.division', 'approver1', 'approver2']);

        $canApproveStage1 = $permissions['canApproveStage1']
            && $officialTravel->status_1 === 'pending'
            && (int) $officialTravel->employee_id !== (int) $user->id;

        $canApproveStage2 = $permissions['canApproveStage2']
            && $officialTravel->status_1 === 'approved'
            && $officialTravel->status_2 === 'pending'
            && (int) $officialTravel->employee_id !== (int) $user->id;

        return view('official-travels.show', compact('officialTravel', 'canApproveStage1', 'canApproveStage2', 'permissions'));
    }

    public function edit(OfficialTravel $officialTravel)
    {
        $this->authorizeOwner($officialTravel);
        abort_unless($officialTravel->status_1 === 'pending' && $officialTravel->status_2 === 'pending', 403);

        return view('official-travels.edit', compact('officialTravel'));
    }

    public function update(UpdateOfficialTravelRequest $request, OfficialTravel $officialTravel)
    {
        $this->authorizeOwner($officialTravel);
        abort_unless($officialTravel->status_1 === 'pending', 403);

        $this->travelService->update($request->validated(), $officialTravel);
        return redirect()->route('official-travels.show', $officialTravel)->with('success', 'Perjalanan dinas berhasil diperbarui.');
    }

    public function destroy(OfficialTravel $officialTravel)
    {
        $user    = Auth::user();
        $isOwner = (int) $officialTravel->employee_id === (int) $user->id;
        $canAny  = $user->hasRole([Roles::Admin->value, Roles::SuperAdmin->value]);

        abort_unless($isOwner || $canAny, 403);
        abort_unless($officialTravel->status_1 === 'pending' || $canAny, 403);

        $officialTravel->delete();
        return redirect()->route('official-travels.index')->with('success', 'Perjalanan dinas berhasil dihapus.');
    }

    public function approve(Request $request, OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        $request->validate(['note' => 'nullable|string|max:100', 'status' => 'required|in:approved,rejected']);

        if ($user->hasRole(Roles::Approver->value) && $officialTravel->status_1 === 'pending') {
            $this->approvalService->handleApproval($officialTravel, $request->status, $request->note, 'status_1');
        } elseif ($user->hasRole(Roles::Manager->value) && $officialTravel->status_1 === 'approved' && $officialTravel->status_2 === 'pending') {
            $this->approvalService->handleApproval($officialTravel, $request->status, $request->note, 'status_2');
        } else {
            abort(403, 'Tidak berwenang atau status tidak sesuai.');
        }

        $msg = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        return redirect()->back()->with('success', "Perjalanan dinas berhasil {$msg}.");
    }

    public function reject(Request $request, OfficialTravel $officialTravel)
    {
        return $this->approve($request->merge(['status' => 'rejected']), $officialTravel);
    }

    public function markedDone(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canMarkPayment(), 403);
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:official_travels,id']);

        OfficialTravel::whereIn('id', $request->ids)->update(['marked_down' => true]);
        return redirect()->back()->with('success', count($request->ids) . ' perjalanan dinas ditandai lunas.');
    }

    public function exportPdf(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        abort_unless(
            (int) $officialTravel->employee_id === (int) $user->id || $user->permissions()->canViewAllRequests(),
            403
        );

        $officialTravel->load(['employee.division', 'approver1', 'approver2']);
        $pdf = Pdf::loadView('components.pdf.official-travel', compact('officialTravel'));

        return $pdf->download('official-travel-' . $officialTravel->id . '.pdf');
    }

    public function export(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canExport(), 403);

        return Excel::download(
            new \App\Exports\OfficialTravelsExport($request->all()),
            'official-travels-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function bulkExport(Request $request)
    {
        abort_unless(Auth::user()->permissions()->canBulkExport(), 403);

        $travels = OfficialTravel::filterFinalStatus('approved')->with('employee')->get();

        $zipPath = storage_path('app/temp/travels-bulk-' . now()->timestamp . '.zip');
        $zip     = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);

        foreach ($travels as $officialTravel) {
            $pdf      = Pdf::loadView('components.pdf.official-travel', compact('officialTravel'));
            $zip->addFromString('travel-' . $officialTravel->id . '.pdf', $pdf->output());
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function authorizeOwner(OfficialTravel $travel): void
    {
        abort_unless((int) $travel->employee_id === (int) Auth::id(), 403);
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
