<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('holidays')->truncate();

        $holidays = [
            ['start_from' => '2026-01-01', 'end_at' => '2026-01-01', 'name' => 'Tahun Baru Masehi'],
            ['start_from' => '2026-01-28', 'end_at' => '2026-01-28', 'name' => 'Isra Miraj'],
            ['start_from' => '2026-02-17', 'end_at' => '2026-02-17', 'name' => 'Tahun Baru Imlek'],
            ['start_from' => '2026-03-25', 'end_at' => '2026-03-25', 'name' => 'Nyepi'],
            ['start_from' => '2026-03-31', 'end_at' => '2026-04-02', 'name' => 'Libur Idul Fitri'],
            ['start_from' => '2026-04-03', 'end_at' => '2026-04-03', 'name' => 'Cuti Bersama Idul Fitri'],
            ['start_from' => '2026-05-01', 'end_at' => '2026-05-01', 'name' => 'Hari Buruh Internasional'],
            ['start_from' => '2026-05-14', 'end_at' => '2026-05-14', 'name' => 'Kenaikan Isa Almasih'],
            ['start_from' => '2026-06-01', 'end_at' => '2026-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['start_from' => '2026-08-17', 'end_at' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI'],
            ['start_from' => '2026-12-24', 'end_at' => '2026-12-26', 'name' => 'Libur Natal'],
            ['start_from' => '2026-12-31', 'end_at' => '2026-12-31', 'name' => 'Malam Tahun Baru'],
        ];

        $now = now();
        foreach ($holidays as $holiday) {
            Holiday::query()->create([
                'start_from' => $holiday['start_from'],
                'end_at' => $holiday['end_at'],
                'name' => $holiday['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info(sprintf('Holiday showcase data seeded: %d rows.', count($holidays)));
    }
}

