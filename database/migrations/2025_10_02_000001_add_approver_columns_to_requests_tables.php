<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'approver_1_id')) {
                    $table->foreignId('approver_1_id')->nullable()->constrained('users', 'id')->nullOnDelete()->after('employee_id');
                }
                if (!Schema::hasColumn($tableName, 'approver_2_id') && $tableName != 'leaves') {
                    $table->foreignId('approver_2_id')->nullable()->constrained('users', 'id')->nullOnDelete()->after('approver_1_id');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'approver_2_id')) {
                    $table->dropConstrainedForeignId('approver_2_id');
                }
                if (Schema::hasColumn($tableName, 'approver_1_id')) {
                    $table->dropConstrainedForeignId('approver_1_id');
                }
            });
        }
    }
};

