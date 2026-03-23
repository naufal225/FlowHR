<?php

namespace App\Exports;

use App\Models\OfficialTravel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OfficialTravelsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = OfficialTravel::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->filters['status'])) {
            $query->where('status_1', $this->filters['status'])
                  ->orWhere('status_2', $this->filters['status']);
        }

        if (!empty($this->filters['from_date'])) {
            $query->whereDate('date_start', '>=', Carbon::parse($this->filters['from_date'])->toDateString());
        }
        if (!empty($this->filters['to_date'])) {
            $query->whereDate('date_start', '<=', Carbon::parse($this->filters['to_date'])->toDateString());
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
            'Total',
            'Status 1',
            'Status 2',
            'Approver',
            'Updated Date',
            'Approved Date',
            'Rejected Date',
            'Applied Date', // Pojok kanan
        ];
    }

    public function map($officialTravel): array
    {
        $startDate = Carbon::parse($officialTravel->date_start);
        $endDate   = Carbon::parse($officialTravel->date_end);
        $duration  = $startDate->diffInDays($endDate) + 1;

        return [
            '#' . $officialTravel->id,
            $officialTravel->employee->name ?? 'N/A',
            $officialTravel->employee->email ?? 'N/A',
            $startDate->format('M d, Y'),
            $endDate->format('M d, Y'),
            $duration,
            $officialTravel->total ?? 0,
            ucfirst((string) $officialTravel->status_1),
            ucfirst((string) $officialTravel->status_2),
            $officialTravel->approver->name ?? 'N/A',
            $officialTravel->updated_at?->format('M d, Y H:i') ?? '-',
            $officialTravel->approved_date ? $officialTravel->approved_date->format('M d, Y H:i') : '-',
            $officialTravel->rejected_date ? $officialTravel->rejected_date->format('M d, Y H:i') : '-',
            $officialTravel->created_at?->format('M d, Y H:i') ?? '-', // Applied Date pojok kanan
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
