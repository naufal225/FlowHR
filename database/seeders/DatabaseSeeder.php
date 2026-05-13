<?php

namespace Database\Seeders;

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
        $this->call(UserAndDivisionFictionalSeeder::class);
        $this->call(OfficeLocationAndAkbarSeeder::class);
        $this->call(HolidaySeeder::class);
        $this->call(ReimbursementTypeSeeder::class);
        $this->call([
            OvertimeSeeder::class,
            ReimbursementSeeder::class,
            OfficialTravelSeeder::class,
            LeaveSeeder::class,
        ]);
        $this->call(AttendanceOfficeEmployeeSeeder::class);
        $this->call(FeatureSettingsSeeder::class);
    }
}
