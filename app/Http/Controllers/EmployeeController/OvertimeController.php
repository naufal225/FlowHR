<?php

namespace App\Http\Controllers\EmployeeController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOvertimeRequest;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\ApprovalLink;
use App\Services\OvertimeApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Roles;
use App\Models\Overtime;
use App\Models\Role;
use App\Models\User;
use App\Services\OvertimeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Str;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query utama untuk list data (ada orderBy)
        $query = Overtime::where('employee_id', $user->id)
            ->with(['employee', 'approver1','approver2'])
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
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $query->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        // Data untuk tabel
        $overtimes = $query->paginate(10)->withQueryString();

        // 🔹 Query baru untuk aggregate (tanpa orderBy)
        $countsQuery = Overtime::where('employee_id', $user->id);

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
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $countsQuery->where(
                'date_end',
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

        return view('Employee.overtimes.overtime-show', compact(
            'overtimes',
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
        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();
        return view('Employee.overtimes.overtime-request', compact('approvers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOvertimeRequest $request)
    {
        try {
            $overtime = $this->overtimeService->store($request->validated());
            return redirect()->route('employee.overtimes.index')
                ->with('success', "Overtime submitted. Total: {$overtime->total}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== (int) $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $overtime->load(['employee', 'approver']);
        return view('Employee.overtimes.overtime-detail', compact('overtime'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Overtime $overtime)
    {
        $pdf = Pdf::loadView('Employee.overtimes.pdf', compact('overtime'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('overtime-details.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== (int) $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') {
            return redirect()->route('employee.overtimes.show', $overtime->id)
                ->with('error', 'You cannot edit an overtime request that has already been processed.');
        }

        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();
        return view('Employee.overtimes.overtime-edit', compact('overtime', 'approvers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        if (Auth::id() !== $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->overtimeService->update($overtime, $request->validated());
            return redirect()->route('employee.overtimes.show', $overtime->id)
                ->with('success', 'Overtime updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') && !$user->hasActiveRole(Roles::Admin->value)) {
            return redirect()->route('employee.overtimes.show', $overtime->id)
                ->with('error', 'You cannot delete an overtime request that has already been processed.');
        }

        if (\App\Models\ApprovalLink::where('model_id', $overtime->id)->where('model_type', get_class($overtime))->exists()) {
            \App\Models\ApprovalLink::where('model_id', $overtime->id)->where('model_type', get_class($overtime))->delete();
        }

        $overtime->delete();

        return redirect()->route('employee.overtimes.index')
            ->with('success', 'Overtime request deleted successfully.');
    }
}
