<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->json('resulting_attendance_snapshot')
                ->nullable()
                ->after('original_attendance_snapshot');
            $table->foreignId('applied_by')
                ->nullable()
                ->after('reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('applied_at')
                ->nullable()
                ->after('applied_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('applied_by');
            $table->dropColumn([
                'resulting_attendance_snapshot',
                'applied_at',
            ]);
        });
    }
};
