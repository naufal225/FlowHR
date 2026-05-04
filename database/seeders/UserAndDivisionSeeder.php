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

class UserAndDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $isMySql = DB::getDriverName() === 'mysql';
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        DB::table('role_user')->truncate();
        DB::table('users')->truncate();
        DB::table('divisions')->truncate();
        if ($isMySql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $roleIds = Role::query()->pluck('id', 'name');
        $divisions = [
            'Management' => [
                'leader' => 'Cholid',
                'members' => [
                    'Cholid',
                    'Triyana Mulayawan',
                    'Sapta Hidayat Guntur',
                    'Rori April',
                    'Aldy Wismawan',
                    'Hendrik',
                    'M Dimas Satria',
                    'Arif Syafii',
                    'Akbar',
                    'Lulu Andriani',
                    'Ardita Widya Amanda',
                ],
            ],
            'Teknikal' => [
                'leader' => 'Iqbal Hekal Vaura',
                'members' => [
                    'Iqbal Hekal Vaura',
                    'Ali',
                    'Dewa Raditya',
                    'Muhammad Wahyudin',
                    'Wisnu Aji Permana',
                    'Ilham Arif Saputra',
                    'Thoriq Muzaki',
                    'Wisnu Hartakusuma',
                ],
            ],
            'Sales' => [
                'leader' => 'Stevano',
                'members' => [
                    'Stevano',
                    'Keanny Rakean',
                    'Anten Rahmith Permatasari',
                    'Najia Salsabila',
                    'Anisa Salmayenti',
                    'Syiva Julaikha',
                ],
            ],
        ];

        $specialRoles = [
            'cholid' => [Roles::Approver->value, Roles::Manager->value],
            'akbar' => [
                Roles::Approver->value,
                Roles::Admin->value,
                Roles::SuperAdmin->value,
                Roles::Manager->value,
                Roles::Finance->value,
            ],
            'stevano' => [Roles::Approver->value],
            'iqbal hekal vaura' => [Roles::Approver->value],
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

        $this->command->info('Showcase users/divisions seeded dengan domain gmail.com dan admin dari divisi Management.');
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
