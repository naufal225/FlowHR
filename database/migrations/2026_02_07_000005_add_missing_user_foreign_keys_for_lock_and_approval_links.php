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
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
        ) {
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
        if (
            ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'id')
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
        ) {
            return;
        }

        if ($this->findForeignConstraintName($table, $column) !== null) {
            return;
        }

        if (! $this->hasCompatibleTypeWithUsersId($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->foreign($column)->references('id')->on('users')->nullOnDelete();
        });
    }

    private function dropUserForeignKey(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $constraintName = $this->findForeignConstraintName($table, $column);
        if ($constraintName === null) {
            return;
        }

        DB::statement(sprintf(
            'alter table %s drop constraint %s',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($constraintName),
        ));
    }

    private function hasCompatibleTypeWithUsersId(string $table, string $column): bool
    {
        $usersIdType = DB::table('information_schema.columns')
            ->select('udt_name')
            ->whereRaw('table_schema = current_schema()')
            ->where('table_name', 'users')
            ->where('column_name', 'id')
            ->value('udt_name');

        $columnType = DB::table('information_schema.columns')
            ->select('udt_name')
            ->whereRaw('table_schema = current_schema()')
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('udt_name');

        return $usersIdType !== null
            && $columnType !== null
            && strtolower((string) $usersIdType) === strtolower((string) $columnType);
    }

    private function findForeignConstraintName(string $table, string $column): ?string
    {
        $result = DB::selectOne(
            <<<SQL
            select c.conname
            from pg_constraint c
            inner join pg_class t on t.oid = c.conrelid
            inner join pg_namespace n on n.oid = t.relnamespace
            inner join pg_attribute a on a.attrelid = t.oid and a.attnum = any(c.conkey)
            where c.contype = 'f'
              and n.nspname = current_schema()
              and t.relname = ?
              and a.attname = ?
            limit 1
            SQL,
            [$table, $column]
        );

        return is_object($result) && isset($result->conname)
            ? (string) $result->conname
            : null;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
