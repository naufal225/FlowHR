<?php

namespace App\Services;

use App\Models\Reimbursement;
use App\Models\ApprovalLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ReimbursementService
{
    public function store(array $data): Reimbursement
    {
        return DB::transaction(function () use ($data) {
            $reimbursement = new Reimbursement();
            $reimbursement->employee_id = Auth::id();
            $reimbursement->customer = $data['customer'];
            $reimbursement->reimbursement_type_id = $data['reimbursement_type_id'];
            $reimbursement->total = $data['total'];
            $reimbursement->date = $data['date'];
            $reimbursement->status_1 = 'pending';
            $reimbursement->status_2 = 'pending';

            if (isset($data['invoice_path'])) {
                $path = $data['invoice_path']->store('reimbursement_invoices', 'public');
                $reimbursement->invoice_path = $path;
            }

            $reimbursement->save();

            $fresh = $reimbursement->fresh(); // ambil ulang (punya created_at dll)
            event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));

            // jika ada approver
            if ($reimbursement->approver) {
                $token = Str::random(48);
                ApprovalLink::create([
                    'model_type' => get_class($reimbursement),
                    'model_id' => $reimbursement->id,
                    'approver_user_id' => $reimbursement->approver->id,
                    'level' => 1,
                    'scope' => 'both',
                    'token' => hash('sha256', $token),
                    'expires_at' => now()->addDays(3),
                ]);

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
            }

            return $reimbursement;
        });
    }

    public function update(Reimbursement $reimbursement, array $data): Reimbursement
    {
        if ($reimbursement->status_1 !== 'pending' || $reimbursement->status_2 !== 'pending') {
            throw new \Exception('Reimbursement sudah diproses, tidak bisa diupdate.');
        }

        $reimbursement->customer = $data['customer'];
        $reimbursement->total = $data['total'];
        $reimbursement->date = $data['date'];
        $reimbursement->reimbursement_type_id = $data['reimbursement_type_id'];
        $reimbursement->status_1 = 'pending';
        $reimbursement->status_2 = 'pending';
        $reimbursement->note_1 = null;
        $reimbursement->note_2 = null;

        $fresh = $reimbursement->fresh(); // ambil ulang (punya created_at dll)
        // dd("jalan");
        event(new \App\Events\ReimbursementSubmitted($fresh, Auth::user()->division_id));

        // handle file
        if (isset($data['invoice_path'])) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
            }
            $path = $data['invoice_path']->store('reimbursement_invoices', 'public');
            $reimbursement->invoice_path = $path;
        } elseif (!empty($data['remove_invoice_path']) && $data['remove_invoice_path']) {
            if ($reimbursement->invoice_path) {
                Storage::disk('public')->delete($reimbursement->invoice_path);
                $reimbursement->invoice_path = null;
            }
        }

        $reimbursement->save();

        return $reimbursement;
    }
}
