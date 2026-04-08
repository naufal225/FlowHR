<?php

namespace App\Services\Reports;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReportExportService
{
    /**
     * @param array{
     *     module:string,
     *     export_type:string,
     *     filters:array<string,mixed>
     * } $payload
     */
    public function createExport(User $user, string $roleScope, array $payload): ReportExport
    {
        $module = (string) $payload['module'];
        $exportType = (string) $payload['export_type'];

        if (
            $exportType === ReportExport::EXPORT_TYPE_EVIDENCE &&
            $module !== ReportExport::MODULE_REIMBURSEMENT
        ) {
            throw ValidationException::withMessages([
                'export_type' => 'Evidence package currently only supported for reimbursement.',
            ]);
        }

        $filters = $this->normalizeFilters((array) ($payload['filters'] ?? []));

        $reportExport = ReportExport::query()->create([
            'requested_by' => $user->id,
            'role_scope' => $roleScope,
            'module' => $module,
            'export_type' => $exportType,
            'format' => $exportType === ReportExport::EXPORT_TYPE_EVIDENCE ? 'zip' : 'pdf',
            'filters_json' => $filters,
            'status' => ReportExport::STATUS_QUEUED,
            'progress_percent' => 0,
            'processed_items' => 0,
            'total_items' => 0,
        ]);

        Log::info('Report export queued.', [
            'report_export_id' => $reportExport->id,
            'user_id' => $user->id,
            'module' => $module,
            'export_type' => $exportType,
            'role_scope' => $roleScope,
            'filters' => $filters,
        ]);

        $this->dispatchExportJob($reportExport->id);

        return $reportExport;
    }

    /**
     * @return array<string, string|null>
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'status' => Arr::get($filters, 'status') ?: null,
            'from_date' => (string) Arr::get($filters, 'from_date', ''),
            'to_date' => (string) Arr::get($filters, 'to_date', ''),
        ];
    }

    private function dispatchExportJob(string $reportExportId): void
    {
        GenerateReportExportJob::dispatch($reportExportId)->onQueue('reports');
    }
}
