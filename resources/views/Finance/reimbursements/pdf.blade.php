<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reimbursement Request #RY{{ $reimbursement->id }}</title>
    <style>
        @page { margin: 130px 26px 24px 26px; }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }

        .pdf-header { position: fixed; top: -110px; left: 0; right: 0; }
        .section { margin-bottom: 12px; page-break-inside: avoid; }
        .sub-title { font-size: 11px; margin-bottom: 8px; text-align: center; }
        .label { font-weight: bold; }

        .box {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 3px;
            background-color: #f8f8f8;
            page-break-inside: avoid;
            word-wrap: break-word;
        }

        .status-approved { color: #118a23; font-weight: bold; }
        .status-rejected { color: #c51212; font-weight: bold; }
        .status-pending { color: #d98200; font-weight: bold; }

        table.layout, table.split {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.layout td, table.split td {
            vertical-align: top;
            padding: 4px 6px;
        }

        table.split td.left-col { width: 22%; }
        table.split td.right-col { width: 78%; }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .info-table td {
            padding: 1px 4px;
            vertical-align: top;
        }

        .detail-item { margin-bottom: 3px; }
        .detail-label { display: block; font-weight: bold; }

        .invoice-box {
            border: 1px solid #000;
            background-color: #f8f8f8;
            padding: 0;
            height: 570px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            page-break-inside: avoid;
        }

        img.invoice {
            width: auto;
            max-width: 100%;
            height: auto;
            max-height: 560px;
            display: block;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        @include('components.pdf.letterhead')
    </div>

    @if($reimbursement->marked_down)
        @include('components.pdf.watermark')
    @endif

    <div class="section">
        <div class="sub-title">
            Reimbursement Request #RY{{ $reimbursement->id }} |
            {{ \Carbon\Carbon::parse($reimbursement->created_at)->format('F d, Y \a\t H:i') }}
        </div>

        <table class="layout">
            <tr>
                <td>
                    <h3 style="margin:0 0 4px 0;">Employee Information</h3>
                    <table class="info-table">
                        <tr>
                            <td style="width:30%;"><span class="label">Email:</span></td>
                            <td>{{ $reimbursement->employee->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Name:</span></td>
                            <td>{{ $reimbursement->employee->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Approver 1:</span></td>
                            <td>{{ $reimbursement->approver1->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Divisi:</span></td>
                            <td>{{ $reimbursement->employee->division->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <h3 style="margin:0 0 4px 0;">Approval Status</h3>
                    <div><span class="label">Approver 1 Status:</span></div>
                    <div class="box status-{{ $reimbursement->status_1 }}">
                        @if($reimbursement->status_1 === 'pending')
                            Pending Review
                        @elseif($reimbursement->status_1 === 'approved')
                            Approved
                        @elseif($reimbursement->status_1 === 'rejected')
                            Rejected
                        @endif
                    </div>

                    <div style="margin-top:6px;"><span class="label">Approver 2 Status:</span></div>
                    <div class="box status-{{ $reimbursement->status_2 }}">
                        @if($reimbursement->status_2 === 'pending')
                            Pending Review
                        @elseif($reimbursement->status_2 === 'approved')
                            Approved
                        @elseif($reimbursement->status_2 === 'rejected')
                            Rejected
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <table class="split">
            <tr>
                <td class="left-col">
                    <h3 style="margin:0 0 4px 0;">Reimbursement Details</h3>

                    <div class="detail-item">
                        <span class="detail-label">Date of Expense</span>
                        <div class="box">{{ \Carbon\Carbon::parse($reimbursement->date)->format('l, M d, Y') }}</div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Customer</span>
                        <div class="box">{{ $reimbursement->customer ?? 'N/A' }}</div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Total Amount</span>
                        <div class="box">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Type Reimbursement</span>
                        <div class="box">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                    </div>
                </td>
                <td class="right-col">
                    <h3 style="margin:0 0 4px 0;">Invoice</h3>
                    @php
                        $invoicePath = $reimbursement->invoice_path ? storage_path('app/public/' . $reimbursement->invoice_path) : null;
                        $base64 = '';
                        $isImage = false;

                        if ($invoicePath && file_exists($invoicePath)) {
                            $ext = strtolower(pathinfo($invoicePath, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);

                            if ($isImage) {
                                $data = @file_get_contents($invoicePath);
                                if ($data !== false) {
                                    $mime = $ext === 'jpg' ? 'jpeg' : $ext;
                                    $base64 = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                                }
                            }
                        }
                    @endphp
                    <div class="invoice-box">
                        @if($base64 !== '')
                            <img src="{{ $base64 }}" alt="Invoice" class="invoice">
                        @elseif($reimbursement->invoice_path)
                            <div style="padding:10px; font-style:italic; color:#666;">
                                Invoice file is not an image ({{ strtoupper(pathinfo($reimbursement->invoice_path, PATHINFO_EXTENSION)) }}).
                            </div>
                        @else
                            <div style="padding:10px; font-style:italic; color:#666;">
                                No invoice provided.
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
