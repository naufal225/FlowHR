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
        Schema::create('report_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('role_scope', 40);
            $table->string('module', 40);
            $table->string('export_type', 20)->default('summary');
            $table->string('format', 10)->default('pdf');
            $table->json('filters_json');
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('total_items')->default(0);
            $table->string('result_disk', 50)->nullable();
            $table->string('result_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'status']);
            $table->index(['role_scope', 'module', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};

