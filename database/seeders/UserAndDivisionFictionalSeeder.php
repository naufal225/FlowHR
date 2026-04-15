<?php

namespace Database\Seeders;

use App\Enums\Roles;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserAndDivisionFictionalSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_user')->truncate();
        DB::table('users')->truncate();
        DB::table('divisions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $roleIds = Role::query()->pluck('id', 'name');
        $divisions = [
            'Management' => [
                'leader' => 'Raka Pratama',
                'members' => [
                    'Raka Pratama',
                    'Dimas Ardiansyah',
                    'Farhan Nugroho',
                    'Yoga Prabowo',
                    'Bima Saputra',
                    'Naufal Ramadhan',
                    'Rizky Maulana',
                    'Fajar Kurniawan',
                    'Nara Setiawan',
                    'Sinta Maharani',
                    'Clara Anggraini',
                ],
            ],
            'Teknikal' => [
                'leader' => 'Aditia Wirawan',
                'members' => [
                    'Aditia Wirawan',
                    'Rian Kusuma',
                    'Bagas Permana',
                    'Gilang Pratama',
                    'Doni Saputro',
                    'Hafiz Ramadhan',
                    'Tegar Mahendra',
                    'Kevin Alfarizi',
                ],
            ],
            'Sales' => [
                'leader' => 'Maya Lestari',
                'members' => [
                    'Maya Lestari',
                    'Nadia Putri',
                    'Vina Aprillia',
                    'Selvi Mahendra',
                    'Rina Ayu',
                    'Putri Cahyani',
                ],
            ],
        ];

        $specialRoles = [
            'raka pratama' => [Roles::Approver->value, Roles::Manager->value],
            'nara setiawan' => [
                Roles::Approver->value,
                Roles::Admin->value,
                Roles::SuperAdmin->value,
                Roles::Manager->value,
                Roles::Finance->value,
            ],
            'maya lestari' => [Roles::Approver->value],
            'aditia wirawan' => [Roles::Approver->value],
        ];

        $allNames = [];
        foreach ($divisions as $division) {
            $allNames[] = $division['leader'];
            foreach ($division['members'] as $memberName) {
                $allNames[] = $memberName;
            }
        }
        $allNames = array_values(array_unique($allNames));

        $users = [];
        foreach ($allNames as $name) {
            $email = $this->gmailFromName($name);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'division_id' => null,
                'office_location_id' => null,
                'url_profile' => null,
                'is_active' => true,
            ]);

            $roles = [Roles::Employee->value];
            foreach ($specialRoles[Str::lower($name)] ?? [] as $roleName) {
                $roles[] = $roleName;
            }
            $roles = array_values(array_unique($roles));

            $user->roles()->sync(
                collect($roles)
                    ->map(fn (string $roleName) => $roleIds[$roleName] ?? null)
                    ->filter()
                    ->all()
            );

            $users[$name] = $user;
        }

        foreach ($divisions as $divisionName => $data) {
            $leader = $users[$data['leader']];

            $division = Division::query()->create([
                'name' => $divisionName,
                'leader_id' => $leader->id,
            ]);

            foreach ($data['members'] as $name) {
                $users[$name]->division_id = $division->id;
                $users[$name]->save();
            }
        }

        $managementDivisionId = Division::query()->where('name', 'Management')->value('id');
        if ($managementDivisionId !== null) {
            User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', Roles::Admin->value))
                ->update(['division_id' => $managementDivisionId]);
        }

        $this->command->info('Fictional users/divisions seeded dengan domain gmail.com dan admin dari divisi Management.');
    }

    private function gmailFromName(string $name): string
    {
        $local = Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();

        if ($local === '') {
            $local = 'user' . sprintf('%u', crc32($name));
        }

        return $local . '@gmail.com';
    }
}
