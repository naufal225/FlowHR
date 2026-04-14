<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_qr_display_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_location_id')
                ->constrained('office_locations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['office_location_id', 'revoked_at'], 'attendance_qr_display_sessions_office_revoked_idx');
            $table->index(['expires_at'], 'attendance_qr_display_sessions_expires_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_qr_display_sessions');
    }
};
