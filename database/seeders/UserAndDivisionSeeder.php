<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Division;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserAndDivisionSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan FK check
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('role_user')->truncate();
        User::truncate();
        Division::truncate();

        // Nyalakan lagi
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Ambil role IDs
        $roleIds = Role::pluck('id', 'name');

        // Data divisi & anggota
        $divisions = [
            'Management' => [
                'leader' => 'Cholid',
                'members' => [
                    'Cholid',
                    'Lulu Andriani',
                    'Triyana Mulayawan',
                    'Sapta Hidayat Guntur',
                    'Rori April',
                    'Aldy Wismawan',
                    'Hendrik',
                    'M Dimas Satria',
                    'Arif Syafii',
                    'Akbar',
                    'Stevano',
                    'Ardita Widya Amanda',
                ],
            ],
            'Teknikal' => [
                'leader' => 'Akbar',
                'members' => [
                    'Akbar',
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

        // Buat semua user dulu
        $users = [];
        foreach ($divisions as $divisionName => $data) {
            foreach ($data['members'] as $name) {
                if (!isset($users[$name])) {
                    $email = strtolower(str_replace(' ', '', $name)) . '@yaztech.co.id';

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'url_profile' => null,
                    ]);

                    // Default: semua Employee
                    $roles = [$roleIds['employee']];

                    // Role spesial
                    if (strtolower($name) === 'cholid') {
                        $roles[] = $roleIds['approver'];
                        $roles[] = $roleIds['manager'];
                    }

                    if (strtolower($name) === 'akbar') {
                        $roles[] = $roleIds['approver'];
                        $roles[] = $roleIds['admin'];
                        $roles[] = $roleIds['superAdmin'];
                        $roles[] = $roleIds['manager'];
                        $roles[] = $roleIds['finance'];
                    }

                    if (strtolower($name) === 'stevano') {
                        $roles[] = $roleIds['approver'];
                    }

                    $user->roles()->attach(array_unique($roles));
                    $users[$name] = $user;
                }
            }
        }

        // Buat divisi
        foreach ($divisions as $divisionName => $data) {
            $leader = $users[$data['leader']];

            $division = Division::create([
                'name' => $divisionName,
                'leader_id' => $leader->id,
            ]);

            // Tambahkan user ke divisi
            foreach ($data['members'] as $name) {
                $users[$name]->division_id = $division->id;
                $users[$name]->save();
            }
        }

        $this->command->info('Users, roles, dan divisions berhasil dibuat sesuai tabel.');
    }
}
