<?php

namespace App\Exports;

use App\Models\Reimbursement;
use App\Models\User;
use App\Enums\Roles;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReimbursementsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Reimbursement::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->filters['status'])) {
            $query->where('status_1', $this->filters['status'])
                  ->orWhere('status_2', $this->filters['status']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('date', '>=', Carbon::parse($this->filters['from_date'])->toDateString());
        }
        if (!empty($this->filters['to_date'])) {
            $query->whereDate('date', '<=', Carbon::parse($this->filters['to_date'])->toDateString());
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Request ID',
            'Employee Name',
            'Employee Email',
            'Date',
            'Total',
            'Status 1',
            'Status 2',
            'Approver 1',
            'Approver 2',
            'Updated Date',
            'Approved Date',
            'Rejected Date',
            'Applied Date', // pojok kanan
        ];
    }

    public function map($reimbursement): array
    {
        $date = Carbon::parse($reimbursement->date);

        return [
            '#' . $reimbursement->id,
            $reimbursement->employee->name ?? 'N/A',
            $reimbursement->employee->email ?? 'N/A',
            $date->format('M d, Y'),
            $reimbursement->total ?? 0,
            ucfirst((string) $reimbursement->status_1),
            ucfirst((string) $reimbursement->status_2),
            $reimbursement->approver->name ?? 'N/A',
            optional(User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first())->name ?? 'N/A',
            $reimbursement->updated_at?->format('M d, Y H:i') ?? '-',
            $reimbursement->approved_date ? $reimbursement->approved_date->format('M d, Y H:i') : '-',
            $reimbursement->rejected_date ? $reimbursement->rejected_date->format('M d, Y H:i') : '-',
            $reimbursement->created_at?->format('M d, Y H:i') ?? '-', // Applied Date
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0'],
                ],
            ],
        ];
    }
}
