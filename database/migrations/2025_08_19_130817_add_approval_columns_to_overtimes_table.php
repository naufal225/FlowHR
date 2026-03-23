<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->timestamp('seen_by_approver_at')->nullable()->after('status_2');
            $table->timestamp('seen_by_manager_at')->nullable()->after('seen_by_approver_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropColumn(['seen_by_approver_at','seen_by_manager_at']);
        });
    }
};
