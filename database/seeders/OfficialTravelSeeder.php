<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Division;

class OfficialTravelSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('official_travels')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $secondApprover = User::where('name', 'Akbar')->first();
        $customers = ['PT Nusantara', 'PT Maju Jaya', 'PT Bumi Sejahtera'];

        foreach (User::all() as $user) {
            $leaderId = null;
            if ($user->division_id) {
                $leaderId = Division::where('id', $user->division_id)->value('leader_id');
            }
            $approver1Id = $leaderId ?: $secondApprover?->id; // fallback if no division
            $approver2Id = $secondApprover?->id ?: $approver1Id;

            // 1) approved/approved
            DB::table('official_travels')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date_start' => Carbon::now()->subDays(12)->toDateString(),
                'date_end' => Carbon::now()->subDays(11)->toDateString(),
                'total' => 500000,
                'status_1' => 'approved',
                'status_2' => 'approved',
                'note_1' => 'Approved level 1',
                'note_2' => 'Approved level 2',
                'marked_down' => false,
                'customer' => $customers[0],
                'approved_date' => Carbon::now()->subDays(10),
                'rejected_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) pending/pending
            DB::table('official_travels')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date_start' => Carbon::now()->subDays(7)->toDateString(),
                'date_end' => Carbon::now()->subDays(6)->toDateString(),
                'total' => 450000,
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
            DB::table('official_travels')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'approver_2_id' => $approver2Id,
                'date_start' => Carbon::now()->subDays(4)->toDateString(),
                'date_end' => Carbon::now()->subDays(3)->toDateString(),
                'total' => 400000,
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

        $this->command?->info('Official travel data seeded (3 per user).');
    }
}

