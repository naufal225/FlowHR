<?php

namespace App\Http\Controllers\EmployeeController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\ApprovalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Reimbursement;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Services\ReimbursementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $reimbursementService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query utama untuk list data (ada orderBy)
        $query = Reimbursement::where('employee_id', $user->id)
            ->with(['approver'])
            ->orderBy('created_at', 'desc');

        // Apply filters ke query utama
        if ($request->filled('status')) {
            $status = $request->status;

            $query->where(function ($q) use ($status) {
                if ($status === 'rejected') {
                    $q->where('status_1', 'rejected')
                        ->orWhere('status_2', 'rejected');
                } elseif ($status === 'approved') {
                    $q->where('status_1', 'approved')
                        ->where('status_2', 'approved');
                } elseif ($status === 'pending') {
                    $q->where(function ($sub) {
                        $sub->where('status_1', 'pending')
                            ->orWhere('status_2', 'pending');
                    })
                        ->where('status_1', '!=', 'rejected')
                        ->where('status_2', '!=', 'rejected')
                        ->where(function ($sub) {
                            $sub->where('status_1', '!=', 'approved')
                                ->orWhere('status_2', '!=', 'approved');
                        });
                }
            });
        }

        if ($request->filled('from_date')) {
            $query->where(
                'date',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $query->where(
                'date',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        // Data untuk tabel
        $reimbursements = $query->paginate(10)->withQueryString();

        // 🔹 Query baru untuk aggregate (tanpa orderBy)
        $countsQuery = Reimbursement::where('employee_id', $user->id);

        if ($request->filled('status')) {
            $status = $request->status;
            $countsQuery->where(function ($q) use ($status) {
                if ($status === 'rejected') {
                    $q->where('status_1', 'rejected')
                        ->orWhere('status_2', 'rejected');
                } elseif ($status === 'approved') {
                    $q->where('status_1', 'approved')
                        ->where('status_2', 'approved');
                } elseif ($status === 'pending') {
                    $q->where(function ($sub) {
                        $sub->where('status_1', 'pending')
                            ->orWhere('status_2', 'pending');
                    })
                        ->where('status_1', '!=', 'rejected')
                        ->where('status_2', '!=', 'rejected')
                        ->where(function ($sub) {
                            $sub->where('status_1', '!=', 'approved')
                                ->orWhere('status_2', '!=', 'approved');
                        });
                }
            });
        }

        if ($request->filled('from_date')) {
            $countsQuery->where(
                'date',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $countsQuery->where(
                'date',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        // 🔹 Hitung aggregate
        $counts = $countsQuery->withFinalStatusCount()->first();

        $totalRequests = (int) $countsQuery->count();
        $pendingRequests = (int) ($counts->pending ?? 0);
        $approvedRequests = (int) ($counts->approved ?? 0);
        $rejectedRequests = (int) ($counts->rejected ?? 0);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('Employee.reimbursements.reimbursement-show', compact(
            'reimbursements',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'manager'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = \App\Models\ReimbursementType::all();
        return view('Employee.reimbursements.reimbursement-request', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReimbursementRequest $request)
    {
        try {
            $this->reimbursementService->store($request->validated());

            return redirect()->route('employee.reimbursements.index')
                ->with('success', 'Reimbursement request submitted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reimbursement $reimbursement)
    {
        // Check if the user has permission to view this reimbursement
        $user = Auth::user();
        if ($user->id !== (int) $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $reimbursement->load(['approver']);

        return view('Employee.reimbursements.reimbursement-detail', compact('reimbursement'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Reimbursement $reimbursement)
    {
        $pdf = Pdf::loadView('Employee.reimbursements.pdf', compact('reimbursement'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('reimbursement-details.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reimbursement $reimbursement)
    {
        // Check if the user has permission to edit this reimbursement
        $user = Auth::user();
        if ($user->id !== (int) $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow editing if the reimbursement is still pending
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            return redirect()->route('employee.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot edit a reimbursement request that has already been processed.');
        }

        $types = \App\Models\ReimbursementType::all();
        return view('Employee.reimbursements.reimbursement-edit', compact('reimbursement', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        try {
            $this->reimbursementService->update($reimbursement, $request->validated());

            return redirect()->route('employee.reimbursements.show', $reimbursement->id)
                ->with('success', 'Reimbursement request updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reimbursement $reimbursement)
    {
        // Check if the user has permission to delete this reimbursement
        $user = Auth::user();
        if ($user->id !== $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow deleting if the reimbursement is still pending
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            return redirect()->route('employee.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot delete a reimbursement request that has already been processed.');
        }

        if ($reimbursement->invoice_path) {
            Storage::disk('public')->delete($reimbursement->invoice_path);
        }

        if (\App\Models\ApprovalLink::where('model_id', $reimbursement->id)->where('model_type', get_class($reimbursement))->exists()) {
            \App\Models\ApprovalLink::where('model_id', $reimbursement->id)->where('model_type', get_class($reimbursement))->delete();
        }

        $reimbursement->delete();

        return redirect()->route('employee.reimbursements.index')
            ->with('success', 'Reimbursement request deleted successfully.');
    }
}
