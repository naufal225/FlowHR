<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\Roles;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call(CostSettingsSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserAndDivisionSeeder::class);
        $this->call([
            ReimbursementSeeder::class,
            OvertimeSeeder::class,
            OfficialTravelSeeder::class,
            LeaveSeeder::class,
        ]);
        $this->call(FeatureSettingsSeeder::class);
    }
}
