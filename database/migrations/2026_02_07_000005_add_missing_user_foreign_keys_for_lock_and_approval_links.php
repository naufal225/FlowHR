<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeInvalidUserRefs('approval_links', 'approver_user_id');
        $this->normalizeInvalidUserRefs('reimbursements', 'locked_by');
        $this->normalizeInvalidUserRefs('overtimes', 'locked_by');
        $this->normalizeInvalidUserRefs('official_travels', 'locked_by');

        $this->addUserForeignKey('approval_links', 'approver_user_id');
        $this->addUserForeignKey('reimbursements', 'locked_by');
        $this->addUserForeignKey('overtimes', 'locked_by');
        $this->addUserForeignKey('official_travels', 'locked_by');
    }

    public function down(): void
    {
        $this->dropUserForeignKey('approval_links', 'approver_user_id');
        $this->dropUserForeignKey('reimbursements', 'locked_by');
        $this->dropUserForeignKey('overtimes', 'locked_by');
        $this->dropUserForeignKey('official_travels', 'locked_by');
    }

    private function normalizeInvalidUserRefs(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($table, $column) {
                $query->select(DB::raw(1))
                    ->from('users')
                    ->whereColumn('users.id', "{$table}.{$column}");
            })
            ->update([$column => null]);
    }

    private function addUserForeignKey(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->foreign($column)->references('id')->on('users')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Ignore if the foreign key already exists for this environment.
        }
    }

    private function dropUserForeignKey(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropForeign([$column]);
            });
        } catch (\Throwable $e) {
            // Ignore if the foreign key does not exist for this environment.
        }
    }
};
