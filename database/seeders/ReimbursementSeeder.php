<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Division;

class ReimbursementSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('reimbursements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $secondApprover = User::where('name', 'Akbar')->first();

        $customers = ['PT Nusantara', 'PT Maju Jaya', 'PT Bumi Sejahtera'];

        foreach (User::all() as $user) {
            $leaderId = null;
            if ($user->division_id) {
                $leaderId = Division::where('id', $user->division_id)->value('leader_id');
            }
            $approver1Id = $leaderId ?: $secondApprover?->id; // fallback to Akbar if no division
            $approver2Id = $secondApprover?->id ?: $approver1Id;

            // 1) approved/approved
            DB::table('reimbursements')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date' => Carbon::now()->subDays(10)->toDateString(),
                'total' => 150000,
                'invoice_path' => 'invoices/dummy.pdf',
                'status_1' => 'approved',
                'status_2' => 'approved',
                'note_1' => 'Approved level 1',
                'note_2' => 'Approved level 2',
                'marked_down' => false,
                'customer' => $customers[0],
                'approved_date' => Carbon::now()->subDays(9),
                'rejected_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) pending/pending
            DB::table('reimbursements')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date' => Carbon::now()->subDays(6)->toDateString(),
                'total' => 175000,
                'invoice_path' => 'invoices/dummy.pdf',
                'status_1' => 'pending',
                'status_2' => 'pending',
                'note_1' => null,
                'note_2' => null,
                'marked_down' => false,
                'customer' => $customers[1],
                'approved_date' => null,
                'rejected_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3) rejected/rejected
            DB::table('reimbursements')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date' => Carbon::now()->subDays(3)->toDateString(),
                'total' => 200000,
                'invoice_path' => 'invoices/dummy.pdf',
                'status_1' => 'rejected',
                'status_2' => 'rejected',
                'note_1' => 'Rejected level 1',
                'note_2' => 'Rejected level 2',
                'marked_down' => false,
                'customer' => $customers[2],
                'approved_date' => null,
                'rejected_date' => Carbon::now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Reimbursement data seeded (3 per user).');
    }
}

