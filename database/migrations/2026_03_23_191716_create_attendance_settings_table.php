<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('office_location_id')
                ->constrained('office_locations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->time('work_start_time');
            $table->time('work_end_time');

            $table->unsignedInteger('late_tolerance_minutes')->default(15);
            $table->unsignedInteger('qr_rotation_seconds')->default(30);
            $table->unsignedInteger('min_location_accuracy_meter')->default(50);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['office_location_id', 'is_active'], 'attendance_settings_office_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
