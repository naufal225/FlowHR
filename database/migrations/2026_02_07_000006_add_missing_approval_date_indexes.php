<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];
        $columns = ['approved_date', 'rejected_date'];

        foreach ($tables as $tableName) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                if ($this->hasSingleColumnIndex($tableName, $column)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->index($column);
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels', 'leaves'];
        $columns = ['approved_date', 'rejected_date'];

        foreach ($tables as $tableName) {
            foreach ($columns as $column) {
                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                $indexName = $this->getDefaultIndexName($tableName, $column);
                if (! $this->hasIndexByName($tableName, $indexName)) {
                    continue;
                }

                DB::statement(sprintf(
                    'drop index if exists %s',
                    $this->quoteIdentifier($indexName),
                ));
            }
        }
    }

    private function hasSingleColumnIndex(string $tableName, string $column): bool
    {
        $result = DB::selectOne(
            <<<SQL
            select 1
            from pg_class t
            inner join pg_namespace n on n.oid = t.relnamespace
            inner join pg_index i on i.indrelid = t.oid
            inner join pg_class idx on idx.oid = i.indexrelid
            inner join pg_attribute a on a.attrelid = t.oid and a.attnum = any(i.indkey)
            where n.nspname = current_schema()
              and t.relname = ?
              and a.attname = ?
              and i.indnatts = 1
            limit 1
            SQL,
            [$tableName, $column]
        );

        return $result !== null;
    }

    private function hasIndexByName(string $tableName, string $indexName): bool
    {
        $result = DB::selectOne(
            <<<SQL
            select 1
            from pg_class t
            inner join pg_namespace n on n.oid = t.relnamespace
            inner join pg_index i on i.indrelid = t.oid
            inner join pg_class idx on idx.oid = i.indexrelid
            where n.nspname = current_schema()
              and t.relname = ?
              and idx.relname = ?
            limit 1
            SQL,
            [$tableName, $indexName]
        );

        return $result !== null;
    }

    private function getDefaultIndexName(string $tableName, string $column): string
    {
        return strtolower($tableName . '_' . $column . '_index');
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
