<?php

namespace App\Traits;

use App\Models\Leave;
use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Enums\Roles;
use App\Enums\TypeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait HelperController
{
    private function getRecentRequests($userId)
    {
        $user = Auth::user();
        $role = session('active_role');

        // Tentukan prefix route sesuai role
        $routePrefix = match ($role) {
            Roles::Admin->value => 'admin',
            Roles::SuperAdmin->value => 'super-admin',
            Roles::Approver->value => 'approver',
            Roles::Manager->value => 'manager',
            default => 'employee',
        };

        // Helper untuk membangun query dasar per model sesuai role
        $baseQuery = function (string $model) use ($role, $userId, $user) {
            /** @var \Illuminate\Database\Eloquent\Builder $q */
            $q = $model::query();

            switch ($role) {
                case Roles::Approver->value:
                    // Ambil data sesuai divisi leader/approver
                    // Pastikan scope forLeader($leaderId) tersedia di masing-masing Model
                    $q = $q->forLeader($user->id);
                    break;

                case Roles::Manager->value:
                case Roles::SuperAdmin->value:
                case Roles::Admin->value:
                    // Semua data -> tanpa filter
                    break;

                default:
                    // Employee: hanya data miliknya
                    $q = $q->where('employee_id', $userId);
                    break;
            }

            return $q->orderBy('created_at', 'desc')->limit(5);
        };

        // Leaves
        $leaves = $baseQuery(Leave::class)
            ->with(['employee.division'])
            ->get()
            ->map(function ($leave) use ($routePrefix) {
                return [
                    'id' => $leave->id,
                    'type' => TypeRequest::Leaves->value,
                    'title' => 'Leave Request: ' . Carbon::parse($leave->date_start)->format('M d') . ' - ' . Carbon::parse($leave->date_end)->format('M d'),
                    'date' => Carbon::parse($leave->created_at)->format('M d, Y'),
                    'status_1' => $leave->status_1,
                    'url' => route($routePrefix . '.leaves.show', $leave->id),
                    'created_at' => $leave->created_at,
                    'employee_id' => $leave->employee_id,
                    'employee_name' => $leave->employee?->name,
                    'division_name' => $leave->employee?->division?->name,
                    'url_profile' => $leave->employee->url_profile,
                ];
            });

        // Reimbursements
        $reimbursements = $baseQuery(Reimbursement::class)
            ->with(['employee.division'])
            ->get()
            ->map(function ($reimbursement) use ($routePrefix) {
                return [
                    'id' => $reimbursement->id,
                    'type' => TypeRequest::Reimbursements->value,
                    'title' => 'Reimbursement: Rp ' . number_format($reimbursement->total),
                    'date' => Carbon::parse($reimbursement->created_at)->format('M d, Y'),
                    'status_1' => $reimbursement->status_1,
                    'status_2' => $reimbursement->status_2,
                    'url' => route($routePrefix . '.reimbursements.show', $reimbursement->id),
                    'created_at' => $reimbursement->created_at,
                    'employee_id' => $reimbursement->employee_id,
                    'employee_name' => $reimbursement->employee?->name,
                    'division_name' => $reimbursement->employee?->division?->name,
                    'url_profile' => $reimbursement->employee->url_profile,
                ];
            });

        // Overtimes
        $overtimes = $baseQuery(Overtime::class)
            ->with(['employee.division'])
            ->get()
            ->map(function ($overtime) use ($routePrefix) {
                return [
                    'id' => $overtime->id,
                    'type' => TypeRequest::Overtimes->value,
                    'title' => 'Overtime: ' . Carbon::parse($overtime->date_start)->format('M d'),
                    'date' => Carbon::parse($overtime->created_at)->format('M d, Y'),
                    'status_1' => $overtime->status_1,
                    'status_2' => $overtime->status_2,
                    'url' => route($routePrefix . '.overtimes.show', $overtime->id),
                    'created_at' => $overtime->created_at,
                    'employee_id' => $overtime->employee_id,
                    'employee_name' => $overtime->employee?->name,
                    'division_name' => $overtime->employee?->division?->name,
                    'url_profile' => $overtime->employee->url_profile,
                ];
            });

        // Official travels
        $travels = $baseQuery(OfficialTravel::class)
            ->with(['employee.division'])
            ->get()
            ->map(function ($travel) use ($routePrefix) {
                return [
                    'id' => $travel->id,
                    'type' => TypeRequest::Travels->value,
                    'title' => 'Official Travel: ' . Carbon::parse($travel->date_start)->format('M d') . ' - ' . Carbon::parse($travel->date_end)->format('M d'),
                    'date' => Carbon::parse($travel->created_at)->format('M d, Y'),
                    'status_1' => $travel->status_1,
                    'status_2' => $travel->status_2,
                    'url' => route($routePrefix . '.official-travels.show', $travel->id),
                    'created_at' => $travel->created_at,
                    'employee_id' => $travel->employee_id,
                    'employee_name' => $travel->employee?->name,
                    'division_name' => $travel->employee?->division?->name,
                    'url_profile' => $travel->employee->url_profile,
                ];
            });

        // Gabungkan & sortir global (ambil 8 terakhir)
        $allRequests = $leaves
            ->concat($reimbursements)
            ->concat($overtimes)
            ->concat($travels)
            ->sortByDesc('created_at')
            ->take(8)
            ->values()
            ->all();

        return $allRequests;
    }
}
