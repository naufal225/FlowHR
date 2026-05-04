<?php

namespace Database\Seeders;

use App\Models\ReimbursementType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReimbursementTypeSeeder extends Seeder
{
    public function run(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        DB::table('reimbursement_types')->truncate();
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $types = [
            'Transport',
            'Makan',
            'Kesehatan',
            'Internet',
            'ATK',
            'Operasional Klien',
        ];

        $now = now();
        foreach ($types as $name) {
            ReimbursementType::query()->create([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Reimbursement types seeded untuk skenario showcase.');
    }
}
