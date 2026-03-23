<?php

namespace Database\Seeders;

use App\Enums\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('roles')->exists()) {
            DB::table('roles')->truncate();
        }

        $roles = Roles::values();

        foreach ($roles as $role) {
            if (!DB::table('roles')->where('name', $role)->exists()) { // Jika role belum ada, maka insert
                DB::table('roles')->insert([
                    'name' => $role,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        $this->command->info('Roles berhasil dibuat: ' . implode(', ', $roles));
    }
}
