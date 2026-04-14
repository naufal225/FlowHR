<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_qr_display_sessions', function (Blueprint $table) {
            $table->longText('token_encrypted')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_qr_display_sessions', function (Blueprint $table) {
            $table->dropColumn('token_encrypted');
        });
    }
};
