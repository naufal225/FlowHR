<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_id')
                ->nullable()
                ->constrained('attendances')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // contoh:
            // check_in_attempt, check_in_success, check_out_attempt, check_out_success,
            // qr_rejected, location_rejected, duplicate_checkin_attempt
            $table->string('action_type', 50);

            // success | rejected | suspicious
            $table->string('action_status', 20);

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_meter', 8, 2)->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index(['user_id', 'occurred_at'], 'attendance_logs_user_occurred_at_idx');
            $table->index(['attendance_id'], 'attendance_logs_attendance_id_idx');
            $table->index(['action_type'], 'attendance_logs_action_type_idx');
            $table->index(['action_status'], 'attendance_logs_action_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
