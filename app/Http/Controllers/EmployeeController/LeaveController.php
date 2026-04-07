<?php

namespace App\Http\Controllers\EmployeeController;

use App\Events\LeaveSubmitted;
use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Models\ApprovalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Leave;
use App\Models\User;
use App\Models\Division;
use App\Models\Role;
use App\Services\HolidayDateService;
use App\Services\LeaveApprovalService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveController extends Controller
{
    public function __construct(
        private LeaveService $leaveService,
        private HolidayDateService $holidayDateService,
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query utama untuk list data
        $query = Leave::where('employee_id', $user->id)
            ->with(['employee', 'approver1'])
            ->orderBy('created_at', 'desc');

        // Filter status
        if ($request->filled('status')) {
            $status = $request->status;

            $query->where(function ($q) use ($status) {
                if ($status === 'rejected') {
                    $q->where('status_1', 'rejected');
                } elseif ($status === 'approved') {
                    $q->where('status_1', 'approved');
                } elseif ($status === 'pending') {
                    $q->where(function ($sub) {
                        $sub->where('status_1', 'pending');
                    })
                        ->where('status_1', '!=', 'rejected')
                        ->where(function ($sub) {
                            $sub->where('status_1', '!=', 'approved');
                        });
                }
            });
        }

        // Filter tanggal
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

        // Data untuk tabel (pagination)
        $leaves = $query->paginate(10)->withQueryString();

        // Query baru khusus untuk aggregate (tanpa orderBy)
        $countsQuery = Leave::where('employee_id', $user->id);
        if ($request->filled('status')) {
            $countsQuery->filterFinalStatus($request->status); // pakai scope dari HasDualStatus
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

        $counts = $countsQuery->withFinalStatusCount()->first();

        $sisaCuti = $this->leaveService->sisaCuti($user);

        // 🔹 Ambil count aman
        $totalRequests = (int) Leave::where('employee_id', $user->id)->count();
        $pendingRequests = (int) ($counts->pending ?? 0);
        $approvedRequests = (int) ($counts->approved ?? 0);
        $rejectedRequests = (int) ($counts->rejected ?? 0);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('Employee.leaves.leave-show', compact(
            'leaves',
            'totalRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'manager',
            'sisaCuti'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create()
    {
        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $holidays = $this->holidayDateService->getDateStringsForForm();
        if ($sisaCuti <= 0) {
            abort(422, 'Sisa cuti tidak cukup.');
        }

        return view('Employee.leaves.leave-request', compact('sisaCuti', 'holidays'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLeaveRequest $request)
    {
        try {
            $this->leaveService->store($request->validated());

            return redirect()->route('employee.leaves.index')
                ->with('success', 'Leave request submitted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    /**
     * Backward compatible helper still used by several leave blade views.
     *
     * @param \Carbon\Carbon|string $start
     * @param \Carbon\Carbon|string $end
     * @param int|string $tahunSekarang
     * @param string[] $hariLibur
     */
    public function hitungHariCuti($start, $end, $tahunSekarang, $hariLibur): int
    {
        return $this->leaveService->hitungHariCuti($start, $end, (int) $tahunSekarang, $hariLibur);
    }

    /**
     * Display the specified resource.
     */
    public function show(Leave $leave)
    {
        $user = Auth::user();
        if ($user->id !== (int) $leave->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $leave->load(['employee', 'approver']);

        return view('Employee.leaves.leave-detail', compact('leave'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Leave $leave)
    {
        $pdf = Pdf::loadView('Employee.leaves.pdf', compact('leave'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('leave-details.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Leave $leave)
    {
        // Check if the user has permission to edit this leave
        $user = Auth::user();
        if ($user->id !== (int) $leave->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $sisaCuti = $this->leaveService->sisaCuti(Auth::user());

        $holidays = $this->holidayDateService->getDateStringsForForm();

        // Only allow editing if the leave is still pending
        if ($leave->status_1 !== 'pending') {
            return redirect()->route('employee.leaves.show', $leave->id)
                ->with('error', 'You cannot edit a leave request that has already been processed.');
        }

        return view('Employee.leaves.leave-edit', compact('leave', 'sisaCuti', 'holidays'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeaveRequest $request, Leave $leave)
    {
        try {
            $this->leaveService->update($leave, $request->validated());

            return redirect()->route('employee.leaves.index')
                ->with('success', 'Leave request updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leave $leave)
    {
        // Check if the user has permission to delete this leave
        $user = Auth::user();
        if ($user->id !== $leave->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow deleting if the leave is still pending
        if (($leave->status_1 !== 'pending') && !$user->hasActiveRole(Roles::Admin->value)) {
            return redirect()->route('employee.leaves.show', $leave->id)
                ->with('error', 'You cannot delete a leave request that has already been processed.');
        }

        if (ApprovalLink::where('model_id', $leave->id)->where('model_type', get_class($leave))->exists()) {
            ApprovalLink::where('model_id', $leave->id)->where('model_type', get_class($leave))->delete();
        }

        $leave->delete();

        return redirect()->route('employee.leaves.index')
            ->with('success', 'Leave request deleted successfully.');
    }
}
