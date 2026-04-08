<?php

namespace App\Services\Reports;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportRenderer
{
    /**
     * @param string[] $headers
     * @param array<int, string[]> $rows
     */
    public function renderSummary(
        ReportExport $reportExport,
        string $title,
        array $headers,
        array $rows
    ): string {
        $filters = $reportExport->filters;

        $pdf = Pdf::loadView('reports.summary-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'statusLabel' => $this->buildStatusLabel($filters['status'] ?? null),
            'dateRangeLabel' => $this->buildDateRangeLabel(
                (string) ($filters['from_date'] ?? ''),
                (string) ($filters['to_date'] ?? '')
            ),
            'generatedAt' => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
            'requestedBy' => $reportExport->requestedBy?->name ?? '-',
        ])->setPaper('a4', 'landscape');

        return $pdf->output();
    }

    private function buildStatusLabel(?string $status): string
    {
        if (! $status) {
            return 'All Statuses';
        }

        return ucfirst($status);
    }

    private function buildDateRangeLabel(string $fromDate, string $toDate): string
    {
        if ($fromDate === '' || $toDate === '') {
            return '-';
        }

        return $fromDate . ' to ' . $toDate;
    }
}

