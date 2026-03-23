<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->date('date_start');
            $table->date('date_end');
            $table->longText('reason');
            $table->enum('status_1', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note_1')->nullable();
            $table->text('note_2')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
