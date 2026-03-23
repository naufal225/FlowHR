<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('feature_settings')->insert([
            [
                'feature_name' => 'cuti',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_name' => 'reimbursement',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_name' => 'overtime',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'feature_name' => 'perjalanan_dinas',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
