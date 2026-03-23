<?php

namespace App\Http\Controllers\ApproverController;

use App\Events\OfficialTravelLevelAdvanced;
use App\Exports\OfficialTravelsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficialTravelRequest;
use App\Http\Requests\UpdateOfficialTravelRequest;
use App\Models\ApprovalLink;
use App\Models\OfficialTravel;
use App\Models\User;
use App\Enums\Roles;
use App\Http\Requests\ApproveOfficialTravelRequest;
use App\Models\Role;
use App\Services\OfficialTravelApprovalService;
use App\Services\OfficialTravelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

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
        $allUsersQuery = OfficialTravel::with(['employee', 'approver1','approver2'])->forLeader(Auth::id())
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

        OfficialTravel::whereNull('seen_by_approver_at')
            ->whereHas('employee', fn($q) => $q->where('division_id', auth()->user()->division_id))
            ->update(['seen_by_approver_at' => now()]);

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('approver.official-travel.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));
    }

    public function show(OfficialTravel $officialTravel)
    {
        if ($officialTravel->employee->division->leader->id !== Auth::id()) {
            return abort(403, 'Unauthorized');
        }

        $officialTravel->load(['employee', 'approver']);
        return view('approver.official-travel.show', compact('officialTravel'));
    }

    public function create()
    {
        return view('approver.official-travel.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficialTravelRequest $request)
    {
        try {
            $this->officialTravelService->store($request->validated());

            return redirect()->route('approver.official-travels.index')
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
            return redirect()->route('approver.official-travels.edit', $officialTravel->id)
                ->with('error', 'You cannot edit a travel request that has already been processed.');
        }

        $officialTravel->load(['employee', 'approver']);

        return view('approver.official-travel.update', compact('officialTravel'));
    }

    public function updateSelf(UpdateOfficialTravelRequest $request, OfficialTravel $travel)
    {
        try {
            $this->officialTravelService->update($travel, $request->validated());

            return redirect()
                ->route('approver.official-travels.index', $travel->id)
                ->with('success', 'Official travel updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function update(ApproveOfficialTravelRequest $request, OfficialTravel $officialTravel)
    {
        try {
            $level = auth()->user()->hasActiveRole(Roles::Manager->value) ? 'status_2' : 'status_1';

            $this->officialTravelApprovalService->handleApproval(
                travel: $officialTravel,
                status: $request->status_1,
                note: $request->note_1,
                level: $level
            );

            return redirect()
                ->route('approver.official-travels.index')
                ->with('success', "Official travel request {$request->status_1} successfully.");

        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }


    public function export(Request $request)
    {
        try {
            // (opsional) disable debugbar yang suka nyisipin output
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            // bersihkan buffer agar XLSX tidak ketimpa
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $filters = [
                'status' => $request->status,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ];

            $filename = 'official-travel-requests-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new OfficialTravelsExport($filters), $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Export error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== $officialTravel->employee_id && !$user->hasActiveRole(Roles::Admin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending') && !$user->hasActiveRole(Roles::Admin->value)) {
            return redirect()->route('approver.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot delete a travel request that has already been processed.');
        }

        $officialTravel->delete();

        return redirect()->route('approver.official-travels.index')
            ->with('success', 'Official travel request deleted successfully.');
    }

    public function exportPdf(OfficialTravel $officialTravel)
    {
        $pdf = Pdf::loadView('admin.travels.pdf', compact('officialTravel'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('official-travel-details.pdf');
    }
}
