<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('office_location_id')
                ->constrained('office_locations')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('attendance_qr_token_id')
                ->nullable()
                ->constrained('attendance_qr_tokens')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('overtime_id')
                ->nullable()
                ->constrained('overtimes')
                ->nullOnDelete();

            $table->date('work_date');

            // CHECK-IN
            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy_meter', 8, 2)->nullable();
            $table->timestamp('check_in_recorded_at')->nullable();

            // none | on_time | late
            $table->string('check_in_status', 20)->default('none');

            // CHECK-OUT
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy_meter', 8, 2)->nullable();
            $table->timestamp('check_out_recorded_at')->nullable();

            // none | normal | early_leave
            $table->string('check_out_status', 20)->default('none');

            // ongoing | complete | incomplete
            $table->string('record_status', 20)->default('ongoing');

            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->boolean('is_suspicious')->default(false);
            $table->text('suspicious_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Satu user hanya boleh punya satu attendance per hari kerja
            $table->unique(['user_id', 'work_date'], 'attendances_user_work_date_unique');

            $table->index(['work_date'], 'attendances_work_date_idx');
            $table->index(['office_location_id', 'work_date'], 'attendances_office_work_date_idx');
            $table->index(['record_status'], 'attendances_record_status_idx');
            $table->index(['check_in_status'], 'attendances_check_in_status_idx');
            $table->index(['check_out_status'], 'attendances_check_out_status_idx');
            $table->index(['is_suspicious'], 'attendances_is_suspicious_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
