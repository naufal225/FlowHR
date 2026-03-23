<?php

namespace App\Http\Controllers\ManagerController;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveOfficialTravelRequest;
use App\Http\Requests\StoreOfficialTravelRequest;
use App\Http\Requests\UpdateOfficialTravelRequest;
use App\Models\OfficialTravel;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
use App\Services\OfficialTravelApprovalService;
use App\Services\OfficialTravelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialTravelController extends Controller
{
    public function __construct(private OfficialTravelService $officialTravelService, private OfficialTravelApprovalService $officialTravelApprovalService)
    {
    }

    public function index(Request $request)
    {
        // Query for user's own requests (all statuses)
        $ownRequestsQuery = OfficialTravel::with(['employee', 'approver1','approver2'])
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Query for all users' requests (excluding own unless approved)
        $allUsersQuery = OfficialTravel::with(['employee', 'approver1','approver2'])
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

        $totalRequests = OfficialTravel::count();
        $pendingRequests = OfficialTravel::where('status_1', 'pending')
            ->orWhere('status_2', 'pending')->count();
        $approvedRequests = OfficialTravel::where('status_1', 'approved')
            ->where('status_2', 'approved')->count();
        $rejectedRequests = OfficialTravel::where('status_1', 'rejected')
            ->orWhere('status_2', 'rejected')->count();

        OfficialTravel::whereNull('seen_by_manager_at')
            // ->whereHas('employee', fn($q) => $q->where('division_id', auth()->user()->division_id))
            ->update(['seen_by_manager_at' => now()]);

         $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('manager.official-travel.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));
    }

    public function show(OfficialTravel $officialTravel)
    {
        $officialTravel->load(['employee', 'approver']);
        return view('manager.official-travel.show', compact('officialTravel'));
    }

    public function create()
    {
        return view('manager.official-travel.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficialTravelRequest $request, OfficialTravelService $service)
    {
        try {
            $service->store($request->validated());

            return redirect()->route('manager.official-travels.index')
                ->with('success', 'Official travel request submitted successfully');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function edit(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== $officialTravel->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending') {
            return redirect()->route('manager.official-travels.edit', $officialTravel->id)
                ->with('error', 'You cannot edit a travel request that has already been processed.');
        }

        $officialTravel->load(['employee', 'approver']);

        return view('manager.official-travel.update', compact('officialTravel'));
    }

    public function updateSelf(UpdateOfficialTravelRequest $request, OfficialTravel $officialTravel)
    {
        try {
            $this->officialTravelService->update($officialTravel, $request->validated());

            return redirect()
                ->route('manager.official-travels.index', $officialTravel->id)
                ->with('success', 'Official travel updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }


    public function update(ApproveOfficialTravelRequest $request, OfficialTravel $officialTravel)
    {
        try {
            $level = Auth::user()->hasActiveRole(Roles::Manager->value) ? 'status_2' : 'status_1';

             $this->officialTravelApprovalService->handleApproval(
                travel: $officialTravel,
                status: $request->status_2,
                note: $request->note_2,
                level: $level
            );

            return redirect()->route('manager.official-travels.index')
                ->with('success', "Travel request {$request->status}.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }


    public function destroy(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== $officialTravel->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending')) {
            return redirect()->route('manager.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot delete a travel request that has already been processed.');
        }

        $officialTravel->delete();

        return redirect()->route('manager.official-travels.index')
            ->with('success', 'Official travel request deleted successfully.');
    }

    public function exportPdf(OfficialTravel $officialTravel)
    {
        $travel = $officialTravel;
        $pdf = Pdf::loadView('admin.official-travel.pdf', compact('travel'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('official-travel-details.pdf');
    }
}
