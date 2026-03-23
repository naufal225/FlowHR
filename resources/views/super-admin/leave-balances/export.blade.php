<!DOCTYPE html>
<html>
<head>
    <title>Leave Balances Report</title>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 120px 25px 25px 25px;
        }
        #page-header {
            position: fixed;
            top: -100px;
            left: 0;
            right: 0;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .filters {
            margin-bottom: 15px;
            font-size: 11px;
        }
        .filters strong {
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .summary {
            margin-top: 20px;
            font-size: 11px;
        }
        .summary-table {
            width: 50%;
        }
        
    </style>
</head>
<body>
    <div id="page-header">
        @include('components.pdf.letterhead')
    </div>
    <div class="header" style="border:none; padding-bottom:0; margin-top:0;">
        <h1>EMPLOYEE LEAVE BALANCES REPORT</h1>
        <p>Generated on {{ now()->format('F d, Y H:i') }}</p>
    </div>

    <div class="filters">
        <p>
            <strong>Year:</strong> {{ $year }}
            <strong>Division:</strong>
            @if($divisionId)
                {{ $divisions->firstWhere('id', $divisionId)?->name ?? 'Unknown Division' }}
            @else
                All Divisions
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="20%">Employee Name</th>
                <th width="20%">Email</th>
                <th width="15%">Division</th>
                <th width="10%">Annual Leave</th>
                <th width="10%">Used Days</th>
                <th width="10%">Remaining Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaveBalances as $index => $balance)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $balance['employee']->name }}</td>
                <td>{{ $balance['employee']->email }}</td>
                <td>{{ $balance['employee']->division?->name ?? 'No Division' }}</td>
                <td class="text-center">{{ $balance['total_cuti'] }}</td>
                <td class="text-center">{{ $balance['used_cuti'] }}</td>
                <td class="text-center">{{ $balance['sisa_cuti'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">No employees found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <table class="summary-table">
            <tr>
                <td><strong>Total Employees:</strong></td>
                <td class="text-right">{{ count($leaveBalances) }}</td>
            </tr>
            <tr>
                <td><strong>Average Used Days:</strong></td>
                <td class="text-right">
                    {{ count($leaveBalances) > 0 ? round(array_sum(array_column($leaveBalances, 'used_cuti')) / count($leaveBalances), 1) : 0 }}
                </td>
            </tr>
            <tr>
                <td><strong>Average Remaining Days:</strong></td>
                <td class="text-right">
                    {{ count($leaveBalances) > 0 ? round(array_sum(array_column($leaveBalances, 'sisa_cuti')) / count($leaveBalances), 1) : 0 }}
                </td>
            </tr>
        </table>
    </div>

    
</body>
</html>
