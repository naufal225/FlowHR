<?php

namespace App\Services\Reports;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class EvidencePackager
{
    /**
     * @param Builder<\App\Models\Reimbursement> $query
     * @param callable(int, int): void $onProgress
     */
    public function buildReimbursementZip(
        ReportExport $reportExport,
        Builder $query,
        callable $onProgress
    ): string {
        $disk = Storage::disk('local');
        $timestamp = now('Asia/Jakarta')->format('Y-m-d-H-i-s');
        $zipRelativePath = 'report-exports/' . $reportExport->id . '/reimbursement-evidence-' . $timestamp . '.zip';
        $zipAbsolutePath = $disk->path($zipRelativePath);

        File::ensureDirectoryExists(dirname($zipAbsolutePath));

        $tempDir = storage_path('app/private/report-exports/tmp-' . $reportExport->id . '-' . Str::random(8));
        File::ensureDirectoryExists($tempDir);

        $zip = new ZipArchive();
        if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create evidence zip file.');
        }

        $total = (clone $query)->count();
        $processed = 0;

        try {
            $query->chunkById(50, function ($items) use (&$processed, $total, $onProgress, $zip, $tempDir): void {
                foreach ($items as $reimbursement) {
                    $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string) ($reimbursement->employee->name ?? 'employee'));
                    $detailFilename = 'reimbursement_' . $safeName . '_RY' . $reimbursement->id . '.pdf';
                    $detailTempPath = $tempDir . DIRECTORY_SEPARATOR . $detailFilename;

                    $pdfBinary = Pdf::loadView('admin.reimbursement.pdf', ['reimbursement' => $reimbursement])
                        ->setOptions(['isPhpEnabled' => true])
                        ->output();

                    File::put($detailTempPath, $pdfBinary);
                    $zip->addFile($detailTempPath, 'details/' . $detailFilename);

                    if ($reimbursement->invoice_path && Storage::disk('public')->exists($reimbursement->invoice_path)) {
                        $invoiceSource = Storage::disk('public')->path($reimbursement->invoice_path);
                        $invoiceExt = pathinfo($invoiceSource, PATHINFO_EXTENSION) ?: 'file';
                        $invoiceName = 'invoice_RY' . $reimbursement->id . '.' . $invoiceExt;
                        $zip->addFile($invoiceSource, 'invoices/' . $invoiceName);
                    }

                    $processed++;
                    $onProgress($processed, $total);
                }
            });
        } finally {
            $zip->close();
            File::deleteDirectory($tempDir);
        }

        return $zipRelativePath;
    }
}
