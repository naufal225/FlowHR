<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_qr_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('office_location_id')
                ->constrained('office_locations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('token', 255)->unique();
            $table->timestamp('generated_at');
            $table->timestamp('expired_at');
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['office_location_id', 'is_active'], 'attendance_qr_tokens_office_active_idx');
            $table->index(['expired_at'], 'attendance_qr_tokens_expired_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_qr_tokens');
    }
};
