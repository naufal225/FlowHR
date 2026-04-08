<?php

namespace App\Services\Reports;

use App\Models\OfficialTravel;
use App\Models\Overtime;
use App\Models\Reimbursement;
use App\Models\ReportExport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ReportDatasetResolver
{
    /**
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function resolveQuery(ReportExport $export): Builder
    {
        $filters = $export->filters;
        $status = isset($filters['status']) ? (string) $filters['status'] : null;
        $from = Carbon::parse((string) ($filters['from_date'] ?? now()->toDateString()), 'Asia/Jakarta')->startOfDay();
        $to = Carbon::parse((string) ($filters['to_date'] ?? now()->toDateString()), 'Asia/Jakarta')->endOfDay();

        $query = match ($export->module) {
            ReportExport::MODULE_REIMBURSEMENT => Reimbursement::query()->with(['employee', 'approver1', 'approver2', 'type']),
            ReportExport::MODULE_OVERTIME => Overtime::query()->with(['employee', 'approver1', 'approver2']),
            ReportExport::MODULE_OFFICIAL_TRAVEL => OfficialTravel::query()->with(['employee', 'approver1', 'approver2']),
            default => throw new InvalidArgumentException('Unsupported report export module: ' . $export->module),
        };

        if ($status) {
            $query->filterFinalStatus($status);
        }

        $this->applyRoleScopeFilter($query, $export->role_scope);
        $this->applyDateFilter($query, $export->module, $export->role_scope, $from, $to);

        return $query;
    }

    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     */
    private function applyRoleScopeFilter(Builder $query, string $roleScope): void
    {
        if ($roleScope === 'finance') {
            $query->where('status_1', 'approved')
                ->where('status_2', 'approved')
                ->where('marked_down', true);
        }
    }

    /**
     * @param Builder<\Illuminate\Database\Eloquent\Model> $query
     */
    private function applyDateFilter(
        Builder $query,
        string $module,
        string $roleScope,
        Carbon $from,
        Carbon $to
    ): void {
        if ($module === ReportExport::MODULE_REIMBURSEMENT) {
            $query->where('date', '>=', $from)->where('date', '<=', $to);
            return;
        }

        if ($roleScope === 'finance') {
            $query->where('date_start', '>=', $from)->where('date_end', '<=', $to);
            return;
        }

        $query->where('created_at', '>=', $from)->where('created_at', '<=', $to);
    }

    /**
     * @return string[]
     */
    public function summaryHeaders(string $module): array
    {
        return match ($module) {
            ReportExport::MODULE_REIMBURSEMENT => [
                'Request ID',
                'Employee',
                'Date',
                'Total',
                'Type',
                'Final Status',
                'Created At',
            ],
            ReportExport::MODULE_OVERTIME => [
                'Request ID',
                'Employee',
                'Start',
                'End',
                'Total',
                'Final Status',
                'Created At',
            ],
            ReportExport::MODULE_OFFICIAL_TRAVEL => [
                'Request ID',
                'Employee',
                'Start Date',
                'End Date',
                'Customer',
                'Total',
                'Final Status',
                'Created At',
            ],
            default => throw new InvalidArgumentException('Unsupported summary module: ' . $module),
        };
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $item
     * @return string[]
     */
    public function toSummaryRow(string $module, $item): array
    {
        return match ($module) {
            ReportExport::MODULE_REIMBURSEMENT => [
                '#' . $item->id,
                (string) ($item->employee->name ?? 'N/A'),
                optional($item->date)->format('Y-m-d') ?? '-',
                'Rp ' . number_format((float) $item->total, 0, ',', '.'),
                (string) ($item->type->name ?? '-'),
                ucfirst((string) ($item->final_status ?? 'pending')),
                optional($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
            ],
            ReportExport::MODULE_OVERTIME => [
                '#' . $item->id,
                (string) ($item->employee->name ?? 'N/A'),
                optional($item->date_start)->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
                optional($item->date_end)->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
                'Rp ' . number_format((float) $item->total, 0, ',', '.'),
                ucfirst((string) ($item->final_status ?? 'pending')),
                optional($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
            ],
            ReportExport::MODULE_OFFICIAL_TRAVEL => [
                '#' . $item->id,
                (string) ($item->employee->name ?? 'N/A'),
                optional($item->date_start)->format('Y-m-d') ?? '-',
                optional($item->date_end)->format('Y-m-d') ?? '-',
                (string) ($item->customer ?? '-'),
                'Rp ' . number_format((float) $item->total, 0, ',', '.'),
                ucfirst((string) ($item->final_status ?? 'pending')),
                optional($item->created_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i') ?? '-',
            ],
            default => throw new InvalidArgumentException('Unsupported summary module: ' . $module),
        };
    }

    public function summaryTitle(string $module): string
    {
        return match ($module) {
            ReportExport::MODULE_REIMBURSEMENT => 'Reimbursement Summary Report',
            ReportExport::MODULE_OVERTIME => 'Overtime Summary Report',
            ReportExport::MODULE_OFFICIAL_TRAVEL => 'Official Travel Summary Report',
            default => 'Summary Report',
        };
    }
}

