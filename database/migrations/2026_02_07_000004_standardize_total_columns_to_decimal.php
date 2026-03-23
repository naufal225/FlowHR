<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->alterTotals('DECIMAL(15,2)', 'numeric(15,2)');
    }

    public function down(): void
    {
        $this->alterTotals('BIGINT', 'bigint');
    }

    private function alterTotals(string $mysqlType, string $pgsqlCastType): void
    {
        $driver = DB::getDriverName();
        $tables = ['reimbursements', 'overtimes', 'official_travels'];

        if (!in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'total')) {
                continue;
            }

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `total` {$mysqlType} NOT NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN total TYPE {$pgsqlCastType} USING total::{$pgsqlCastType}");
            }
        }
    }
};
