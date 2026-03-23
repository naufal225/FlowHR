<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setCustomerNullable(false);
    }

    public function down(): void
    {
        $this->setCustomerNullable(true);
    }

    private function setCustomerNullable(bool $nullable): void
    {
        $driver = DB::getDriverName();
        $tables = ['reimbursements', 'overtimes', 'official_travels'];

        foreach ($tables as $table) {
            if ($driver === 'mysql') {
                $nullSql = $nullable ? 'NULL' : 'NOT NULL';
                DB::statement("ALTER TABLE `{$table}` MODIFY `customer` VARCHAR(255) {$nullSql}");
                continue;
            }

            if ($driver === 'pgsql') {
                $nullSql = $nullable ? 'DROP NOT NULL' : 'SET NOT NULL';
                DB::statement("ALTER TABLE {$table} ALTER COLUMN customer {$nullSql}");
            }
        }
    }
};
