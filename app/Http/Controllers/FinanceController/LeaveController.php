<?php

namespace App\Http\Controllers\FinanceController;

use App\Events\LeaveSubmitted;
use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLeaveRequest;
use App\Models\ApprovalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Leave;
use App\Models\User;
use App\Models\Division;
use App\Models\Role;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Str;

class LeaveController extends Controller
{
    public function __construct(private LeaveService $leaveService)
    {
    }
    public function index(Request $request)
    {
        $userId = Auth::id();
        $tahunSekarang = now()->year;

        // --- Query untuk "Your Leaves"
        $yourLeavesQuery = Leave::with(['employee', 'approver1'])
            ->where('employee_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $yourLeavesQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $yourLeavesQuery->where(
                'date_end',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $yourLeavesQuery->where(function ($q) use ($status) {
                if ($status === 'pending' || $status === 'approved' || $status === 'rejected') {
                    $q->where('status_1', $status);
                }
            });
        }

        $yourLeaves = $yourLeavesQuery->paginate(10, ['*'], 'your_page')->withQueryString();

        // --- Query untuk "All Leaves" (approved)
        $allLeavesQuery = Leave::with(['employee', 'approver1'])
            ->where('status_1', 'approved')
            ->orderBy('created_at', 'desc');

        if ($request->filled('from_date')) {
            $allLeavesQuery->where(
                'date_start',
                '>=',
                Carbon::parse($request->from_date)->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if ($request->filled('to_date')) {
            $allLeavesQuery->where(
                'date_start',
                '<=',
                Carbon::parse($request->to_date)->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        $allLeaves = $allLeavesQuery->paginate(10, ['*'], 'all_page')->withQueryString();

        // --- Hitung counts (sebelum paginate!)
        $counts = (clone $allLeavesQuery)->reorder()->withFinalStatusCount()->first();
        $totalRequests = Leave::count();
        $approvedRequests = $counts ? (int) $counts->approved : 0;

        $countsYours = (clone $yourLeavesQuery)->reorder()->withFinalStatusCount()->first();
        $totalYoursRequests = $countsYours ? (int) ($countsYours->total ?? 0) : 0;
        $pendingYoursRequests = $countsYours ? (int) $countsYours->pending : 0;
        $approvedYoursRequests = $countsYours ? (int) $countsYours->approved : 0;
        $rejectedYoursRequests = $countsYours ? (int) $countsYours->rejected : 0;

        // Hitung total cuti
        $tahunSekarang = now()->year;

        // Ambil hari libur dari tabel holidays
        $hariLibur = \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $totalHariCuti = Leave::where('employee_id', Auth::id())
            ->where('status_1', 'approved')
            ->where(function ($q) use ($tahunSekarang) {
                $q->whereYear('date_start', $tahunSekarang)
                    ->orWhereYear('date_end', $tahunSekarang);
            })
            ->get()
            ->sum(function ($cuti) use ($tahunSekarang, $hariLibur) {
                $start = Carbon::parse($cuti->date_start);
                $end = Carbon::parse($cuti->date_end);

                return $this->hitungHariCuti($start, $end, $tahunSekarang, $hariLibur);
            });

        $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        $sisaCuti = $annual - $totalHariCuti;

        $managerRole = Role::where('name', 'manager')->first();

        $manager = User::whereHas('roles', function ($query) use ($managerRole) {
            $query->where('roles.id', $managerRole->id);
        })->first();

        return view('Finance.leaves.leave-show', compact(
            'yourLeaves',
            'allLeaves',
            'totalRequests',
            'approvedRequests',
            'manager',
            'sisaCuti',
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
        $tahunSekarang = now()->year;

        // Ambil daftar hari libur dalam tahun ini
        $hariLibur = \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $holidays = \App\Models\Holiday::pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();


        // Hitung total cuti yang sudah diambil
        $totalHariCuti = (int) Leave::where('employee_id', Auth::id())
            ->with(['employee', 'approver1'])
            ->orderBy('created_at', 'desc')
            ->where('status_1', 'approved')
            ->where(function ($q) use ($tahunSekarang) {
                $q->whereYear('date_start', $tahunSekarang)
                    ->orWhereYear('date_end', $tahunSekarang);
            })
            ->get()
            ->sum(function ($cuti) use ($tahunSekarang, $hariLibur) {
                $start = \Carbon\Carbon::parse($cuti->date_start);
                $end = \Carbon\Carbon::parse($cuti->date_end);

                // Batasi tanggal ke dalam tahun berjalan
                if ($start->year < $tahunSekarang) {
                    $start = \Carbon\Carbon::create($tahunSekarang, 1, 1);
                }
                if ($end->year > $tahunSekarang) {
                    $end = \Carbon\Carbon::create($tahunSekarang, 12, 31);
                }

                $hariCuti = 0;

                while ($start->lte($end)) {
                    // Skip kalau Sabtu/Minggu
                    if ($start->isWeekend()) {
                        $start->addDay();
                        continue;
                    }

                    // Skip kalau hari libur
                    if (in_array($start->format('Y-m-d'), $hariLibur)) {
                        $start->addDay();
                        continue;
                    }

                    $hariCuti++;
                    $start->addDay();
                }

                return $hariCuti;
            });

        $annual = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        $sisaCuti = $annual - $totalHariCuti;

        if ($sisaCuti <= 0) {
            abort(422, 'Sisa cuti tidak cukup.');
        }

        return view('Finance.leaves.leave-request', compact('sisaCuti', 'holidays'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'reason' => 'required|string|max:1000',
        ], [
            'date_start.required' => 'Tanggal/Waktu Mulai harus diisi.',
            'date_start.date_format' => 'Format Tanggal/Waktu Mulai tidak valid.',
            'date_end.required' => 'Tanggal/Waktu Akhir harus diisi.',
            'date_end.date_format' => 'Format Tanggal/Waktu Akhir tidak valid.',
            'date_end.after' => 'Tanggal/Waktu Akhir harus setelah Tanggal/Waktu Mulai.',
            'reason.required' => 'Alasan harus diisi.',
            'reason.string' => 'Alasan harus berupa teks.',
            'reason.max' => 'Alasan tidak boleh lebih dari 1000 karakter.',
        ]);

        $tahunSekarang = now()->year;

        // Ambil semua hari libur di tahun ini
        $hariLibur = \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
            ->pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Hitung cuti yang sudah terpakai
        $totalHariCuti = (int) Leave::where('employee_id', Auth::id())
            ->where('status_1', 'approved')
            ->where(function ($q) use ($tahunSekarang) {
                $q->whereYear('date_start', $tahunSekarang)
                    ->orWhereYear('date_end', $tahunSekarang);
            })
            ->get()
            ->sum(function ($cuti) use ($tahunSekarang, $hariLibur) {
                $start = \Carbon\Carbon::parse($cuti->date_start);
                $end = \Carbon\Carbon::parse($cuti->date_end);
                return $this->hitungHariCuti($start, $end, $tahunSekarang, $hariLibur);
            });

        // Hitung cuti yang sedang diajukan
        $startBaru = \Carbon\Carbon::parse($request->date_start);
        $endBaru = \Carbon\Carbon::parse($request->date_end);
        $hariCutiBaru = $this->hitungHariCuti($startBaru, $endBaru, $tahunSekarang, $hariLibur);

        $jatahTahunan = (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20));
        $sisaCuti = $jatahTahunan - $totalHariCuti;

        if ($hariCutiBaru > $sisaCuti) {
            return back()->with('error', "Sisa cuti hanya {$sisaCuti} hari, tidak bisa ajukan {$hariCutiBaru} hari.");
        }

        DB::transaction(function () use ($request) {
            $leave = new Leave();
            $leave->employee_id = Auth::id();
            $leave->date_start = $request->date_start;
            $leave->date_end = $request->date_end;
            $leave->reason = $request->reason;
            $leave->status_1 = 'pending';
            $leave->save();

            $tokenRaw = null;
            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
            if ($manager) {
                $token = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($leave),   // App\Models\Leave
                    'model_id' => $leave->id,
                    'approver_user_id' => $manager->id,
                    'level' => 2,
                    'scope' => 'both',             // boleh approve & reject
                    'token' => hash('sha256', $token), // simpan hash, kirim raw
                    'expires_at' => now()->addDays(3),  // masa berlaku
                ]);
            }

            // pastikan broadcast SETELAH commit
            DB::afterCommit(function () use ($leave, $request, $tokenRaw, $manager) {
                $fresh = $leave->fresh(); // ambil ulang (punya created_at dll)

                event(new \App\Events\LeaveLevelAdvanced($fresh, $fresh->employee->division_id ?? (Auth::user()->division_id ?? 0), 'manager'));

                if (!$fresh || !$fresh->approver || !$tokenRaw) {
                    return;
                }

                $linkTanggapan = route('public.approval.show', $tokenRaw); // pastikan route param sesuai

                // Gunakan queue
                Mail::to($manager->email)->queue(
                    new \App\Mail\SendMessage(
                        namaPengaju: $leave->employee->name,
                        namaApprover: $manager->name,
                        linkTanggapan: $linkTanggapan,
                        emailPengaju: $leave->employee->email
                    )
                );
            });
        });

        return redirect()->route('finance.leaves.index')
            ->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Count leave days excluding weekends and holidays.
     */
    public function hitungHariCuti($start, $end, $tahunSekarang, $hariLibur)
    {
        // clone supaya tidak merusak object asli
        $start = $start->copy();
        $end = $end->copy();

        if ($start->year < $tahunSekarang) {
            $start = \Carbon\Carbon::create($tahunSekarang, 1, 1);
        }
        if ($end->year > $tahunSekarang) {
            $end = \Carbon\Carbon::create($tahunSekarang, 12, 31);
        }

        $hariCuti = 0;
        while ($start->lte($end)) {
            if ($start->isWeekend()) {
                $start->addDay();
                continue;
            }

            if (in_array($start->format('Y-m-d'), $hariLibur)) {
                $start->addDay();
                continue;
            }

            $hariCuti++;
            $start->addDay();
        }

        return $hariCuti;
    }

    /**
     * Display the specified resource.
     */
    public function show(Leave $leave)
    {
        $leave->load(['employee', 'approver']);
        return view('Finance.leaves.leave-detail', compact('leave'));
    }

    /**
     * Export the specified resource as a PDF.
     */
    public function exportPdf(Leave $leave)
    {
        $pdf = Pdf::loadView('Finance.leaves.pdf', compact('leave'))
            ->setOptions(['isPhpEnabled' => true]);
        return $pdf->download('leave-details-finance.pdf');
    }

    /**
     * Bulk export approved requests as PDFs in a ZIP file.
     */
    public function bulkExport(Request $request)
    {
        $dateFrom = $request->input('from_date');
        $dateTo = $request->input('date_to');

        $query = Leave::with('employee')->where('status_1', 'approved');

        if ($dateFrom && $dateTo) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereDate('date_start', '<=', $dateTo)
                    ->whereDate('date_end', '>=', $dateFrom);
            });
        }

        $leaves = $query->get();

        if ($leaves->isEmpty()) {
            return back()->with('error', 'Tidak ada data untuk filter tersebut.');
        }

        $zipFileName = 'LeaveRequests_' . Carbon::now()->format('YmdHis') . '.zip';
        $zipPath = Storage::disk('public')->path($zipFileName);

        // Folder sementara untuk menyimpan PDF
        $tempFolder = 'temp_leaves';
        if (!Storage::disk('public')->exists($tempFolder)) {
            Storage::disk('public')->makeDirectory($tempFolder);
        }

        $files = [];

        foreach ($leaves as $leave) {
            $pdf = Pdf::loadView('Finance.leaves.pdf', compact('leave'))
                ->setOptions(['isPhpEnabled' => true]);
            $fileName = "leave_{$leave->employee->name}_" . $leave->id . ".pdf";
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
     * Show the form for editing the specified resource.
     */
    public function edit(Leave $leave)
    {
        // Check if the user has permission to edit this leave
        $user = Auth::user();
        if ($user->id !== (int) $leave->employee_id) {
            abort(403, 'Unauthorized action.');
        }

        $holidays = \App\Models\Holiday::pluck('holiday_date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        // Only allow editing if the leave is still pending
        if ($leave->status_1 !== 'pending') {
            return redirect()->route('finance.leaves.show', $leave->id)
                ->with('error', 'You cannot edit a leave request that has already been processed.');
        }

        return view('Finance.leaves.leave-edit', compact('leave', 'holidays'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLeaveRequest $request, Leave $leave)
    {
        try {
            $this->leaveService->update($leave, $request->validated());
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
        return redirect()->route('finance.leaves.show', $leave->id)
            ->with('success', 'Leave request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Leave $leave)
    {
        // Check if the user has permission to delete this leave
        $user = Auth::user();
        if ($user->id !== $leave->employee_id && !$user->hasActiveRole(Roles::Finance->value)) {
            abort(403, 'Unauthorized action.');
        }

        // Only allow deleting if the leave is still pending
        if (($leave->status_1 !== 'pending') && !$user->hasActiveRole(Roles::Finance->value)) {
            return redirect()->route('finance.leaves.show', $leave->id)
                ->with('error', 'You cannot delete a leave request that has already been processed.');
        }

        if (\App\Models\ApprovalLink::where('model_id', $leave->id)->where('model_type', get_class($leave))->exists()) {
            \App\Models\ApprovalLink::where('model_id', $leave->id)->where('model_type', get_class($leave))->delete();
        }

        $leave->delete();

        return redirect()->route('finance.leaves.index')
            ->with('success', 'Leave request deleted successfully.');
    }
}
