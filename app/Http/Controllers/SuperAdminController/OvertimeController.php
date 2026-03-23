<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Exports\OvertimesExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOvertimeRequest;
use App\Models\ApprovalLink;
use App\Models\Overtime;
use App\Models\User;
use App\Enums\Roles;
use App\Http\Requests\StoreOvertimeRequest;
use App\Models\Role;
use App\Services\OvertimeApprovalService;
use App\Services\OvertimeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class OvertimeController extends Controller
{
    public function __construct(private OvertimeService $overtimeService, private OvertimeApprovalService $overtimeApprovalService)
    {
    }

    public function index(Request $request)
    {
        // Query for user's own requests (all statuses)
        $ownRequestsQuery = Overtime::with(['employee', 'approver1','approver2'])
            ->where('employee_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Query for all users' requests (excluding own unless approved)
        $allUsersQuery = Overtime::with(['employee', 'approver1','approver2'])
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

        $totalRequests = Overtime::count();
        $pendingRequests = Overtime::where('status_1', 'pending')
            ->orWhere('status_2', 'pending')->count();
        $approvedRequests = Overtime::where('status_1', 'approved')
            ->where('status_2', 'approved')->count();
        $rejectedRequests = Overtime::where('status_1', 'rejected')
            ->orWhere('status_2', 'rejected')->count();

        $managerRole = Role::where('name', 'manager')->first();

       $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();
        return view('super-admin.overtime.index', compact('allUsersRequests', 'ownRequests', 'totalRequests', 'pendingRequests', 'approvedRequests', 'rejectedRequests', 'manager'));
    }

    public function show($id)
    {
        $overtime = Overtime::findOrFail($id);
        $overtime->load(['employee', 'approver']);
        return view('super-admin.overtime.show', compact('overtime'));
    }

    public function edit(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        if ($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') {
            return redirect()->route('super-admin.overtimes.show', $overtime->id)
                ->with('error', 'You cannot edit an overtime request that has already been processed.');
        }

        return view('super-admin.overtime.update', compact('overtime'));
    }

    public function update(UpdateOvertimeRequest $request, Overtime $overtime)
    {
        if (Auth::id() !== $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $this->overtimeService->update($overtime, $request->validated());
            return redirect()->route('super-admin.overtimes.index', $overtime->id)
                ->with('success', 'Overtime request updated successfully');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

    }

    public function create()
    {
        return view('super-admin.overtime.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOvertimeRequest $request)
    {
        try {
            $overtime = $this->overtimeService->store($request->validated());
            return redirect()->route('super-admin.overtimes.index')
                ->with('success', "Overtime submitted. Total: {$overtime->total}");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
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

            $filename = 'overtime-requests-' . now()->format('Y-m-d-H-i-s') . '.xlsx';

            return Excel::download(new OvertimesExport($filters), $filename);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Export error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id && !$user->hasActiveRole(Roles::SuperAdmin->value)) {
            abort(403, 'Unauthorized action.');
        }

        if (($overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending') && !$user->hasActiveRole(Roles::SuperAdmin->value)) {
            return redirect()->route('super-admin.overtimes.show', $overtime->id)
                ->with('error', 'You cannot delete an overtime request that has already been processed.');
        }

        $overtime->delete();

        return redirect()->route('super-admin.overtimes.index')
            ->with('success', 'Overtime request deleted successfully.');
    }


    public function exportPdf(Overtime $overtime)
    {
        $pdf = Pdf::loadView('Employee.overtimes.pdf', compact('overtime'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('overtime-details.pdf');
    }

    public function exportPdfAllData(Request $request)
    {
        try {
            // Authorization: Only Super Admin
            if (!Auth::user()->hasActiveRole(Roles::SuperAdmin->value)) {
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

            // Bangun query dasar untuk semua overtime
            // Pastikan eager loading semua relasi yang dibutuhkan oleh view PDF
            $query = Overtime::with(['employee', 'approver', 'employee.division']);

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
                        // Logika "pending" yang kompleks
                        $query->where(function ($q) {
                            // Kondisi 1: Minimal satu status adalah 'pending'
                            $q->where(function ($qq) {
                                $qq->where('status_1', 'pending')
                                    ->orWhere('status_2', 'pending');
                            });
                            // Kondisi 2: Tidak ada status yang 'rejected'
                            $q->where(function ($qq) {
                                $qq->where('status_1', '!=', 'rejected')
                                    ->where('status_2', '!=', 'rejected');
                            });
                        });
                        break;
                    // Tidak ada case default, jadi jika status tidak valid, tidak ada filter
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
            $overtimes = $query->get();

            if ($overtimes->isEmpty()) {
                return response()->json(['message' => 'No data found for the selected filters.'], 404);
            }

            // Buat direktori sementara untuk menyimpan PDF
            // uniqid() memastikan nama direktori unik untuk menghindari konflik
            $tempDir = storage_path('app/temp_pdf_exports_' . uniqid());
            File::makeDirectory($tempDir, 0755, true); // Buat direktori

            // Buat PDF untuk setiap reimbursement
            foreach ($overtimes as $overtime) {
                // Load view PDF dengan data overtime
                // View harus menggunakan $overtime->employee, bukan Auth::user()
                $pdf = Pdf::loadView('admin.overtime.pdf', compact('overtime'))
                    ->setOptions(['isPhpEnabled' => true]);

                // Buat nama file yang unik dan deskriptif
                // Sanitasi nama file untuk menghindari karakter ilegal
                $safeEmployeeName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $overtime->employee->name ?? 'Unknown');
                $fileName = "overtime_{$safeEmployeeName}_RY{$overtime->id}.pdf";
                $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

                // Simpan PDF ke direktori sementara
                $pdf->save($filePath);
            }

            // Buat file ZIP
            $zipFileName = 'overtime-requests-all-' . now()->format('Y-m-d-H-i-s') . '.zip';
            $zipFilePath = storage_path('app/' . $zipFileName);
            $zip = new ZipArchive();

            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // Tambahkan semua file PDF yang telah dibuat ke dalam ZIP
                $files = File::files($tempDir);
                foreach ($files as $file) {
                    // Tambahkan file ke ZIP dengan nama file asli
                    $zip->addFile($file->getPathname(), $file->getFilename());
                }
                $zip->close();
            } else {
                // Jika gagal membuat ZIP, hapus direktori sementara dan lempar exception
                if (File::exists($tempDir)) {
                    File::deleteDirectory($tempDir);
                }
                throw new \Exception('Could not create ZIP file.');
            }

            // Hapus direktori sementara setelah ZIP berhasil dibuat
            if (File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            // Return file ZIP sebagai download
            // deleteFileAfterSend(true) akan menghapus file ZIP setelah dikirim ke browser
            return response()->download($zipFilePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            // Log error untuk debugging
            Log::error('Export PDF All Data error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id' => Auth::id(),
                'filters' => $request->only(['status', 'from_date', 'to_date'])
            ]);

            // Hapus file/direktori sementara jika ada error di tengah jalan
            if (isset($tempDir) && File::exists($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            if (isset($zipFilePath) && File::exists($zipFilePath)) {
                File::delete($zipFilePath);
            }

            // Return JSON error response untuk AJAX
            return response()->json([
                'error' => 'Export PDF (All) failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
