<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalLink;
use App\Models\User;
use App\Enums\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use App\Events\LeaveLevelAdvanced;
use App\Events\ReimbursementLevelAdvanced;
use App\Events\OvertimeLevelAdvanced;
use App\Events\OfficialTravelLevelAdvanced;

class PublicApprovalController extends Controller
{
    public function show(string $token)
    {
        $link = ApprovalLink::where('token', hash('sha256', $token))->first();
        abort_if(!$link || !$link->isValid(), 410, 'Link invalid atau kadaluarsa.');

        $subject = $link->subject; // contoh: Leave/OfficialTravel dsb
        // Batasi aksi sesuai scope
        $canApprove = in_array($link->scope, ['approve', 'both']);
        $canReject = in_array($link->scope, ['reject', 'both']);

        $rawToken = $token;

        return view('public-approval.show', compact('link', 'subject', 'canApprove', 'canReject', 'rawToken'));
    }

    public function act(Request $request, string $token)
    {
        $validated = $request->validate([
            'action' => 'required|in:approved,rejected',
            'note' => 'nullable|string'
        ]);

        $link = ApprovalLink::where('token', hash('sha256', $token))->lockForUpdate()->first();
        abort_if(!$link || !$link->isValid(), 410, 'Link invalid atau kadaluarsa.');

        // Cek scope
        if ($validated['action'] === 'approved' && !in_array($link->scope, ['approve', 'both']))
            abort(403);
        if ($validated['action'] === 'rejected' && !in_array($link->scope, ['reject', 'both']))
            abort(403);

        // Ambil subject polymorphic
        $subject = $link->subject;

        DB::transaction(function () use ($validated, $link, $subject, $request) {
            // Terapkan aturan level (status_1 atau status_2) sesuai desainmu
            if ($link->level === 1) {
                if ($subject->status_1 !== 'pending')
                    abort(422, 'Status 1 sudah final.');
                if ($validated['action'] === 'rejected') {
                    $subject->update([
                        'status_1' => 'rejected',
                        'note_1' => $validated['note'] ?? null,
                        'status_2' => 'rejected',
                        'note_2' => $validated['note'] ?? null,
                        'approver_1_id' => $link->approver_user_id,
                        'rejected_date' => now(),
                    ]);
                } else {
                    $subject->update([
                        'status_1' => 'approved',
                        'note_1' => $validated['note'] ?? null,
                        'approver_1_id' => $link->approver_user_id,
                    ]);

                    // Flag untuk mengirim email setelah commit
                    $notifyManager = true;

                    if ($notifyManager) {
                        DB::afterCommit(function () use ($subject) {
                            // 1) Tentukan manager penerima
                            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
                            if (!$manager) {
                                // Tidak ada manager, ya sudah: fail-silent atau log
                                Log::warning('No manager found for subject id ' . $subject->id . ' type ' . get_class($subject));
                                return;
                            }

                            $base = class_basename($subject); // "Leave", "OfficialTravel", "Overtime", "Reimbursement"
                            $eventClass = "App\\Events\\{$base}LevelAdvanced"; // e.g. App\Events\LeaveLevelAdvanced

                            if (!class_exists($eventClass)) {
                                Log::error("Event class not found: {$eventClass}");
                                return;
                            }

                            $divisionId = Auth::user()->division_id; // fallback
                            // pakai fresh biar field (timestamps, relasi) up-to-date
                            $freshSubject = $subject->fresh();

                            $emp = $freshSubject->employee;
                            $isLeader = $emp && \App\Models\Division::where('leader_id', $emp->id)->exists();
                            $isApprover = $emp && $emp->roles()->where('name', \App\Enums\Roles::Approver->value)->exists();
                            if ($isLeader || $isApprover) {
                                event(new $eventClass($freshSubject, ($emp->division_id ?? $divisionId), 'manager'));
                            }

                            // 2) Buat token & simpan hash di DB untuk approval Level 2 (Manager)
                            $rawToken = Str::random(48);
                            ApprovalLink::create([
                                'model_type' => get_class($subject),
                                'model_id' => $subject->id,
                                'approver_user_id' => $manager->id,
                                'level' => 2,            // Manager
                                'scope' => 'both',       // boleh approve/reject
                                'token' => hash('sha256', $rawToken), // simpan HASH
                                'expires_at' => now()->addDays(3),
                            ]);

                            // 3) Buat URL publik untuk manager (pakai RAW token!)
                            $publicUrl = route('public.approval.show', $rawToken);

                            // 4) Susun pesan email
                            $employeeName = $subject->employee->name ?? 'Unknown';
                            $start = $subject->date_start;
                            $end = $subject->date_end;
                            $reason = $subject->reason ?? '-';
                            $pesan = "Terdapat pengajuan perjalanan dinas baru atas nama {$employeeName}.
                                <br> Tanggal Mulai: {$start}
                                <br> Tanggal Selesai: {$end}
                                <br> Alasan: {$reason}";

                            // 5) Kirim email via queue
                            Mail::to($manager->email)->queue(
                                new \App\Mail\SendMessage(
                                    namaPengaju: $employeeName,
                                    namaApprover: $manager->name,
                                    linkTanggapan: $publicUrl,
                                    emailPengaju: $subject->employee->email ?? null
                                )
                            );
                        });
                    }

                }
            } elseif ($link->level === 2) {
                if ($subject->status_1 !== 'approved')
                    abort(422, 'Status 2 hanya setelah status 1 approved.');
                if ($subject->status_2 !== 'pending')
                    abort(422, 'Status 2 sudah final.');
                $subject->update([
                    'status_2' => $validated['action'],
                    'note_2' => $validated['note'] ?? null,
                    'approver_2_id' => $link->approver_user_id,
                    'approved_date' => $validated['action'] === 'approved' ? now() : $subject->approved_date,
                    'rejected_date' => $validated['action'] === 'rejected' ? now() : $subject->rejected_date,
                ]);
            }

            // Burn token
            $link->update([
                'used_at' => now(),
                'used_ip' => $request->ip(),
                'used_ua' => substr($request->userAgent() ?? '', 0, 255),
            ]);
        });

        return view('public-approval.done'); // atau redirect ke halaman sukses statis
    }
}
