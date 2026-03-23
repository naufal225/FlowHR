<?php

namespace App\Http\Controllers\ManagerController;

use App\Enums\Roles as EnumsRoles;
use App\Exports\OvertimesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveReimbursementRequest;
use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\ApprovalLink;
use App\Models\Reimbursement;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Services\ReimbursementApprovalService;
use App\Services\ReimbursementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $reimbursementService, private ReimbursementApprovalService $reimbursementApprovalService)
    {
    }
    public function index(Request $request)
    {
        // Query for user's own requests (all statuses)
        $ownRequestsQuery = Reimbursement::with(['employee', 'approver1','approver2'])
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Query for all users' requests (excluding own unless approved)
        $allUsersQuery = Reimbursement::with(['employee', 'approver1','approver2'])
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
            $ownRequestsQuery->where('date', '>=', $fromDate);
            $allUsersQuery->where('date', '>=', $fromDate);
        }

        if ($request->filled('to_date')) {
            $toDate = Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta');
            $ownRequestsQuery->where('date', '<=', $toDate);
            $allUsersQuery->where('date', '<=', $toDate);
        }

        $ownRequests = $ownRequestsQuery->paginate(10, ['*'], 'own_page');
        $allUsersRequests = $allUsersQuery->paginate(10, ['*'], 'all_page');


        $totalRequests = Reimbursement::count();
        $pendingRequests = Reimbursement::where('status_1', 'pending')
            ->orWhere('status_2', 'pending')->count();
        $approvedRequests = Reimbursement::where('status_1', 'approved')
            ->where('status_2', 'approved')->count();
        $rejectedRequests = Reimbursement::where('status_1', 'rejected')
            ->orWhere('status_2', 'rejected')->count();

        Reimbursement::whereNull('seen_by_manager_at')
            // ->whereHas('employee', fn($q) => $q->where('division_id', auth()->user()->division_id))
            ->update(['seen_by_manager_at' => now()]);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('manager.reimbursement.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));

    }
    public function show($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $reimbursement->load(['approver']);

        return view('manager.reimbursement.show', compact('reimbursement'));
    }

    public function create()
    {
        $types = \App\Models\ReimbursementType::all();
        return view('manager.reimbursement.create', compact('types'));
    }

    public function store(StoreReimbursementRequest $request)
    {
        try {
            $this->reimbursementService->store($request->validated());

            return redirect()->route('manager.reimbursements.index')
                ->with('success', 'Reimbursement request submitted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function update(ApproveReimbursementRequest $request, Reimbursement $reimbursement)
    {
        try {
            // bedakan level status berdasarkan role
            $level = auth()->user()->hasActiveRole(Roles::Manager->value) ? 'status_2' : 'status_1';

            $this->reimbursementApprovalService->handleApproval(
                reimbursement: $reimbursement,
                status: $request->status_2,
                note: $request->input('note'),
                level: $level
            );

            return redirect()
                ->route('admin.reimbursements.index')
                ->with('success', "Reimbursement request {$request->status} successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function edit(Reimbursement $reimbursement)
    {
        // Check if the user has permission to edit this reimbursement
        $user = Auth::user();
        if ($user->id !== $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow editing if the reimbursement is still pending
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            return redirect()->route('manager.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot edit a reimbursement request that has already been processed.');
        }

        $types = \App\Models\ReimbursementType::all();

        return view('manager.reimbursement.update', compact('reimbursement', 'types'));
    }

    public function updateSelf(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        try {
            $this->reimbursementService->update($reimbursement, $request->validated());

            return redirect()->route('manager.reimbursements.index', $reimbursement->id)
                ->with('success', 'Reimbursement request updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function destroy(Reimbursement $reimbursement)
    {
        // Check if the user has permission to delete this reimbursement
        $user = Auth::user();
        if ($user->id !== $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow deleting if the reimbursement is still pending
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            return redirect()->route('manager.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot delete a reimbursement request that has already been processed.');
        }

        if ($reimbursement->invoice_path) {
            Storage::disk('public')->delete($reimbursement->invoice_path);
        }
        $reimbursement->delete();

        return redirect()->route('manager.reimbursements.index')
            ->with('success', 'Reimbursement request deleted successfully.');
    }

    public function exportPdf(Reimbursement $reimbursement)
    {
        $pdf = Pdf::loadView('admin.reimbursement.pdf', compact('reimbursement'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('reimbursement-details.pdf');
    }
}
