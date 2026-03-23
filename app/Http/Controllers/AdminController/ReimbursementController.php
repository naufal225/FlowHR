<?php

namespace App\Http\Controllers\AdminController;

use App\Exports\ReimbursementsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReimbursementRequest;
use App\Http\Requests\UpdateReimbursementRequest;
use App\Models\ApprovalLink;
use App\Models\Reimbursement;
use App\Models\User;
use App\Enums\Roles;
use App\Models\Role;
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
use ZipArchive; // Tambahkan ini
use Illuminate\Support\Facades\File; // Tambahkan ini untuk manipulasi file sementara

class ReimbursementController extends Controller
{
    public function __construct(private ReimbursementService $reimbursementService)
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

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('admin.reimbursement.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));
    }

    public function show($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $reimbursement->load(['approver']);

        return view('admin.reimbursement.show', compact('reimbursement'));
    }

    public function create()
    {
        $types = \App\Models\ReimbursementType::all();
        return view('admin.reimbursement.create', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReimbursementRequest $request)
    {
        try {
            $this->reimbursementService->store($request->validated());

            return redirect()->route('admin.reimbursements.index')
                ->with('success', 'Reimbursement request submitted successfully.');
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

            $filename = 'reimbursement-requests-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new ReimbursementsExport($filters), $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Export error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reimbursement $reimbursement)
    {
        // Check if the user has permission to edit this reimbursement
        $user = Auth::user();
        if ($user->id !== $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow editing if the reimbursement is still pending
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            return redirect()->route('admin.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot edit a reimbursement request that has already been processed.');
        }

        $types = \App\Models\ReimbursementType::all();

        return view('admin.reimbursement.update', compact('reimbursement', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReimbursementRequest $request, Reimbursement $reimbursement)
    {
        try {
            $this->reimbursementService->update($reimbursement, $request->validated());

            return redirect()->route('admin.reimbursements.index', $reimbursement->id)
                ->with('success', 'Reimbursement request updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
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
            return redirect()->route('admin.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot delete a reimbursement request that has already been processed.');
        }

        if ($reimbursement->invoice_path) {
            Storage::disk('public')->delete($reimbursement->invoice_path);
        }
        $reimbursement->delete();

        return redirect()->route('admin.reimbursements.index')
            ->with('success', 'Reimbursement request deleted successfully.');
    }


    public function exportPdf(Reimbursement $reimbursement)
    {
        $pdf = Pdf::loadView('admin.reimbursement.pdf', compact('reimbursement'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('reimbursement-details.pdf');
    }

    public function exportPdfAllData(Request $request)
    {
        try {
            // Authorization: Only Admin
            if (!Auth::user()->hasActiveRole(Roles::Admin->value)) {
                abort(403, 'Unauthorized action.');
            }

            // (opsional) disable debugbar
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            // Bersihkan buffer
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Bangun query dasar untuk semua reimbursement
            $query = Reimbursement::with(['employee', 'approver', 'employee.division']);

            // Terapkan filter status
            if ($request->filled('status')) {
                $statusFilter = $request->status;
                switch ($statusFilter) {
                    case 'approved':
                        $query->where('status_1', 'approved')
                            ->where('status_2', 'approved');
                        break;
                    case 'rejected':
                        $query->where(function ($q) {
                            $q->where('status_1', 'rejected')
                                ->orWhere('status_2', 'rejected');
                        });
                        break;
                    case 'pending':
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
                    // default: tidak filter status
                }
            }

            // Terapkan filter tanggal
            if ($request->filled('from_date')) {
                $fromDate = Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta');
                $query->where('created_at', '>=', $fromDate);
            }
            if ($request->filled('to_date')) {
                $toDate = Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta');
                $query->where('created_at', '<=', $toDate);
            }

            // Ambil data
            $reimbursements = $query->get();

            if ($reimbursements->isEmpty()) {
                return response()->json(['message' => 'No data found for the selected filters.'], 404);
            }

            // Buat direktori sementara untuk menyimpan PDF
            $tempDir = storage_path('app/temp_pdf_exports_' . uniqid());
            File::makeDirectory($tempDir, 0755, true);

            // Buat PDF untuk setiap reimbursement
            foreach ($reimbursements as $reimbursement) {

                $pdf = Pdf::loadView('admin.reimbursement.pdf', compact('reimbursement'))
                    ->setOptions(['isPhpEnabled' => true]);

                // Nama file PDF unik
                $fileName = "Reimbursement_{$reimbursement->employee->name}_RY{$reimbursement->id}.pdf";
                $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

                // Simpan PDF ke direktori sementara
                $pdf->save($filePath);
            }

            // Buat file ZIP
            $zipFileName = 'reimbursement-requests-all-' . now()->format('Y-m-d-H-i-s') . '.zip';
            $zipFilePath = storage_path('app/' . $zipFileName);
            $zip = new ZipArchive();

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Tambahkan semua file PDF ke ZIP
                $files = File::files($tempDir);
                foreach ($files as $file) {
                    $zip->addFile($file->getPathname(), $file->getFilename());
                }
                $zip->close();
            } else {
                // Hapus direktori sementara jika ada error
                File::deleteDirectory($tempDir);
                throw new \Exception('Could not create ZIP file.');
            }

            // Hapus direktori sementara setelah ZIP dibuat
            File::deleteDirectory($tempDir);

            // Return ZIP file sebagai download
            return response()->download($zipFilePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Log error
            Log::error('Export PDF All Data error: ' . $e->getMessage(), ['exception' => $e]);

            // Hapus file/direktori sementara jika ada error di tengah jalan
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            if (isset($zipFilePath) && File::exists($zipFilePath)) {
                File::delete($zipFilePath);
            }

            // Return JSON error response
            return response()->json([
                'error' => 'Export PDF (All) failed: ' . $e->getMessage()
            ], 500);
        }

    }
}
