<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            if (! Schema::hasColumn('report_exports', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('finished_at');
            }

            if (! Schema::hasColumn('report_exports', 'worker_app')) {
                $table->string('worker_app', 120)->default('flowhr-reporting-app')->after('queued_at');
            }

            if (! Schema::hasColumn('report_exports', 'attempts')) {
                $table->unsignedInteger('attempts')->default(0)->after('worker_app');
            }

            if (! Schema::hasColumn('report_exports', 'last_heartbeat_at')) {
                $table->timestamp('last_heartbeat_at')->nullable()->after('attempts');
            }

            if (! Schema::hasColumn('report_exports', 'artifact_size_bytes')) {
                $table->unsignedBigInteger('artifact_size_bytes')->nullable()->after('last_heartbeat_at');
            }
        });

        Schema::table('report_exports', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'report_exports_status_created_at_idx');
            $table->index(['requested_by', 'role_scope', 'status'], 'report_exports_user_scope_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropIndex('report_exports_status_created_at_idx');
            $table->dropIndex('report_exports_user_scope_status_idx');

            $drops = [];
            foreach (['queued_at', 'worker_app', 'attempts', 'last_heartbeat_at', 'artifact_size_bytes'] as $column) {
                if (Schema::hasColumn('report_exports', $column)) {
                    $drops[] = $column;
                }
            }

            if ($drops !== []) {
                $table->dropColumn($drops);
            }
        });
    }
};
