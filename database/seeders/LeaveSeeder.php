<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Division;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        // Reset table
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('leaves')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $secondApprover = User::where('name', 'Akbar')->first();

        foreach (User::all() as $user) {
            $leaderId = null;
            if ($user->division_id) {
                $leaderId = Division::where('id', $user->division_id)->value('leader_id');
            }
            $approver1Id = $leaderId ?: $secondApprover?->id; // fallback if no division

            // We seed 3 leaves per user with a mix of approved and rejected

            // 1) approved
            DB::table('leaves')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'date_start' => Carbon::now()->subDays(15)->toDateString(),
                'date_end' => Carbon::now()->subDays(13)->toDateString(),
                'reason' => 'Cuti keluarga',
                'status_1' => 'approved',
                'note_1' => 'Disetujui atasan',
                'note_2' => null,
                'approved_date' => Carbon::now()->subDays(12),
                'rejected_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2) rejected
            DB::table('leaves')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'date_start' => Carbon::now()->subDays(9)->toDateString(),
                'date_end' => Carbon::now()->subDays(8)->toDateString(),
                'reason' => 'Cuti pribadi',
                'status_1' => 'rejected',
                'note_1' => 'Tidak dapat disetujui',
                'note_2' => null,
                'approved_date' => null,
                'rejected_date' => Carbon::now()->subDays(8),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3) approved (again)
            DB::table('leaves')->insert([
                'employee_id' => $user->id,
                'approver_1_id' => $approver1Id,
                'date_start' => Carbon::now()->subDays(5)->toDateString(),
                'date_end' => Carbon::now()->subDays(4)->toDateString(),
                'reason' => 'Izin kesehatan',
                'status_1' => 'approved',
                'note_1' => 'OK',
                'note_2' => null,
                'approved_date' => Carbon::now()->subDays(4),
                'rejected_date' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Leave data seeded (3 per user; mix approved and rejected).');
    }
}

