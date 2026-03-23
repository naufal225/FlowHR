<?php

namespace App\Http\Controllers\FinanceController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\Roles;
use App\Helpers\CostSettingsHelper;
use App\TypeRequest;
use App\Models\OfficialTravel;
use App\Models\User;
use App\Models\Reimbursement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ApprovalLink;
use App\Models\Role;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Illuminate\Support\Str;
use Exception;

class OfficialTravelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // --- Query untuk "Your Official Travels"
        $yourTravelsQuery = OfficialTravel::with(['employee', 'approver1', 'approver2'])
            ->where('employee_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $yourTravelsQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $yourTravelsQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $yourTravelsQuery->where(function ($q) use ($status) {
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

        $yourTravels = $yourTravelsQuery->paginate(5, ['*'], 'your_page')->withQueryString();

        // --- Query untuk "All Official Travels Done (Marked Down)"
        $allTravelsDoneQuery = OfficialTravel::with(['employee', 'approver1', 'approver2'])
            ->where('status_1', 'approved')
            ->where('status_2', 'approved')
            ->where('marked_down', true)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $allTravelsDoneQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allTravelsDoneQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allTravelsDone = $allTravelsDoneQuery->paginate(5, ['*'], 'all_page_done')->withQueryString();

        // --- Query untuk "All Official Travels Not Marked (lockable)"
        $allTravels = collect();
        DB::transaction(function () use (&$allTravels, $request, $userId) {
            $query = OfficialTravel::with(['employee', 'approver1', 'approver2'])
                ->where('status_1', 'approved')
                ->where('status_2', 'approved')
                ->where('marked_down', false)
                ->where(function ($q) use ($userId) {
                    $q->whereNull('locked_by')
                        ->orWhere(function ($q2) use ($userId) {
                            $q2->whereRaw('DATE_ADD(locked_at, INTERVAL 60 MINUTE) < ?', [now()]);
                        })
                        ->orWhere(function ($q3) use ($userId) {
                            $q3->where('locked_by', $userId)
                                ->whereRaw('DATE_ADD(locked_at, INTERVAL 60 MINUTE) >= ?', [now()]);
                        });
                })
                ->orderBy('created_at', 'asc');

            if (request()->filled('from_date')) {
                $query->where(
                    'date_start',
                    '>=',
                    Carbon::parse(request()->from_date)->startOfDay()->timezone('Asia/Jakarta')
                );
            }

            if (request()->filled('to_date')) {
                $query->where(
                    'date_end',
                    '<=',
                    Carbon::parse(request()->to_date)->endOfDay()->timezone('Asia/Jakarta')
                );
            }

            $lockedIds = $query->limit(5)->lockForUpdate()->pluck('id');

            if ($lockedIds->isNotEmpty()) {
                OfficialTravel::whereIn('id', $lockedIds)
                    ->update([
                        'locked_by' => $userId,
                        'locked_at' => now(),
                    ]);

                // Fetch ulang data yang udah updated!
                $allTravels = OfficialTravel::with(['employee', 'approver1', 'approver2'])
                    ->whereIn('id', $lockedIds)
                    ->get();
            } else {
                $allTravels = collect();
            }
        });

        // --- Statistik
        $dataAll = OfficialTravel::where('status_1', 'approved')
            ->where('status_2', 'approved');

        $totalRequests = (clone $dataAll)->count();
        $approvedRequests = optional((clone $dataAll)->reorder()->withFinalStatusCount()->first())->approved ?? 0;
        $markedRequests = (clone $dataAll)->where('marked_down', true)->count();
        $totalAllNoMark = (clone $dataAll)->where('marked_down', false)->count();

        $countsYours = (clone $yourTravelsQuery)->reorder()->withFinalStatusCount()->first();
        $totalYoursRequests = (clone $yourTravelsQuery)->reorder()->count();
        $pendingYoursRequests = optional($countsYours)->pending ?? 0;
        $approvedYoursRequests = optional($countsYours)->approved ?? 0;
        $rejectedYoursRequests = optional($countsYours)->rejected ?? 0;
        // --- Manager
        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        $allTravels->fresh();

        return view('Finance.travels.travel-show', compact(
            'yourTravels',
            'allTravels',
            'allTravelsDone',
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
        return view('Finance.travels.travel-request', compact('approvers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer' => 'required',
            'date_start' => 'required|date|after_or_equal:today',
            'date_end' => 'required|date|after_or_equal:date_start',
        ], [
            'customer.required' => 'Customer harus diisi.',
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_start.date_format' => 'Format Tanggal/Waktu Mulai tidak valid.',
            'date_start.after' => 'Tanggal/Waktu Mulai harus setelah sekarang.',
            'date_start.after_or_equal' => 'Tanggal/Waktu Mulai harus hari ini atau setelahnya.',
            'date_end.required' => 'Tanggal/Waktu Akhir harus diisi.',
            'date_end.date_format' => 'Format Tanggal/Waktu Akhir tidak valid.',
            'date_end.after' => 'Tanggal/Waktu Akhir harus setelah Tanggal/Waktu Mulai.',
            'date_end.after_or_equal' => 'Tanggal/Waktu Akhir harus hari ini atau setelahnya.',
        ]);

        $start = Carbon::parse($validated['date_start']);
        $end = Carbon::parse($validated['date_end']);

        $totalDays = $start->diffInDays($end) + 1;

        $user = Auth::user();
        $userName = $user->name;
        $userEmail = $user->email;
        $divisionId = $user->division_id;

        // Hitung biaya per hari
        $weekDayCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_DAY', 150000);
        $weekEndCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_END', 225000);

        // Ambil semua holiday dari DB
        $holidayDates = \App\Models\Holiday::pluck('holiday_date')->map(fn($d) => Carbon::parse($d)->toDateString())->toArray();

        $period = CarbonPeriod::create($start, $end);

        $totalCost = 0;
        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();
            $isHoliday = in_array($date->toDateString(), $holidayDates);

            if ($isWeekend || $isHoliday) {
                $totalCost += $weekEndCost;
            } else {
                $totalCost += $weekDayCost;
            }
        }

        DB::transaction(function () use ($request, $start, $end, $totalDays, $user, $userName, $userEmail, $divisionId, $totalCost) {
            $officialTravel = new OfficialTravel();
            $officialTravel->customer = $request->customer;
            $officialTravel->employee_id = Auth::id();
            $officialTravel->date_start = $start;
            $officialTravel->date_end = $end;
            $officialTravel->total = $totalCost;

            // Cek apakah user adalah leader division
            $isLeader = \App\Models\Division::where('leader_id', Auth::id())->exists();

            if ($isLeader) {
                $officialTravel->status_1 = 'approved';
                $officialTravel->status_2 = 'pending';
            } else {
                $officialTravel->status_1 = 'pending';
                $officialTravel->status_2 = 'pending';
            }

            $officialTravel->save();

            $token = null;

            if ($isLeader) {
                // --- Jika leader, langsung kirim ke Manager (level 2)
                $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();

                if ($manager) {
                    $token = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($officialTravel),   // App\Models\OfficialTravel
                        'model_id' => $officialTravel->id,
                        'approver_user_id' => $manager->id,
                        'level' => 2, // level 2 berarti arahnya ke manager
                        'scope' => 'both',             // boleh approve & reject
                        'token' => hash('sha256', $token), // simpan hash, kirim raw
                        'expires_at' => now()->addDays(3),  // masa berlaku
                    ]);
                }

                DB::afterCommit(function () use ($officialTravel, $token) {
                    $fresh = $officialTravel->fresh();
                    $emp = $fresh->employee;
                    $isLeader = $emp && \App\Models\Division::where('leader_id', $emp->id)->exists();
                    $isApprover = $emp && $emp->roles()->where('name', \App\Enums\Roles::Approver->value)->exists();
                    if ($isLeader || $isApprover) {
                        event(new \App\Events\OfficialTravelLevelAdvanced(
                            $fresh,
                            $emp->division_id ?? (Auth::user()->division_id ?? 0),
                            'manager'
                        ));
                    }

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
                if ($officialTravel->approver) {
                    $token = Str::random(48);
                    ApprovalLink::create([
                        'model_type' => get_class($officialTravel),   // App\Models\OfficialTravel
                        'model_id' => $officialTravel->id,
                        'approver_user_id' => $officialTravel->approver->id,
                        'level' => 1, // level 1 berarti arahnya ke team lead
                        'scope' => 'both',             // boleh approve & reject
                        'token' => hash('sha256', $token), // simpan hash, kirim raw
                        'expires_at' => now()->addDays(3),  // masa berlaku
                    ]);

                }

                DB::afterCommit(function () use ($officialTravel, $request, $token) {
                    $fresh = $officialTravel->fresh(); // ambil ulang (punya created_at dll)
                    // dd("jalan");
                    event(new \App\Events\OfficialTravelSubmitted($fresh, Auth::user()->division_id));

                    // Kalau tidak ada approver atau token, jangan kirim email
                    if (!$fresh || !$fresh->approver || !$token) {
                        return;
                    }

                    $linkTanggapan = route('public.approval.show', $token);

                    Mail::to($officialTravel->approver->email)->queue(
                        new \App\Mail\SendMessage(
                            namaPengaju: Auth::user()->name,
                            namaApprover: $officialTravel->approver->name,
                            linkTanggapan: $linkTanggapan,
                            emailPengaju: Auth::user()->email,
                        )
                    );
                });
            }

        });

        return redirect()->route('finance.official-travels.index')
            ->with('success', 'Official travel request submitted successfully. Total days: ' . $totalDays);
    }

    /**
     * Display the specified resource.
     */
    public function show(OfficialTravel $officialTravel)
    {
        $officialTravel->load(['employee', 'approver']);
        return view('Finance.travels.travel-detail', compact('officialTravel'));
    }

    /**
     * Mark selected overtimes as done (marked_down = true).
     */

    public function markedDone(Request $request)
    {
        $ids = $request->input('ids', []);

        try {
            DB::transaction(function () use ($ids) {
                $records = OfficialTravel::whereIn('id', $ids)
                    ->where('marked_down', false)
                    ->where('locked_by', Auth::id())
                    ->lockForUpdate()
                    ->get();

                if ($records->isEmpty()) {
                    throw new Exception('No official travels available to mark as done.');
                }

                foreach ($records as $rec) {
                    $rec->update([
                        'marked_down' => true,
                        'locked_by' => null,
                        'locked_at' => null,
                    ]);
                }
            });

            return redirect()
                ->route('finance.official-travels.index')
                ->with('success', 'Selected official travels marked as done.');
        } catch (Exception $e) {
            return redirect()
                ->route('finance.official-travels.index')
                ->with('error', 'Failed: ' . $e->getMessage());
        }
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

        $isLeader = \App\Models\Division::where('leader_id', $user->id)->exists();

        if (($isLeader && $officialTravel->status_2 !== 'pending') || (!$isLeader && $officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending')) {
            return redirect()->route('finance.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot edit a travel request that has already been processed.');
        }

        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->get();
        return view('Finance.travels.travel-edit', compact('officialTravel', 'approvers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== (int) $officialTravel->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $isLeader = \App\Models\Division::where('leader_id', $user->id)->exists();

        if (($isLeader && $officialTravel->status_2 !== 'pending') || (!$isLeader && $officialTravel->status_1 !== 'pending' || $officialTravel->status_2 !== 'pending')) {
            return redirect()->route('finance.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot update a travel request that has already been processed.');
        }

        $request->validate([
            'customer' => 'required',
            'date_start' => 'required|date|after_or_equal:today',
            'date_end' => 'required|date|after_or_equal:date_start',
        ], [
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_start.date_format' => 'Format Tanggal/Waktu Mulai tidak valid.',
            'date_start.after_or_equal' => 'Tanggal/Waktu Mulai harus hari ini atau setelahnya.',
            'date_end.required' => 'Tanggal/Waktu Akhir harus diisi.',
            'date_end.date_format' => 'Format Tanggal/Waktu Akhir tidak valid.',
            'date_end.after' => 'Tanggal/Waktu Akhir harus setelah Tanggal/Waktu Mulai.',
            'date_end.after_or_equal' => 'Tanggal/Waktu Akhir harus hari ini atau setelahnya.',
            'customer.required' => 'Customer harus diisi.',
        ]);

        // Calculate total days
        $start = Carbon::parse($request->date_start);
        $end = Carbon::parse($request->date_end);

        $totalDays = $start->diffInDays($end) + 1;

        // Hitung biaya per hari
        $weekDayCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_DAY', 150000);
        $weekEndCost = (int) CostSettingsHelper::get('TRAVEL_COSTS_WEEK_END', 225000);

        // Ambil semua holiday dari DB
        $holidayDates = \App\Models\Holiday::pluck('holiday_date')->map(fn($d) => Carbon::parse($d)->toDateString())->toArray();

        $period = CarbonPeriod::create($start, $end);

        $totalCost = 0;
        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();
            $isHoliday = in_array($date->toDateString(), $holidayDates);

            if ($isWeekend || $isHoliday) {
                $totalCost += $weekEndCost;
            } else {
                $totalCost += $weekDayCost;
            }
        }

        $officialTravel->customer = $request->customer;
        $officialTravel->date_start = $request->date_start;
        $officialTravel->date_end = $request->date_end;

        if ($isLeader) {
            $officialTravel->status_1 = 'approved';
            $officialTravel->status_2 = 'pending';
        } else {
            $officialTravel->status_1 = 'pending';
            $officialTravel->status_2 = 'pending';
        }

        $officialTravel->note_1 = NULL;
        $officialTravel->note_2 = NULL;
        $officialTravel->total = $totalCost;
        $officialTravel->save();

        $token = null;

        if ($isLeader) {
            // --- Jika leader, langsung kirim ke Manager (level 2)
            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();

            if ($manager) {
                $token = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($officialTravel),   // App\Models\OfficialTravel
                    'model_id' => $officialTravel->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2, // level 2 berarti arahnya ke manager
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);
            }

            DB::afterCommit(function () use ($officialTravel, $token) {
                $fresh = $officialTravel->fresh();
                $emp = $fresh->employee;
                $isLeader = $emp && \App\Models\Division::where('leader_id', $emp->id)->exists();
                $isApprover = $emp && $emp->roles()->where('name', \App\Enums\Roles::Approver->value)->exists();
                if ($isLeader || $isApprover) {
                    event(new \App\Events\OfficialTravelLevelAdvanced(
                        $fresh,
                        $emp->division_id ?? (Auth::user()->division_id ?? 0),
                        'manager'
                    ));
                }

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
            if ($officialTravel->approver) {
                $token = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($officialTravel),   // App\Models\OfficialTravel
                    'model_id' => $officialTravel->id,
                    'approver_user_id' => $officialTravel->approver->id,
                    'level' => 1, // level 1 berarti arahnya ke team lead
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);

            }

            DB::afterCommit(function () use ($officialTravel, $request, $token) {
                $fresh = $officialTravel->fresh(); // ambil ulang (punya created_at dll)
                // dd("jalan");
                event(new \App\Events\OfficialTravelSubmitted($fresh, Auth::user()->division_id));

                // Kalau tidak ada approver atau token, jangan kirim email
                if (!$fresh || !$fresh->approver || !$token) {
                    return;
                }

                $linkTanggapan = route('public.approval.show', $token);

                Mail::to($officialTravel->approver->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: Auth::user()->name,
                        namaApprover: $officialTravel->approver->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: Auth::user()->email,
                    )
                );
            });
        }

        return redirect()->route('finance.official-travels.show', $officialTravel->id)
            ->with('success', 'Official travel request updated successfully. Total days: ' . $totalDays);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OfficialTravel $officialTravel)
    {
        $user = Auth::user();
        if ($user->id !== $officialTravel->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $isLeader = \App\Models\Division::where('leader_id', $user->id)->exists();

        if (($isLeader && $officialTravel->status_2 !== 'pending') || (!$isLeader && $officialTravel->status_1 !== 'pending')) {
            return redirect()->route('finance.official-travels.show', $officialTravel->id)
                ->with('error', 'You cannot delete a travel request that has already been processed.');
        }

        if (ApprovalLink::where('model_id', $officialTravel->id)->where('model_type', get_class($officialTravel))->exists()) {
            ApprovalLink::where('model_id', $officialTravel->id)->where('model_type', get_class($officialTravel))->delete();
        }

        $officialTravel->delete();

        return redirect()->route('finance.official-travels.index')
            ->with('success', 'Official travel request deleted successfully.');
    }

    /**
     * Bulk export approved requests as PDFs in a ZIP file.
     */
    public function bulkExport(Request $request)
    {
        $dateFrom = $request->input('from_date');
        $dateTo = $request->input('date_to');

        $query = OfficialTravel::with('employee')->where('status_1', 'approved')->where('status_2', 'approved')->where('marked_down', true);

        if ($dateFrom && $dateTo) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereDate('date_start', '<=', $dateTo)
                    ->whereDate('date_end', '>=', $dateFrom);
            });
        }

        $officialTravels = $query->get();

        if ($officialTravels->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk filter tersebut.');
        }

        $zipFileName = 'OfficialTravelsRequests_' . Carbon::now()->format('YmdHis') . '.zip';
        $zipPath = Storage::disk('public')->path($zipFileName);

        // Folder sementara untuk menyimpan PDF
        $tempFolder = 'temp_official_travels';
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder);
        }

        $files = [];

        foreach ($officialTravels as $officialTravel) {
            $pdf = Pdf::loadView('Finance.travels.pdf', compact('officialTravel'))
                ->setOptions(['isPhpEnabled' => true]);
            $fileName = "official_travel_{$officialTravel->employee->name}_" . $officialTravel->id . ".pdf";
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

        // Return download
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(OfficialTravel $officialTravel)
    {
        $pdf = Pdf::loadView('Finance.travels.pdf', compact('officialTravel'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('official-travel-details-finance.pdf');
    }
}
