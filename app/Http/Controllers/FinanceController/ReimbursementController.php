<?php

namespace App\Http\Controllers\FinanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\Roles;
use App\TypeRequest;
use App\Models\Leave;
use App\Models\User;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApprovalLink;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Str;
use Exception;

class ReimbursementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // --- Query untuk "Your Reimbursements"
        $yourReimbursementsQuery = Reimbursement::with(['employee', 'approver', 'type'])
            ->where('employee_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $yourReimbursementsQuery->where(
                'date',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $yourReimbursementsQuery->where(
                'date',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $yourReimbursementsQuery->where(function ($q) use ($status) {
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

        $yourReimbursements = $yourReimbursementsQuery
            ->paginate(5, ['*'], 'your_page')
            ->withQueryString();

        // --- Query untuk "All Reimbursements Done (Marked Down)"
        $allReimbursementsDoneQuery = Reimbursement::with(['employee', 'approver', 'type'])
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->where('marked_down', true)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $allReimbursementsDoneQuery->where(
                'date',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allReimbursementsDoneQuery->where(
                'date',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allReimbursementsDone = $allReimbursementsDoneQuery
            ->paginate(5, ['*'], 'all_page_done')
            ->withQueryString();

        // --- Query untuk "All Reimbursements Not Marked"
        $allReimbursementsQuery = Reimbursement::with(['employee', 'approver1', 'approver2', 'type'])
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->where('marked_down', false)
            ->orderBy('created_at', 'asc');

        if ($request->filled('from_date')) {
            $allReimbursementsQuery->where(
                'date',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allReimbursementsQuery->where(
                'date',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allReimbursements = $allReimbursementsQuery
            ->paginate(5, ['*'], 'all_page')
            ->withQueryString();

        // --- Statistik (dipisah supaya tidak bentrok dengan GROUP BY)
        $dataAll = Reimbursement::where('status_1', 'approved')
            ->where('status_2', 'approved');

        $totalRequests = $dataAll->count();
        $approvedRequests = (clone $dataAll)->count(); // karena semua sudah approved
        $markedRequests = (clone $dataAll)->where('marked_down', true)->count();
        $totalAllNoMark = (clone $dataAll)->where('marked_down', false)->count();

        // Statistik untuk reimbursement milik user
        $totalYoursRequests = (clone $yourReimbursementsQuery)->count();
        $pendingYoursRequests = (clone $yourReimbursementsQuery)->where(function ($q) {
            $q->where('status_1', 'pending')
                ->orWhere('status_2', 'pending');
        })->count();
        $approvedYoursRequests = (clone $yourReimbursementsQuery)
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->count();
        $rejectedYoursRequests = (clone $yourReimbursementsQuery)->where(function ($q) {
            $q->where('status_1', 'rejected')
                ->orWhere('status_2', 'rejected');
        })->count();

        // --- Manager
        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('Finance.reimbursements.reimbursement-show', compact(
            'yourReimbursements',
            'allReimbursements',
            'allReimbursementsDone',
            'totalRequests',
            'markedRequests',
            'totalAllNoMark',
            'approvedRequests',
            'manager',
            'totalYoursRequests',
            'pendingYoursRequests',
            'approvedYoursRequests',
            'rejectedYoursRequests'
        ));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = \App\Models\ReimbursementType::all();
        return view('Finance.reimbursements.reimbursement-request', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer' => 'required',
            'total' => 'required|numeric|min:0',
            'date' => 'required|date',
            'reimbursement_type_id' => 'required|exists:reimbursement_types,id',
            'invoice_path' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'customer.required' => 'Customer harus dipilih.',
            'customer.exists' => 'Customer tidak valid.',
            'total.required' => 'Total harus diisi.',
            'total.numeric' => 'Total harus berupa angka.',
            'total.min' => 'Total tidak boleh kurang dari 0.',
            'date.required' => 'Tanggal harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'invoice_path.file' => 'File yang diupload tidak valid.',
            'invoice_path.mimes' => 'File harus berupa: jpg, jpeg, png, pdf.',
            'invoice_path.max' => 'Ukuran file tidak boleh lebih dari 2MB.',
            'reimbursement_type_id.required' => 'Tipe reimbursement harus dipilih.',
            'reimbursement_type_id.exists' => 'Tipe reimbursement tidak valid.',
        ]);

        DB::transaction(function () use ($request) {
            $reimbursement = new Reimbursement();
            $reimbursement->employee_id = Auth::id();
            $reimbursement->customer = $request->customer;
            $reimbursement->reimbursement_type_id = $request->reimbursement_type_id;
            $reimbursement->total = $request->total;
            $reimbursement->date = $request->date;

            $submitter = Auth::user();
            $isTeamLeader = $submitter->userHasRole('team-leader');
            $isManager = $submitter->userHasRole('manager');

            if ($isManager) {
                $reimbursement->status_1 = 'approved';
                $reimbursement->status_2 = 'approved';
                $reimbursement->approver_1_id = $submitter->id;
                $reimbursement->approver_2_id = $submitter->id;
                $reimbursement->approved_date = now();
            } elseif ($isTeamLeader) {
                $reimbursement->status_1 = 'approved';
                $reimbursement->status_2 = 'pending';
                $reimbursement->approver_1_id = $submitter->id;
            } else {
                $reimbursement->status_1 = 'pending';
                $reimbursement->status_2 = 'pending';
            }

            if ($request->hasFile('invoice_path')) {
                $path = $request->file('invoice_path')->store('reimbursement_invoices', 'public');
                $reimbursement->invoice_path = $path;
            }

            $reimbursement->save();

            $token = null;

            if ($isManager) {
                return;
            } elseif ($isTeamLeader) {
                // --- Jika leader, langsung kirim ke Manager (level 2)
                $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();

                if ($manager) {
                    $token = \Illuminate\Support\Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($reimbursement),
                        'model_id' => $reimbursement->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2,
                        'scope' => 'both',
                        'token' => hash('sha256', $token),
                        'expires_at' => now()->addDays(3),
                    ]);
                }

                DB::afterCommit(function () use ($reimbursement, $token) {
                    $fresh = $reimbursement->fresh();
                    event(new \App\Events\ReimbursementLevelAdvanced(
                        $fresh,
                        $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                        'manager'
                    ));

                    if (!$fresh || !$token) {
                        return;
                    }

                    $linkTanggapan = route('public.approval.show', $token);

                    $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
                    Mail::to($manager->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $manager->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                            attachmentPath: $reimbursement->invoice_path
                        )
                    );
                });

            } else {
                // --- Kalau bukan leader, jalur normal ke approver (team lead)
                if ($reimbursement->approver) {
                    $token = \Illuminate\Support\Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($reimbursement),   // App\Models\reim$reimbursement
                        'model_id' => $reimbursement->id,
                        'approver_user_id' => $reimbursement->approver->id,
                        'level' => 1, // level 1 berarti arahnya ke team lead
                        'scope' => 'both',             // boleh approve & reject
                        'token' => hash('sha256', $token), // simpan hash, kirim raw
                        'expires_at' => now()->addDays(3),  // masa berlaku
                    ]);

                }

                DB::afterCommit(function () use ($reimbursement, $request, $token) {
                    $fresh = $reimbursement->fresh(); // ambil ulang (punya created_at dll)
                    // dd("jalan");
                    event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));

                    // Kalau tidak ada approver atau token, jangan kirim email
                    if (!$fresh || !$fresh->approver || !$token) {
                        return;
                    }

                    $linkTanggapan = route('public.approval.show', $token);

                    Mail::to($reimbursement->approver->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $reimbursement->approver->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                            attachmentPath: $reimbursement->invoice_path
                        )
                    );
                });
            }
        });


        return redirect()->route('finance.reimbursements.index')
            ->with('success', 'Reimbursement request submitted successfully.');
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

        $isTeamLeader = $user->userHasRole('team-leader');

        // Only allow editing if the reimbursement is still pending
        if (($isTeamLeader && $reimbursement->status_2 !== 'pending') || (!$isTeamLeader && $reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending')) {
            return redirect()->route('finance.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot edit a reimbursement request that has already been processed.');
        }

        $types = \App\Models\ReimbursementType::all();
        return view('Finance.reimbursements.reimbursement-edit', compact('reimbursement', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reimbursement $reimbursement)
    {
        $user = Auth::user();
        if ($user->id !== (int) $reimbursement->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $isTeamLeader = $user->userHasRole('team-leader');

        if (($isTeamLeader && $reimbursement->status_2 !== 'pending') || (!$isTeamLeader && $reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending')) {
            return redirect()->route('finance.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot update a reimbursement request that has already been processed.');
        }

        $request->validate([
            'customer' => 'required',
            'total' => 'required|numeric|min:0',
            'date' => 'required|date',
            'invoice_path' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'reimbursement_type_id' => 'required|exists:reimbursement_types,id',
        ], [
            'customer.required' => 'Customer harus dipilih.',
            'customer.exists' => 'Customer tidak valid.',
            'total.required' => 'Total harus diisi.',
            'total.numeric' => 'Total harus berupa angka.',
            'total.min' => 'Total tidak boleh kurang dari 0.',
            'date.required' => 'Tanggal harus diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'invoice_path.file' => 'File yang diupload tidak valid.',
            'invoice_path.mimes' => 'File harus berupa: jpg, jpeg, png, pdf.',
            'invoice_path.max' => 'Ukuran file tidak boleh lebih dari 2MB.',
            'reimbursement_type_id.required' => 'Tipe reimbursement harus dipilih.',
            'reimbursement_type_id.exists' => 'Tipe reimbursement tidak valid.',
        ]);

        $reimbursement->customer = $request->customer;
        $reimbursement->total = $request->total;
        $reimbursement->reimbursement_type_id = $request->reimbursement_type_id;
        $reimbursement->date = $request->date;

            $submitter = Auth::user();
            $isTeamLeader = $submitter->userHasRole('team-leader');
            $isManager = $submitter->userHasRole('manager');

            if ($isManager) {
                $reimbursement->status_1 = 'approved';
                $reimbursement->status_2 = 'approved';
                $reimbursement->approver_1_id = $submitter->id;
                $reimbursement->approver_2_id = $submitter->id;
                $reimbursement->approved_date = now();
            } elseif ($isTeamLeader) {
                $reimbursement->status_1 = 'approved';
                $reimbursement->status_2 = 'pending';
                $reimbursement->approver_1_id = $submitter->id;
            } else {
                $reimbursement->status_1 = 'pending';
                $reimbursement->status_2 = 'pending';
            }

            if ($request->hasFile('invoice_path')) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
            }
            $path = $request->file('invoice_path')->store('reimbursement_invoices', 'public');
            $reimbursement->invoice_path = $path;
        } elseif ($request->input('remove_invoice_path')) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
                $reimbursement->invoice_path = null;
            }
        }

        $reimbursement->save();

        $token = null;

        if ($isManager) {
            return redirect()->route('finance.reimbursements.show', $reimbursement->id)
                ->with('success', 'Reimbursement request updated successfully.');
        } elseif ($isTeamLeader) {
            // --- Jika leader, langsung kirim ke Manager (level 2)
            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
            if ($manager) {
                $token = \Illuminate\Support\Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($reimbursement),
                    'model_id' => $reimbursement->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2,
                    'scope' => 'both',
                    'token' => hash('sha256', $token),
                    'expires_at' => now()->addDays(3),
                ]);
            }

            DB::afterCommit(function () use ($reimbursement, $token) {
                $fresh = $reimbursement->fresh();
                event(new \App\Events\ReimbursementLevelAdvanced(
                    $fresh,
                    $fresh?->employee?->division_id ?? (Auth::user()->division_id ?? 0),
                    'manager'
                ));
                if (!$fresh || !$token) {
                    return;
                }
                $linkTanggapan = route('public.approval.show', $token);
                $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
                Mail::to($manager->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: Auth::user()->name,
                        namaApprover: $manager->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: Auth::user()->email,
                        attachmentPath: $reimbursement->invoice_path
                    )
                );
            });
        } else {
            // --- Kalau bukan leader, jalur normal ke approver (team lead)
            if ($reimbursement->approver) {
                $token = \Illuminate\Support\Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($reimbursement),   // App\Models\reim$reimbursement
                    'model_id' => $reimbursement->id,
                    'approver_user_id' => $reimbursement->approver->id,
                    'level' => 1, // level 1 berarti arahnya ke team lead
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);
            }

            DB::afterCommit(function () use ($reimbursement, $request, $token) {
                $fresh = $reimbursement->fresh(); // ambil ulang (punya created_at dll)
                // dd("jalan");
                event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));
                // Kalau tidak ada approver atau token, jangan kirim email
                if (!$fresh || !$fresh->approver || !$token) {
                    return;
                }
                $linkTanggapan = route('public.approval.show', $token);
                Mail::to($reimbursement->approver->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: Auth::user()->name,
                        namaApprover: $reimbursement->approver->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: Auth::user()->email,
                        attachmentPath: $reimbursement->invoice_path
                    )
                );
            });
        }

        return redirect()->route('finance.reimbursements.show', $reimbursement->id)
            ->with('success', 'Reimbursement request updated successfully.');
    }

    /**
     * Mark selected reimbursements as done (marked_down = true).
     */

    public function markedDone(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        try {
            $updated = Reimbursement::whereIn('id', $ids)
                ->where('status_1', 'approved')
                ->where('status_2', 'approved')
                ->where('marked_down', false)
                ->update([
                    'marked_down' => true,
                    'locked_by' => null,
                    'locked_at' => null,
                ]);

            if ($updated < 1) {
                throw new Exception('No reimbursements available to mark as done.');
            }

            return redirect()
                ->route('finance.reimbursements.index')
                ->with('success', $updated . ' reimbursement(s) marked as done.');
        } catch (Exception $e) {
            return redirect()
                ->route('finance.reimbursements.index')
                ->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reimbursement $reimbursement)
    {
        $reimbursement->load('approver');
        return view('Finance.reimbursements.reimbursement-detail', compact('reimbursement'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Reimbursement $reimbursement)
    {
        $pdf = Pdf::loadView('Finance.reimbursements.pdf', compact('reimbursement'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('reimbursement-details-finance.pdf');
    }

    /**
     * Bulk export approved requests as PDFs in a ZIP file.
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:approved,rejected,pending'],
        ]);

        $dateFrom = Carbon::parse((string) $request->input('from_date'), 'Asia/Jakarta')->startOfDay();
        $dateTo = Carbon::parse((string) $request->input('to_date'), 'Asia/Jakarta')->endOfDay();

        if ($dateFrom->diffInDays($dateTo) > 31) {
            return back()->with('error', 'Maximum export range is 31 days.');
        }

        $startedAt = microtime(true);

        $query = Reimbursement::with('employee')->where('status_1', 'approved')->where('status_2', 'approved')->where('marked_down', true);

        $query->where('date', '>=', $dateFrom)->where('date', '<=', $dateTo);

        $reimbursements = $query->get();

        if ($reimbursements->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk filter tersebut.');
        }

        if ($reimbursements->count() > 300) {
            return back()->with('error', 'Export limit exceeded. Maximum 300 records per request.');
        }

        $zipFileName = 'ReimbursementRequests_' . Carbon::now()->format('YmdHis') . '.zip';
        $zipPath = Storage::disk('public')->path($zipFileName);

        // Folder sementara untuk menyimpan PDF
        $tempFolder = 'temp_reimbursements';
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder);
        }

        $files = [];

        foreach ($reimbursements as $reimbursement) {
            $pdf = Pdf::loadView('Finance.reimbursements.pdf', compact('reimbursement'))
                ->setOptions(['isPhpEnabled' => true]);
            $fileName = "reimbursement_{$reimbursement->employee->name}_" . $reimbursement->id . ".pdf";
            $filePath = "{$tempFolder}/{$fileName}";
            Storage::disk('public')->put($filePath, $pdf->output());
            $files[] = Storage::disk('public')->path($filePath);
        }

        // Buat ZIP
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        // Bersihkan file sementara
        foreach ($files as $file) {
            @unlink($file);
        }
        Storage::disk('public')->deleteDirectory($tempFolder);

        \Log::info('Finance reimbursement bulk export completed.', [
            'user_id' => Auth::id(),
            'count' => $reimbursements->count(),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        // Return download
        return response()->download($zipPath)->deleteFileAfterSend(true);
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

        $isTeamLeader = $user->userHasRole('team-leader');

        // Only allow deleting if the reimbursement is still pending
        if (($isTeamLeader && $reimbursement->status_2 !== 'pending') || (!$isTeamLeader && $reimbursement->status_1 !== 'pending')) {
            return redirect()->route('finance.reimbursements.show', $reimbursement->id)
                ->with('error', 'You cannot delete a reimbursement request that has already been processed.');
        }

        if ($reimbursement->invoice_path) {
            Storage::disk('public')->delete($reimbursement->invoice_path);
        }

        if (\App\Models\ApprovalLink::where('model_id', $reimbursement->id)->where('model_type', get_class($reimbursement))->exists()) {
            \App\Models\ApprovalLink::where('model_id', $reimbursement->id)->where('model_type', get_class($reimbursement))->delete();
        }

        $reimbursement->delete();

        return redirect()->route('finance.reimbursements.index')
            ->with('success', 'Reimbursement request deleted successfully.');
    }
}


