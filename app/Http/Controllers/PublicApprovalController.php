<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalLink;
use App\Models\User;
use App\Enums\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
        $hasSecondLevel = array_key_exists('status_2', $subject->getAttributes());

        DB::transaction(function () use ($validated, $link, $subject, $hasSecondLevel, $request) {
            // Terapkan aturan level (status_1 atau status_2) sesuai desainmu
            if ($link->level === 1) {
                if ($subject->status_1 !== 'pending')
                    abort(422, 'Status 1 sudah final.');
                if ($validated['action'] === 'rejected') {
                    $payload = [
                        'status_1' => 'rejected',
                        'note_1' => $validated['note'] ?? null,
                        'approver_1_id' => $link->approver_user_id,
                        'rejected_date' => now(),
                    ];

                    if ($hasSecondLevel) {
                        $payload['status_2'] = 'rejected';
                        $payload['note_2'] = $validated['note'] ?? null;
                    }

                    $subject->update($payload);
                } else {
                    $subject->update([
                        'status_1' => 'approved',
                        'note_1' => $validated['note'] ?? null,
                        'approver_1_id' => $link->approver_user_id,
                    ]);

                    // Dual-level modules: after approver approval, continue to manager approval.
                    if ($hasSecondLevel) {
                        DB::afterCommit(function () use ($subject) {
                            $freshSubject = $subject->fresh();
                            if (! $freshSubject) {
                                return;
                            }

                            $manager = User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first();
                            if (!$manager) {
                                Log::warning('No manager found for subject id ' . $subject->id . ' type ' . get_class($subject));
                                return;
                            }

                            $base = class_basename($freshSubject);
                            $eventClass = "App\\Events\\{$base}LevelAdvanced";

                            if (!class_exists($eventClass)) {
                                Log::error("Event class not found: {$eventClass}");
                                return;
                            }

                            $divisionId = (int) ($freshSubject->employee?->division_id ?? 0);
                            event(new $eventClass($freshSubject, $divisionId, 'manager'));

                            $rawToken = Str::random(48);
                            ApprovalLink::create([
                                'model_type' => get_class($subject),
                                'model_id' => $subject->id,
                                'approver_user_id' => $manager->id,
                                'level' => 2,
                                'scope' => 'both',
                                'token' => hash('sha256', $rawToken),
                                'expires_at' => now()->addDays(3),
                            ]);

                            $publicUrl = route('public.approval.show', $rawToken);
                            $employeeName = $freshSubject->employee->name ?? 'Unknown';
                            Mail::to($manager->email)->queue(
                                new \App\Mail\SendMessage(
                                    namaPengaju: $employeeName,
                                    namaApprover: $manager->name,
                                    linkTanggapan: $publicUrl,
                                    emailPengaju: $freshSubject->employee->email ?? null
                                )
                            );
                        });
                    }

                }
            } elseif ($link->level === 2) {
                if (! $hasSecondLevel) {
                    abort(422, 'Request ini hanya memiliki 1 level approval.');
                }

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
