<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reimbursement Request #RY{{ $reimbursement->id }} (Invoice Full Page)</title>
    <style>
        @page { margin: 30px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.5;
            margin: 0;
        }

        .section { margin-bottom: 18px; }

        .title { font-size: 18px; text-align: center; font-weight: 700; }
        .sub-title { font-size: 11px; margin-bottom: 10px; text-align: center; }

        .label { font-weight: 700; }
        .box { border: 1px solid #000; padding: 6px; margin-top: 4px; background: #f8f8f8; }

        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; padding: 4px 8px; }

        table.details-table { width: 100%; border-collapse: collapse; margin-top: 8px; table-layout: fixed; }
        table.details-table td { padding: 4px 6px; vertical-align: top; }
        table.details-table td.label-col { width: 30%; font-weight: 700; }
        table.details-table td.data-col { width: 70%; }

        /* Invoice page */
        .invoice-page { page-break-before: always; }
        .invoice-wrapper { padding: 0; margin: 0; text-align: left; line-height: 0; overflow: hidden; }
        .invoice-full {
            width: auto;           /* jangan stretch lebar */
            max-width: 100%;       /* tetap aman di batas kanan */
            height: auto;
            max-height: 720px;     /* maksimalkan tinggi halaman konten */
            object-fit: contain;   /* jaga proporsi */
            display: block;
            margin: 0;             /* rata kiri */
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    {{-- Letterhead hanya di halaman pertama --}}
    @include('components.pdf.letterhead')

    <div class="section">
        <div class="sub-title">Reimbursement Request #RY{{ $reimbursement->id }} | {{ \Carbon\Carbon::parse($reimbursement->created_at)->format('F d, Y \a\t H:i') }}</div>

        <table class="layout">
            <tr>
                <td>
                    <h3>Employee Information</h3>
                    <table class="details-table">
                        <tr>
                            <td class="label-col">Email:</td>
                            <td class="data-col"><div class="box">{{ $reimbursement->employee->email ?? 'N/A' }}</div></td>
                        </tr>
                        <tr>
                            <td class="label-col">Name:</td>
                            <td class="data-col"><div class="box">{{ $reimbursement->employee->name ?? 'N/A' }}</div></td>
                        </tr>
                        <tr>
                            <td class="label-col">Approver 1:</td>
                            <td class="data-col"><div class="box">{{ $reimbursement->approver->name ?? 'N/A' }}</div></td>
                        </tr>
                        <tr>
                            <td class="label-col">Divisi:</td>
                            <td class="data-col"><div class="box">{{ $reimbursement->employee->division->name ?? 'N/A' }}</div></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <h3>Approval Status</h3>
                    <div><span class="label">Approver 1 Status:</span></div>
                    <div class="box">
                        @if($reimbursement->status_1 === 'pending') Pending Review
                        @elseif($reimbursement->status_1 === 'approved') Approved
                        @elseif($reimbursement->status_1 === 'rejected') Rejected
                        @endif
                    </div>
                    <div style="margin-top:10px"><span class="label">Approver 2 Status:</span></div>
                    <div class="box">
                        @if($reimbursement->status_2 === 'pending') Pending Review
                        @elseif($reimbursement->status_2 === 'approved') Approved
                        @elseif($reimbursement->status_2 === 'rejected') Rejected
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Reimbursement Details</h3>
        <table class="details-table">
            <tr>
                <td class="label-col">Date of Expanse:</td>
                <td class="data-col"><div class="box">{{ \Carbon\Carbon::parse($reimbursement->date)->format('l, M d, Y') }}</div></td>
            </tr>
            <tr>
                <td class="label-col">Customer:</td>
                <td class="data-col"><div class="box">{{ $reimbursement->customer ?? 'N/A' }}</div></td>
            </tr>
            <tr>
                <td class="label-col">Total Amount:</td>
                <td class="data-col"><div class="box">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div></td>
            </tr>
            <tr>
                <td class="label-col">Type Reimbursement:</td>
                <td class="data-col"><div class="box">{{ $reimbursement->type->name ?? 'N/A' }}</div></td>
            </tr>
        </table>
    </div>

    {{-- Halaman khusus untuk invoice, isi gambar memenuhi halaman secara proporsional --}}
    <div class="section invoice-page">
        <h3 style="margin-bottom:8px;">Invoice</h3>
        @php
            $path = storage_path('app/public/' . $reimbursement->invoice_path);
            $base64 = '';
            if ($reimbursement->invoice_path && file_exists($path)) {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $data = @file_get_contents($path);
                if ($data !== false) {
                    $mime = strtolower($ext) === 'jpg' || strtolower($ext) === 'jpeg' ? 'jpeg' : strtolower($ext);
                    $base64 = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                }
            }
        @endphp
        <div class="invoice-wrapper">
            @if($base64)
                <img class="invoice-full" src="{{ $base64 }}" alt="Invoice">
            @else
                <div class="box" style="font-style:italic; color:#666;">No image available</div>
            @endif
        </div>
    </div>

</body>
</html>

