<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('attendance_id')
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamp('requested_check_in_time')->nullable();
            $table->timestamp('requested_check_out_time')->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('reviewer_note')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'attendance_corrections_user_status_idx');
            $table->index(['attendance_id', 'status'], 'attendance_corrections_attendance_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
