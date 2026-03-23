<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->timestamp('approved_date')->nullable()->after('updated_at');
            $table->timestamp('rejected_date')->nullable()->after('approved_date');
            $table->index('approved_date');
            $table->index('rejected_date');
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->timestamp('approved_date')->nullable()->after('updated_at');
            $table->timestamp('rejected_date')->nullable()->after('approved_date');
            $table->index('approved_date');
            $table->index('rejected_date');
        });

        Schema::table('official_travels', function (Blueprint $table) {
            $table->timestamp('approved_date')->nullable()->after('updated_at');
            $table->timestamp('rejected_date')->nullable()->after('approved_date');
            $table->index('approved_date');
            $table->index('rejected_date');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->timestamp('approved_date')->nullable()->after('updated_at');
            $table->timestamp('rejected_date')->nullable()->after('approved_date');
            $table->index('approved_date');
            $table->index('rejected_date');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropIndex(['approved_date']);
            $table->dropIndex(['rejected_date']);
            $table->dropColumn(['approved_date', 'rejected_date']);
        });

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropIndex(['approved_date']);
            $table->dropIndex(['rejected_date']);
            $table->dropColumn(['approved_date', 'rejected_date']);
        });

        Schema::table('official_travels', function (Blueprint $table) {
            $table->dropIndex(['approved_date']);
            $table->dropIndex(['rejected_date']);
            $table->dropColumn(['approved_date', 'rejected_date']);
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex(['approved_date']);
            $table->dropIndex(['rejected_date']);
            $table->dropColumn(['approved_date', 'rejected_date']);
        });
    }
};
