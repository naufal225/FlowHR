<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['reimbursements', 'overtimes', 'official_travels'];

        foreach ($tables as $table) {
            DB::table($table)
                ->where(function ($query) {
                    $query->whereNull('customer')
                        ->orWhere('customer', '');
                })
                ->update(['customer' => 'General']);
        }
    }

    public function down(): void
    {
        // Irreversible: previous values are not recoverable after backfill.
    }
};
