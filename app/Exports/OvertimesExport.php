<?php

namespace App\Exports;

use App\Models\Overtime;
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

class OvertimesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Overtime::with(['employee', 'approver'])
            ->orderBy('created_at', 'desc');

        // Filter status
        if (!empty($this->filters['status'])) {
            $statusFilter = $this->filters['status'];
            switch ($statusFilter) {
                case 'approved':
                    $query->where('status_1', 'approved')
                          ->where('status_2', 'approved');
                    break;
                case 'rejected':
                    $query->where(function ($q) {
                        $q->where('status_1', 'rejected')
                          ->orWhere('status_2', 'rejected');
                    });
                    break;
                case 'pending':
                    $query->where(function ($q) {
                        $q->where(function ($qq) {
                            $qq->where('status_1', 'pending')
                               ->orWhere('status_2', 'pending');
                        });
                        $q->where(function ($qq) {
                            $qq->where('status_1', '!=', 'rejected')
                               ->where('status_2', '!=', 'rejected');
                        });
                    });
                    break;
            }
        }

        // Filter tanggal created_at
        if (!empty($this->filters['from_date'])) {
            $fromDate = Carbon::parse($this->filters['from_date'])->startOfDay()->timezone('Asia/Jakarta');
            $query->where('created_at', '>=', $fromDate);
        }
        if (!empty($this->filters['to_date'])) {
            $toDate = Carbon::parse($this->filters['to_date'])->endOfDay()->timezone('Asia/Jakarta');
            $query->where('created_at', '<=', $toDate);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Request ID',
            'Employee Name',
            'Employee Email',
            'Start Date & Time',
            'End Date & Time',
            'Duration (Days)',
            'Overtime (Hours & Minutes)',
            'Meal Costs (Rp)',
            'Overtime Rate (Rp)',
            'Total Amount (Rp)',
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

    public function map($overtime): array
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_start, 'Asia/Jakarta');
        $end   = Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_end, 'Asia/Jakarta');

        // Durasi hari
        $durationDays = round($start->diffInDays($end) + 1, 2);

        // Durasi menit
        $overtimeMinutes = $start->diffInMinutes($end);
        $hours = floor($overtimeMinutes / 60);
        $minutes = $overtimeMinutes % 60;

        // Biaya
        $mealCost = (int) env('MEAL_COSTS', 30000);
        $overtimeRatePerHour = (int) env('OVERTIME_COSTS', 25000);
        $calculatedTotal = ($hours * $overtimeRatePerHour) + $mealCost;

        return [
            '#' . $overtime->id,
            $overtime->employee->name ?? 'N/A',
            $overtime->employee->email ?? 'N/A',
            $start->format('M d, Y H:i'),
            $end->format('M d, Y H:i'),
            $durationDays,
            $hours . " jam, " . $minutes . " menit",
            number_format($mealCost, 0, ',', '.'),
            number_format($hours * $overtimeRatePerHour, 0, ',', '.'),
            number_format($calculatedTotal, 0, ',', '.'),
            ucfirst((string) $overtime->status_1),
            ucfirst((string) $overtime->status_2),
            $overtime->approver->name ?? 'N/A',
            optional(User::whereHas('roles', fn($q) => $q->where('name', Roles::Manager->value))->first())->name ?? 'N/A',
            $overtime->updated_at?->timezone('Asia/Jakarta')->format('M d, Y H:i') ?? '-',
            $overtime->approved_date ? $overtime->approved_date->timezone('Asia/Jakarta')->format('M d, Y H:i') : '-', // Approved Date
            $overtime->rejected_date ? $overtime->rejected_date->timezone('Asia/Jakarta')->format('M d, Y H:i') : '-', // Rejected Date
            $overtime->created_at?->timezone('Asia/Jakarta')->format('M d, Y H:i') ?? '-', // Applied Date
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
