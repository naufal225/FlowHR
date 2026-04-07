<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class OfficeLocationAndAkbarSeeder extends Seeder
{
    public function run(): void
    {
        $office = OfficeLocation::query()->updateOrCreate(
            ['code' => 'JAKSEL-01'],
            [
                'name' => 'Kantor Jaksel',
                'address' => 'Jakarta Selatan',
                'latitude' => -6.2614920,
                'longitude' => 106.8106000,
                'timezone' => 'Asia/Jakarta',
                'radius_meter' => 100,
                'is_active' => true,
            ]
        );

        $user = User::query()
            ->where('email', 'akbar@flowhr.co.id')
            ->first();

        if ($user === null) {
            $this->command->warn('User akbar@flowhr.co.id tidak ditemukan. Office berhasil dibuat, assignment user dilewati.');

            return;
        }

        $user->forceFill([
            'office_location_id' => $office->id,
        ])->save();

        $this->command->info(sprintf(
            'Office "%s" (%s) tersimpan dan user %s di-assign ke office tersebut.',
            $office->name,
            $office->code,
            $user->email,
        ));
    }
}
