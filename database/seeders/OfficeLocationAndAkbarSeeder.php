<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeLocationAndAkbarSeeder extends Seeder
{
    public function run(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('office_locations')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            // On PostgreSQL, TRUNCATE uses CASCADE and can wipe users through FK dependency.
            User::query()->whereNotNull('office_location_id')->update(['office_location_id' => null]);
            DB::table('office_locations')->delete();
        }

        $office = OfficeLocation::query()->create([
            'code' => 'BSP1-01',
            'name' => 'Kantor Bumi Sani Permai 1',
            'address' => 'Bumi Sani Permai 1',
            'latitude' => -6.2614920,
            'longitude' => 106.8106000,
            'timezone' => 'Asia/Jakarta',
            'radius_meter' => 100,
            'is_active' => true,
        ]);

        $assignedCount = User::query()
            ->where('is_active', true)
            ->update(['office_location_id' => $office->id]);

        $this->command->info(sprintf(
            'Office "%s" (%s) tersimpan dan %d user aktif di-assign ke office tersebut.',
            $office->name,
            $office->code,
            $assignedCount,
        ));
    }
}
