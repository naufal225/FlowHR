<?php

namespace App\Http\Controllers\FinanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\Roles;
use App\Helpers\CostSettingsHelper;
use App\TypeRequest;
use App\Models\Overtime;
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

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // --- Query untuk "Your Overtimes"
        $yourOvertimesQuery = Overtime::with(['employee', 'approver1', 'approver2'])
            ->where('employee_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $yourOvertimesQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $yourOvertimesQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $yourOvertimesQuery->where(function ($q) use ($status) {
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

        $yourOvertimes = $yourOvertimesQuery
            ->paginate(5, ['*'], 'your_page')
            ->withQueryString();

        // --- Query untuk "All Overtimes Done (Marked Down)"
        $allOvertimesDoneQuery = Overtime::with(['employee', 'approver1', 'approver2'])
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->where('marked_down', true)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $allOvertimesDoneQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allOvertimesDoneQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allOvertimesDone = $allOvertimesDoneQuery
            ->paginate(5, ['*'], 'all_page_done')
            ->withQueryString();

        // --- Query untuk "All Overtimes Not Marked"
        $allOvertimesQuery = Overtime::with(['employee', 'approver1', 'approver2'])
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->where('marked_down', false)
            ->orderBy('created_at', 'asc');

        if ($request->filled('from_date')) {
            $allOvertimesQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allOvertimesQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allOvertimes = $allOvertimesQuery
            ->paginate(5, ['*'], 'all_page')
            ->withQueryString();

        // --- Statistik
        $dataAll = Overtime::where('status_1', 'approved')
            ->where('status_2', 'approved');

        $totalRequests = $dataAll->count();
        $approvedRequests = (clone $dataAll)->count(); // semua yg masuk sini pasti approved
        $markedRequests = (clone $dataAll)->where('marked_down', true)->count();
        $totalAllNoMark = (clone $dataAll)->where('marked_down', false)->count();

        // Statistik untuk overtimes milik user
        $totalYoursRequests = (clone $yourOvertimesQuery)->count();
        $approvedYoursRequests = (clone $yourOvertimesQuery)
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->count();
        $rejectedYoursRequests = (clone $yourOvertimesQuery)->where(function ($q) {
            $q->where('status_1', 'rejected')
                ->orWhere('status_2', 'rejected');
        })->count();
        $pendingYoursRequests = $totalYoursRequests - $approvedYoursRequests - $rejectedYoursRequests;

        // --- Manager
        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        // $allOvertimes->fresh();

        return view('Finance.overtimes.overtime-show', compact(
            'yourOvertimes',
            'allOvertimes',
            'allOvertimesDone',
            'totalRequests',
            'approvedRequests',
            'markedRequests',
            'totalAllNoMark',
            'totalYoursRequests',
            'pendingYoursRequests',
            'approvedYoursRequests',
            'rejectedYoursRequests',
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
        return view('Finance.overtimes.overtime-request', compact('approvers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer' => 'required',
            'date_start' => 'required|date_format:Y-m-d\TH:i',
            'date_end' => 'required|date_format:Y-m-d\TH:i|after:date_start',
        ], [
            'customer.required' => 'Customer harus diisi.',
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_start.date_format' => 'Format Tanggal/Waktu Mulai tidak valid.',
            'date_end.required' => 'Tanggal/Waktu Akhir harus diisi.',
            'date_end.date_format' => 'Format Tanggal/Waktu Akhir tidak valid.',
            'date_end.after' => 'Tanggal/Waktu Akhir harus setelah Tanggal/Waktu Mulai.',
        ]);

        // Parsing waktu input
        $start = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_start, 'Asia/Jakarta');
        $end = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_end, 'Asia/Jakarta');

        if ($start->isToday() && $start->lt(Carbon::today()->setTime(17, 0))) {
            return back()->withErrors([
                'date_start' => 'Jika tanggal mulai adalah hari ini, maka waktu mulai harus setelah jam 17:00.'
            ])->withInput();
        }

        // Hitung langsung dari date_start
        $overtimeMinutes = $start->diffInMinutes($end);
        $overtimeHours = $overtimeMinutes / 60;

        if ($overtimeHours < 0.5) {
            return back()->withErrors(['date_end' => 'Minimum overtime is 0.5 hours. Please adjust your end time.']);
        }

        $hours = floor($overtimeMinutes / 60);
        $minutes = $overtimeMinutes % 60;

        DB::transaction(function () use ($start, $end, $overtimeMinutes, $hours, $minutes, $request) {
            $overtime = new Overtime();
            $overtime->employee_id = Auth::id();
            $overtime->customer = $request->customer;
            $overtime->date_start = $start;
            $overtime->date_end = $end;
            // Hitung biaya overtime
            $costPerHour = (int) CostSettingsHelper::get('OVERTIME_COSTS', 25000);
            $bonusCost = (int) CostSettingsHelper::get('OVERTIME_BONUS_COSTS', 30000);

            $baseTotal = $hours * $costPerHour;

            // Hitung bonus tiap 24 jam
            $bonusMultiplier = intdiv($hours, 24);
            $bonusTotal = $bonusMultiplier * $bonusCost;

            $totalOvertime = $baseTotal + $bonusTotal;

            $overtime->total = $totalOvertime;

            $submitter = Auth::user();
            $isTeamLeader = $submitter->userHasRole('team-leader');
            $isManager = $submitter->userHasRole('manager');

            if ($isManager) {
                $overtime->status_1 = 'approved';
                $overtime->status_2 = 'approved';
                $overtime->approver_1_id = $submitter->id;
                $overtime->approver_2_id = $submitter->id;
                $overtime->approved_date = now();
            } elseif ($isTeamLeader) {
                $overtime->status_1 = 'approved';
                $overtime->status_2 = 'pending';
                $overtime->approver_1_id = $submitter->id;
            } else {
                $overtime->status_1 = 'pending';
                $overtime->status_2 = 'pending';
            }

            $overtime->save();

            $token = null;

            if ($isManager) {
                return;
            } elseif ($isTeamLeader) {
                // --- Jika leader, langsung kirim ke Manager (level 2)
                $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();

                if ($manager) {
                    $token = \Illuminate\Support\Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($overtime),   // App\Models\overtime
                        'model_id' => $overtime->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2, // level 2 berarti arahnya ke manager
                        'scope' => 'both',             // boleh approve & reject
                        'token' => hash('sha256', $token), // simpan hash, kirim raw
                        'expires_at' => now()->addDays(3),  // masa berlaku
                    ]);
                }

                DB::afterCommit(function () use ($overtime, $token) {
                    $fresh = $overtime->fresh();
                    event(new \App\Events\OvertimeLevelAdvanced(
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
                        )
                    );
                });

            } else {
                // --- Kalau bukan leader, jalur normal ke approver (team lead)
                if ($overtime->approver) {
                    $token = \Illuminate\Support\Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($overtime),   // App\Models\overtime
                        'model_id' => $overtime->id,
                        'approver_user_id' => $overtime->approver->id,
                        'level' => 1, // level 1 berarti arahnya ke team lead
                        'scope' => 'both',             // boleh approve & reject
                        'token' => hash('sha256', $token), // simpan hash, kirim raw
                        'expires_at' => now()->addDays(3),  // masa berlaku
                    ]);

                }

                DB::afterCommit(function () use ($overtime, $request, $token) {
                    $fresh = $overtime->fresh(); // ambil ulang (punya created_at dll)
                    // dd("jalan");
                    event(new \App\Events\OvertimeSubmitted($fresh, Auth::user()->division_id));

                    // Kalau tidak ada approver atau token, jangan kirim email
                    if (!$fresh || !$fresh->approver || !$token) {
                        return;
                    }

                    $linkTanggapan = route('public.approval.show', $token);

                    Mail::to($overtime->approver->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $overtime->approver->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                        )
                    );
                });
            }
        });

        return redirect()->route('finance.overtimes.index')
            ->with('success', 'Overtime submitted. Total: ' . $hours . ' hours ' . $minutes . ' minutes');
    }

    /**
     * Display the specified resource.
     */
    public function show(Overtime $overtime)
    {
        $overtime->load(['employee', 'approver']);
        return view('Finance.overtimes.overtime-detail', compact('overtime'));
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

        $isTeamLeader = $user->userHasRole('team-leader');

        if (($isTeamLeader && $overtime->status_2 !== 'pending') || (!$isTeamLeader && $overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending')) {
            return redirect()->route('finance.overtimes.show', $overtime->id)
                ->with('error', 'You cannot edit an overtime request that has already been processed.');
        }

        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();

        return view('Finance.overtimes.overtime-edit', compact('overtime', 'approvers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Overtime $overtime)
    {
        $user = Auth::user();

        if ($user->id !== (int) $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $isTeamLeader = $user->userHasRole('team-leader');

        if (($isTeamLeader && $overtime->status_2 !== 'pending') || (!$isTeamLeader && $overtime->status_1 !== 'pending' || $overtime->status_2 !== 'pending')) {
            return redirect()->route('finance.overtimes.show', $overtime->id)
                ->with('error', 'You cannot update an overtime request that has already been processed.');
        }

        $request->validate([
            'customer' => 'required',
            'date_start' => 'required|date_format:Y-m-d\TH:i',
            'date_end' => 'required|date_format:Y-m-d\TH:i|after:date_start',
        ], [
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_start.date_format' => 'Format Tanggal/Waktu Mulai tidak valid.',
            'date_end.required' => 'Tanggal/Waktu Akhir harus diisi.',
            'date_end.date_format' => 'Format Tanggal/Waktu Akhir tidak valid.',
            'date_end.after' => 'Tanggal/Waktu Akhir harus setelah Tanggal/Waktu Mulai.',
        ]);

        $start = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_start, 'Asia/Jakarta');
        $end = Carbon::createFromFormat('Y-m-d\TH:i', $request->date_end, 'Asia/Jakarta');

        if ($start->isToday() && $start->lt(Carbon::today()->setTime(17, 0))) {
            return back()->withErrors([
                'date_start' => 'Jika tanggal mulai adalah hari ini, maka waktu mulai harus setelah jam 17:00.'
            ])->withInput();
        }

        $overtimeMinutes = $start->diffInMinutes($end);
        $overtimeHours = $overtimeMinutes / 60;

        $hours = floor($overtimeMinutes / 60);
        $minutes = $overtimeMinutes % 60;

        if ($overtimeHours < 0.5) {
            return back()->withErrors(['date_end' => 'Minimum overtime is 0.5 hours. Please adjust your end time.']);
        }

        // Simpan data
        $overtime->customer = $request->customer;
        $overtime->date_start = $request->date_start;
        $overtime->date_end = $request->date_end;
        // Hitung biaya overtime
        $costPerHour = (int) CostSettingsHelper::get('OVERTIME_COSTS', 25000);
        $bonusCost = (int) CostSettingsHelper::get('OVERTIME_BONUS_COSTS', 30000);
        $baseTotal = $hours * $costPerHour;
        // Hitung bonus tiap 24 jam
        $bonusMultiplier = intdiv($hours, 24);
        $bonusTotal = $bonusMultiplier * $bonusCost;
        $totalOvertime = $baseTotal + $bonusTotal;
        $overtime->total = $totalOvertime;

        // Reset status dan catatan

        $submitter = Auth::user();
        $isTeamLeader = $submitter->userHasRole('team-leader');
        $isManager = $submitter->userHasRole('manager');

        if ($isManager) {
            $overtime->status_1 = 'approved';
            $overtime->status_2 = 'approved';
            $overtime->approver_1_id = $submitter->id;
            $overtime->approver_2_id = $submitter->id;
            $overtime->approved_date = now();
        } elseif ($isTeamLeader) {
            $overtime->status_1 = 'approved';
            $overtime->status_2 = 'pending';
            $overtime->approver_1_id = $submitter->id;
            $overtime->approver_2_id = null;
            $overtime->approved_date = null;
        } else {
            $overtime->status_1 = 'pending';
            $overtime->status_2 = 'pending';
            $overtime->approver_1_id = null;
            $overtime->approver_2_id = null;
            $overtime->approved_date = null;
        }

        $overtime->note_1 = NULL;
        $overtime->note_2 = NULL;
        $overtime->save();

        $token = null;

        if ($isManager) {
            return redirect()->route('finance.overtimes.index')
                ->with('success', 'Overtime request updated successfully. Total overtime: ' . $overtimeHours . ' hours ' . $overtimeMinutes . ' minutes');
        } elseif ($isTeamLeader) {
            // --- Jika leader, langsung kirim ke Manager (level 2)
            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();

            if ($manager) {
                $token = \Illuminate\Support\Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($overtime),   // App\Models\overtime
                    'model_id' => $overtime->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2, // level 2 berarti arahnya ke manager
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);
            }

            DB::afterCommit(function () use ($overtime, $token) {
                $fresh = $overtime->fresh();
                event(new \App\Events\OvertimeLevelAdvanced(
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
                    )
                );
            });

        } else {
            // --- Kalau bukan leader, jalur normal ke approver (team lead)
            if ($overtime->approver) {
                $token = \Illuminate\Support\Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($overtime),   // App\Models\overtime
                    'model_id' => $overtime->id,
                    'approver_user_id' => $overtime->approver->id,
                    'level' => 1, // level 1 berarti arahnya ke team lead
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);
            }

            DB::afterCommit(function () use ($overtime, $request, $token) {
                $fresh = $overtime->fresh(); // ambil ulang (punya created_at dll)
                // dd("jalan");
                event(new \App\Events\OvertimeSubmitted($fresh, Auth::user()->division_id));

                // Kalau tidak ada approver atau token, jangan kirim email
                if (!$fresh || !$fresh->approver || !$token) {
                    return;
                }

                $linkTanggapan = route('public.approval.show', $token);

                Mail::to($overtime->approver->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: Auth::user()->name,
                        namaApprover: $overtime->approver->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: Auth::user()->email,
                    )
                );
            });
        }

        return redirect()->route('finance.overtimes.index')
            ->with('success', 'Overtime request updated successfully. Total overtime: ' . $overtimeHours . ' hours ' . $overtimeMinutes . ' minutes');
    }

    /**
     * Mark selected overtimes as done (marked_down = true).
     */

    public function markedDone(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        try {
            $updated = Overtime::whereIn('id', $ids)
                ->where('status_1', 'approved')
                ->where('status_2', 'approved')
                ->where('marked_down', false)
                ->update([
                    'marked_down' => true,
                    'locked_by' => null,
                    'locked_at' => null,
                ]);

            if ($updated < 1) {
                throw new Exception('No overtimes available to mark as done.');
            }

            return redirect()
                ->route('finance.overtimes.index')
                ->with('success', $updated . ' overtime request(s) marked as done.');
        } catch (Exception $e) {
            return redirect()
                ->route('finance.overtimes.index')
                ->with('error', 'Failed: ' . $e->getMessage());
        }
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

        $query = Overtime::with('employee')->where('status_1', 'approved')->where('status_2', 'approved')->where('marked_down', true);

        $query->where(function ($q) use ($dateFrom, $dateTo) {
            $q->whereDate('date_start', '<=', $dateTo->toDateString())
                ->whereDate('date_end', '>=', $dateFrom->toDateString());
        });

        $overtimes = $query->get();

        if ($overtimes->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk filter tersebut.');
        }

        if ($overtimes->count() > 300) {
            return back()->with('error', 'Export limit exceeded. Maximum 300 records per request.');
        }

        $zipFileName = 'OvertimeRequests_' . Carbon::now()->format('YmdHis') . '.zip';
        $zipPath = Storage::disk('public')->path($zipFileName);

        // Folder sementara untuk menyimpan PDF
        $tempFolder = 'temp_overtimes';
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder);
        }

        $files = [];

        foreach ($overtimes as $overtime) {
            $pdf = Pdf::loadView('Finance.overtimes.pdf', compact('overtime'))
                ->setOptions(['isPhpEnabled' => true]);
            $fileName = "overtime_{$overtime->employee->name}_" . $overtime->id . ".pdf";
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

        \Log::info('Finance overtime bulk export completed.', [
            'user_id' => Auth::id(),
            'count' => $overtimes->count(),
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
    public function destroy(Overtime $overtime)
    {
        $user = Auth::user();
        if ($user->id !== $overtime->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $isTeamLeader = $user->userHasRole('team-leader');

        if (($isTeamLeader && $overtime->status_2 !== 'pending') || (!$isTeamLeader && $overtime->status_1 !== 'pending')) {
            return redirect()->route('finance.overtimes.show', $overtime->id)
                ->with('error', 'You cannot delete an overtime request that has already been processed.');
        }

        if (\App\Models\ApprovalLink::where('model_id', $overtime->id)->where('model_type', get_class($overtime))->exists()) {
            \App\Models\ApprovalLink::where('model_id', $overtime->id)->where('model_type', get_class($overtime))->delete();
        }

        $overtime->delete();

        return redirect()->route('finance.overtimes.index')
            ->with('success', 'Overtime request deleted successfully.');
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Overtime $overtime)
    {
        $pdf = Pdf::loadView('Finance.overtimes.pdf', compact('overtime'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('overtime-details-finance.pdf');
    }
}
