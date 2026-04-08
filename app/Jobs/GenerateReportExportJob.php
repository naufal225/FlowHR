<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\EvidencePackager;
use App\Services\Reports\ReportDatasetResolver;
use App\Services\Reports\ReportRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        private readonly string $reportExportId
    ) {
    }

    public function handle(
        ReportDatasetResolver $datasetResolver,
        ReportRenderer $renderer,
        EvidencePackager $evidencePackager
    ): void {
        $reportExport = ReportExport::query()->find($this->reportExportId);
        if (! $reportExport) {
            return;
        }

        $reportExport->update([
            'status' => ReportExport::STATUS_PROCESSING,
            'started_at' => now(),
            'error_message' => null,
        ]);

        Log::info('GenerateReportExportJob started.', [
            'report_export_id' => $reportExport->id,
            'module' => $reportExport->module,
            'export_type' => $reportExport->export_type,
            'role_scope' => $reportExport->role_scope,
            'requested_by' => $reportExport->requested_by,
        ]);

        try {
            $query = $datasetResolver->resolveQuery($reportExport);
            $totalItems = (clone $query)->count();

            if ($totalItems < 1) {
                $this->markFailed($reportExport, 'No data found for the selected filters.');
                return;
            }

            $reportExport->update([
                'total_items' => $totalItems,
                'processed_items' => 0,
                'progress_percent' => 0,
            ]);

            if ($reportExport->export_type === ReportExport::EXPORT_TYPE_EVIDENCE) {
                $relativePath = $evidencePackager->buildReimbursementZip(
                    $reportExport,
                    $query,
                    fn (int $processed, int $total): bool => $this->updateProgress($reportExport, $processed, $total)
                );

                $this->markCompleted($reportExport, $relativePath, 'zip');
                return;
            }

            $rows = [];
            $processed = 0;

            $query->chunkById(100, function ($items) use (
                &$rows,
                &$processed,
                $totalItems,
                $reportExport,
                $datasetResolver
            ): void {
                foreach ($items as $item) {
                    $rows[] = $datasetResolver->toSummaryRow($reportExport->module, $item);
                    $processed++;
                }

                $this->updateProgress($reportExport, $processed, $totalItems);
            });

            $pdfBinary = $renderer->renderSummary(
                $reportExport,
                $datasetResolver->summaryTitle($reportExport->module),
                $datasetResolver->summaryHeaders($reportExport->module),
                $rows
            );

            $timestamp = now('Asia/Jakarta')->format('Y-m-d-H-i-s');
            $moduleSlug = str_replace('_', '-', $reportExport->module);
            $relativePath = 'report-exports/' . $reportExport->id . '/' . $moduleSlug . '-summary-' . $timestamp . '.pdf';
            Storage::disk('local')->put($relativePath, $pdfBinary);

            $this->markCompleted($reportExport, $relativePath, 'pdf');
        } catch (Throwable $exception) {
            $this->markFailed($reportExport, $exception->getMessage());

            $durationMs = $reportExport->started_at
                ? (int) $reportExport->started_at->diffInMilliseconds(now())
                : null;

            Log::error('GenerateReportExportJob failed.', [
                'report_export_id' => $this->reportExportId,
                'module' => $reportExport->module,
                'export_type' => $reportExport->export_type,
                'total_items' => $reportExport->total_items,
                'processed_items' => $reportExport->processed_items,
                'duration_ms' => $durationMs,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function updateProgress(ReportExport $reportExport, int $processed, int $total): bool
    {
        $percent = $total > 0 ? (int) min(99, floor(($processed / $total) * 100)) : 0;

        return $reportExport->forceFill([
            'processed_items' => $processed,
            'total_items' => $total,
            'progress_percent' => $percent,
        ])->save();
    }

    private function markCompleted(ReportExport $reportExport, string $relativePath, string $format): void
    {
        $fileName = basename($relativePath);
        $finishedAt = now();

        $reportExport->update([
            'status' => ReportExport::STATUS_COMPLETED,
            'progress_percent' => 100,
            'processed_items' => $reportExport->total_items,
            'result_disk' => 'local',
            'result_path' => $relativePath,
            'file_name' => $fileName,
            'format' => $format,
            'finished_at' => $finishedAt,
        ]);

        $durationMs = $reportExport->started_at
            ? (int) $reportExport->started_at->diffInMilliseconds($finishedAt)
            : null;

        Log::info('GenerateReportExportJob completed.', [
            'report_export_id' => $reportExport->id,
            'module' => $reportExport->module,
            'export_type' => $reportExport->export_type,
            'format' => $format,
            'total_items' => $reportExport->total_items,
            'processed_items' => $reportExport->processed_items,
            'duration_ms' => $durationMs,
            'result_path' => $relativePath,
        ]);
    }

    private function markFailed(ReportExport $reportExport, string $message): void
    {
        $reportExport->update([
            'status' => ReportExport::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }
}
