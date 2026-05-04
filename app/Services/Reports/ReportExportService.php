<?php

namespace App\Services\Reports;

use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportExportService
{
    public function __construct(
        private readonly ReportingDispatchClient $dispatchClient,
        private readonly ReportDatasetResolver $datasetResolver,
    ) {}

    /**
     * @param array{
     *     module:string,
     *     export_type:string,
     *     format?:string|null,
     *     filters:array<string,mixed>
     * } $payload
     */
    public function createExport(User $user, string $roleScope, array $payload): ReportExport
    {
        $module = (string) $payload['module'];
        $exportType = (string) $payload['export_type'];
        $requestedFormat = isset($payload['format']) ? (string) $payload['format'] : '';

        if (
            $exportType === ReportExport::EXPORT_TYPE_EVIDENCE &&
            $module !== ReportExport::MODULE_REIMBURSEMENT
        ) {
            throw ValidationException::withMessages([
                'export_type' => 'Evidence package currently only supported for reimbursement.',
            ]);
        }

        $filters = $this->normalizeFilters((array) ($payload['filters'] ?? []));
        $format = $this->resolveFormat(
            exportType: $exportType,
            requestedFormat: $requestedFormat,
            roleScope: $roleScope,
            module: $module,
            filters: $filters,
        );

        $reportExport = ReportExport::query()->create([
            'requested_by' => $user->id,
            'role_scope' => $roleScope,
            'module' => $module,
            'export_type' => $exportType,
            'format' => $format,
            'filters_json' => $filters,
            'status' => ReportExport::STATUS_QUEUED,
            'progress_percent' => 0,
            'processed_items' => 0,
            'total_items' => 0,
            'queued_at' => now(),
            'worker_app' => 'flowhr-reporting-app',
            'attempts' => 0,
            'error_message' => null,
            'started_at' => null,
            'finished_at' => null,
        ]);

        Log::info('Report export queued.', [
            'report_export_id' => $reportExport->id,
            'user_id' => $user->id,
            'module' => $module,
            'export_type' => $exportType,
            'format' => $format,
            'role_scope' => $roleScope,
            'filters' => $filters,
        ]);

        try {
            $this->dispatchClient->enqueue($reportExport);
        } catch (Throwable $exception) {
            $reportExport->update([
                'status' => ReportExport::STATUS_FAILED,
                'error_message' => 'Failed to enqueue report export: ' . $exception->getMessage(),
                'finished_at' => now(),
            ]);

            Log::error('Report export enqueue failed.', [
                'report_export_id' => $reportExport->id,
                'module' => $module,
                'export_type' => $exportType,
                'format' => $format,
                'error' => $exception->getMessage(),
            ]);
        }

        return $reportExport->fresh();
    }

    /**
     * @param array<string, string|null> $filters
     */
    private function resolveFormat(
        string $exportType,
        string $requestedFormat,
        string $roleScope,
        string $module,
        array $filters,
    ): string {
        if ($exportType === ReportExport::EXPORT_TYPE_EVIDENCE) {
            return ReportExport::FORMAT_ZIP;
        }

        $requestedFormat = strtolower(trim($requestedFormat));
        if (in_array($requestedFormat, [ReportExport::FORMAT_PDF, ReportExport::FORMAT_XLSX], true)) {
            return $requestedFormat;
        }

        return $this->resolveDefaultSummaryFormat($roleScope, $module, $filters);
    }

    /**
     * @param array<string, string|null> $filters
     */
    private function resolveDefaultSummaryFormat(string $roleScope, string $module, array $filters): string
    {
        try {
            $probe = new ReportExport();
            $probe->role_scope = $roleScope;
            $probe->module = $module;
            $probe->filters_json = $filters;

            $pdfThreshold = max(1, (int) config('reporting.summary_pdf_max_rows', 300));
            $sampleCount = (clone $this->datasetResolver->resolveQuery($probe))
                ->selectRaw('1')
                ->limit($pdfThreshold + 1)
                ->get()
                ->count();

            return $sampleCount <= $pdfThreshold
                ? ReportExport::FORMAT_PDF
                : ReportExport::FORMAT_XLSX;
        } catch (Throwable $exception) {
            Log::warning('Failed to infer summary format, fallback to xlsx.', [
                'module' => $module,
                'role_scope' => $roleScope,
                'error' => $exception->getMessage(),
            ]);

            return ReportExport::FORMAT_XLSX;
        }
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
}
