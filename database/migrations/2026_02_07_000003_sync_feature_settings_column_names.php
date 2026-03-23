<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('feature_settings')) {
            return;
        }

        $driver = DB::getDriverName();

        if (Schema::hasColumn('feature_settings', 'nama_fitur') && !Schema::hasColumn('feature_settings', 'feature_name')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `feature_settings` CHANGE `nama_fitur` `feature_name` VARCHAR(255) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE feature_settings RENAME COLUMN nama_fitur TO feature_name');
            }
        }

        if (Schema::hasColumn('feature_settings', 'status') && !Schema::hasColumn('feature_settings', 'is_enabled')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `feature_settings` CHANGE `status` `is_enabled` TINYINT(1) NOT NULL DEFAULT 1');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE feature_settings RENAME COLUMN status TO is_enabled');
                DB::statement('ALTER TABLE feature_settings ALTER COLUMN is_enabled SET DEFAULT true');
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('feature_settings')) {
            return;
        }

        $driver = DB::getDriverName();

        if (Schema::hasColumn('feature_settings', 'feature_name') && !Schema::hasColumn('feature_settings', 'nama_fitur')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `feature_settings` CHANGE `feature_name` `nama_fitur` VARCHAR(255) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE feature_settings RENAME COLUMN feature_name TO nama_fitur');
            }
        }

        if (Schema::hasColumn('feature_settings', 'is_enabled') && !Schema::hasColumn('feature_settings', 'status')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `feature_settings` CHANGE `is_enabled` `status` TINYINT(1) NOT NULL DEFAULT 1');
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE feature_settings RENAME COLUMN is_enabled TO status');
                DB::statement('ALTER TABLE feature_settings ALTER COLUMN status SET DEFAULT true');
            }
        }
    }
};
