<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reimbursement Request #RY{{ $reimbursement->id }}</title>
    <style>
        @page {
            margin: 130px 30px 30px 30px; /* top, right, bottom, left */
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.6;
            margin: 0; /* handled by @page */
        }

        .header,
        .section {
            margin-bottom: 15px; /* Dikurangi dari 20px */
        }

        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .title {
            font-size: 20px;
            text-align: center;
            font-weight: bold;
        }

        .sub-title {
            font-size: 11px;
            margin-bottom: 8px; /* Dikurangi dari 12px */
            text-align: center
        }

        .label {
            font-weight: bold;
        }

        .text-data {
            display: inline;
        }

        .box {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 4px;
            background-color: #f8f8f8;
            page-break-inside: avoid;
            word-wrap: break-word;
        }

        .status-approved {
            color: green;
        }

        .status-rejected {
            color: red;
        }

        .status-pending {
            color: orange;
        }

        /* Repeating header/footer */
        .pdf-header {
            position: fixed;
            top: -110px; /* place into top margin */
            left: 0;
            right: 0;
        }

        table.layout {
            width: 100%;
            border-collapse: collapse;
        }

        table.layout td {
            vertical-align: top;
            padding: 4px 6px; /* Dikurangi dari 4px 8px */
        }

        /* Split layout for details (left) and invoice (right) */
        table.split {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.split td {
            vertical-align: top;
            padding: 4px 6px; /* Dikurangi dari 4px 8px */
        }

        table.split td.left-col {
            width: 24%;
        }
        table.split td.right-col {
            width: 76%;
        }
        table.split h3 {
            margin: 0 0 4px 0; /* Dikurangi dari 6px */
        }

        /* Stacked detail items: label above value */
        .detail-item {
            margin-bottom: 3px; /* Dikurangi dari 4px */
        }
        .detail-label {
            font-weight: bold;
            display: block;
        }
        .detail-item .box {
            margin-top: 2px; /* Dikurangi dari 3px */
            padding: 4px;
        }

        /* Reduce spacing around the split section to maximize space */
        .split-section {
            margin-top: 2px; /* Dikurangi dari 4px */
            margin-bottom: 0;
            page-break-after: avoid;
        }

        /* Invoice container dengan tinggi maksimal */
        .invoice-box {
            padding: 0;
            line-height: 0;
            overflow: hidden;
            page-break-inside: avoid;
            height: 720px; /* Tinggi ditambah lagi */
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #000;
            background-color: #f8f8f8;
        }

        /* Hide legacy blocks we keep for reference */
        .hide-old {
            display: none;
        }

        img.invoice {
            width: auto;
            max-width: 100%;
            height: auto;
            max-height: 710px; /* Sesuaikan dengan container */
            object-fit: contain;
            display: block;
            margin: 0;
            page-break-inside: avoid;
        }

        /* Adjust spacing untuk memaksimalkan ruang */
        .section {
            margin-bottom: 12px; /* Dikurangi dari 15px */
        }

        .split-section {
            margin-bottom: 8px; /* Dikurangi dari 10px */
        }

        /* Perbaikan untuk info-table yang lebih compact */
        .info-table tr td {
            padding: 1px 4px !important; /* Dikurangi dari 2px 4px */
        }

        /* Reduce margin untuk approval status boxes */
        .approval-box-margin {
            margin-top: 6px !important; /* Dikurangi dari 10px */
        }
    </style>
</head>

<body>
    <div class="pdf-header">
        @include('components.pdf.letterhead')
    </div>

    @if($reimbursement->marked_down)
    <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            z-index: 9999;
        ">
        <img src="{{ public_path('yaztech-logo-web.png') }}" alt="Yaztech Engineering Solusindo" style="
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    width: 12rem;
                    z-index: 100;
                    opacity: 1;
                ">
    </div>
    @endif

    <div class="section">
        <div class="sub-title">Reimbursement Request #RY{{ $reimbursement->id }} | {{
            \Carbon\Carbon::parse($reimbursement->created_at)->format('F d, Y \a\t H:i') }}</div>

        <table class="layout">
            <tr>
                <!-- Employee Information -->
                <td>
                    <h3 style="margin: 0 0 4px 0;">Employee Information</h3>
                    <table class="info-table" style="width: 100%; border-collapse: collapse; margin-top: 2px;">
                        <tr>
                            <td style="padding: 1px 4px; vertical-align: top; width: 30%;"><span
                                    class="label">Email:</span></td>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->email ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="label">Name:</span></td>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->name ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="label">Approver 1:</span>
                            </td>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->approver->name ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="label">Divisi:</span></td>
                            <td style="padding: 1px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->division->name ?? 'N/A' }}</span></td>
                        </tr>
                    </table>
                </td>

                <!-- Approval Status -->
                <td>
                    <h3 style="margin: 0 0 4px 0;">Approval Status</h3>
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

                    <div style="margin-top: 6px;"><span class="label">Approver 2 Status:</span></div>
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

    <!-- Reimbursement Details (left) and Invoice (right) side-by-side -->
    <div class="section split-section">
        <table class="split">
            <tr>
                <td class="left-col">
                    <h3 style="margin: 0 0 4px 0;">Reimbursement Details</h3>

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
                    <h3 style="margin: 0 0 4px 0;">Invoice</h3>
                    @php
                        $path = storage_path('app/public/' . $reimbursement->invoice_path);
                        $base64 = '';
                        if (file_exists($path)) {
                            $type = pathinfo($path, PATHINFO_EXTENSION);
                            $data = file_get_contents($path);
                            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                        }
                    @endphp
                    <div class="box invoice-box">
                        @if($base64)
                            <img src="{{ $base64 }}" alt="Invoice" class="invoice">
                        @else
                            <span style="color:#777; font-style: italic;">No image available</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Hidden legacy content -->
    <div class="section hide-old">
        <h3>Reimbursement Details</h3>
        <table class="details-table">
            <tr>
                <td class="label-col">Date of Expanse:</td>
                <td class="data-col">
                    <div class="box">{{ \Carbon\Carbon::parse($reimbursement->date)->format('l, M d, Y') }}</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Customer:</td>
                <td class="data-col">
                    <div class="box">{{ $reimbursement->customer ?? 'N/A' }}</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Total Amount:</td>
                <td class="data-col">
                    <div class="box">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Type Reimbursement:</td>
                <td class="data-col">
                    <div class="box">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section page-break hide-old">
        <h3>Invoice</h3>
        @php
            $path = storage_path('app/public/' . $reimbursement->invoice_path);
            $base64 = '';
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        @endphp
        <div class="box" style="padding:0; line-height:0; overflow:hidden;">
            @if($base64)
                <img src="{{ $base64 }}" alt="Invoice" class="invoice">
            @else
                <span style="color:#777; font-style: italic;">No image available</span>
            @endif
        </div>
    </div>
</body>
</html>
