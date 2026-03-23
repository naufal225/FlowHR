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
        Schema::create('approval_links', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');        // App\Models\Leave, Reimbursement, dst (polymorphic)
            $table->unsignedBigInteger('model_id');
            $table->foreignId('approver_user_id')->nullable()->constrained('users', 'id')->nullOnDelete(); // manager/team lead yang dituju
            $table->unsignedTinyInteger('level'); // 1 = team lead, 2 = manager
            $table->string('scope')->default('both'); // 'approve','reject','both'
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('used_ip')->nullable();
            $table->text('used_ua')->nullable();
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_links');
    }
};
