<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];
        $columns = ['approved_date', 'rejected_date'];

        foreach ($tables as $tableName) {
            foreach ($columns as $column) {
                if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                try {
                    Schema::table($tableName, function (Blueprint $table) use ($column) {
                        $table->index($column);
                    });
                } catch (\Throwable $e) {
                    // Ignore if index already exists.
                }
            }
        }
    }

    public function down(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];
        $columns = ['approved_date', 'rejected_date'];

        foreach ($tables as $tableName) {
            foreach ($columns as $column) {
                if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                try {
                    Schema::table($tableName, function (Blueprint $table) use ($column) {
                        $table->dropIndex([$column]);
                    });
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }
            }
        }
    }
};
