<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = [
            ['feature_name' => 'cuti', 'is_enabled' => true],
            ['feature_name' => 'reimbursement', 'is_enabled' => true],
            ['feature_name' => 'overtime', 'is_enabled' => true],
            ['feature_name' => 'perjalanan_dinas', 'is_enabled' => true],
        ];

        foreach ($rows as $row) {
            DB::table('feature_settings')->updateOrInsert(
                ['feature_name' => $row['feature_name']],
                [
                    'is_enabled' => $row['is_enabled'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
