<?php

namespace App\Http\Controllers\EmployeeController;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficialTravelRequest;
use App\Http\Requests\UpdateOfficialTravelRequest;
use App\Models\ApprovalLink;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\Roles;
use App\Models\OfficialTravel;
use App\Models\Role;
use App\Models\User;
use App\Services\OfficialTravelService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfficialTravelController extends Controller
{
    public function __construct(private OfficialTravelService $officialTravelService)
    {
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Query utama untuk list data (ada orderBy)
        $query = OfficialTravel::where('employee_id', $user->id)
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

        // Data tabel (ada pagination)
        $officialTravels = $query->paginate(10)->withQueryString();

        // 🔹 Query baru khusus aggregate (tanpa orderBy)
        $countsQuery = OfficialTravel::where('employee_id', $user->id);

        if ($request->filled('status')) {
            // pake scope yang sama atau ulangi filter disini kalau perlu
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

        // 🔹 Jalankan aggregate aman
        $counts = $countsQuery->withFinalStatusCount()->first();

        $totalRequests = (int) $countsQuery->count();
        $pendingRequests = (int) ($counts->pending ?? 0);
        $approvedRequests = (int) ($counts->approved ?? 0);
        $rejectedRequests = (int) ($counts->rejected ?? 0);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('Employee.travels.travel-show', compact(
            'officialTravels',
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
        return view('Employee.travels.travel-request', compact('approvers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficialTravelRequest $request)
    {
        try {
            $this->officialTravelService->store($request->validated());

            return redirect()->route('employee.official-travels.index')
                ->with('success', 'Official travel request submitted successfully');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== (int) $officialTravel->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $officialTravel->load(['employee', 'approver']);
        return view('Employee.travels.travel-detail', compact('officialTravel'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(OfficialTravel $officialTravel)
    {
        $pdf = Pdf::loadView('Employee.travels.pdf', compact('officialTravel'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('official-travel-details.pdf');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== (int) $officialTravel->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending') {
            return redirect()->route('employee.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot edit a travel request that has already been processed.');
        }

        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();
        return view('Employee.travels.travel-edit', compact('officialTravel', 'approvers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfficialTravelRequest $request, OfficialTravel $travel)
    {
        try {
            $this->officialTravelService->update($travel, $request->validated());

            return redirect()
                ->route('employee.official-travels.show', $travel->id)
                ->with('success', 'Official travel updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== $officialTravel->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending') && !$user->hasActiveRole(Roles::Admin->value)) {
            return redirect()->route('employee.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot delete a travel request that has already been processed.');
        }

        if (ApprovalLink::where('model_id', $officialTravel->id)->where('model_type', get_class($officialTravel))->exists()) {
            ApprovalLink::where('model_id', $officialTravel->id)->where('model_type', get_class($officialTravel))->delete();
        }

        $officialTravel->delete();

        return redirect()->route('employee.official-travels.index')
            ->with('success', 'Official travel request deleted successfully.');
    }
}
