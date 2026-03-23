<?php

namespace App\Exports;

use App\Models\Leave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LeavesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Leave::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        // Apply the same filters as in the index method
        if (!empty($this->filters['status'])) {
            $query->where('status_1', $this->filters['status']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->where(
                'date_start',
                '>=',
                Carbon::parse($this->filters['from_date'])->startOfDay()->timezone('Asia/Jakarta')
            );
        }

        if (!empty($this->filters['to_date'])) {
            $query->where(
                'date_start',
                '<=',
                Carbon::parse($this->filters['to_date'])->endOfDay()->timezone('Asia/Jakarta')
            );
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Request ID',
            'Employee Name',
            'Employee Email',
            'Start Date',
            'End Date',
            'Duration (Days)',
            'Reason',
            'Status',
            'Approver ',
            'Updated Date',
            'Approved Date',
            'Rejected Date',
            'Applied Date',
        ];
    }

    public function map($leave): array
    {
        $startDate = Carbon::parse($leave->date_start);
        $endDate = Carbon::parse($leave->date_end);
        $duration = $startDate->diffInDays($endDate) + 1;

        return [
            '#' . $leave->id,
            $leave->employee->name ?? 'N/A',
            $leave->employee->email ?? 'N/A',
            $startDate->format('M d, Y'),
            $endDate->format('M d, Y'),
            $duration,
            $leave->reason ?? 'N/A',
            ucfirst($leave->status_1),
            $leave->approver->name ?? 'N/A',
            $leave->updated_at->format('M d, Y H:i'),
            $leave->approved_date ? $leave->approved_date->format('M d, Y H:i') : '-', // Approved Date
            $leave->rejected_date ? $leave->rejected_date->format('M d, Y H:i') : '-', // Rejected Date
            $leave->created_at->format('M d, Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
    }
}
