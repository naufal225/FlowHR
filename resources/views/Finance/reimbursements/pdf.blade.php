<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reimbursement Request #RY{{ $reimbursement->id }}</title>
    <style>
        @page { margin: 130px 30px 30px 30px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.6;
            margin: 0;
        }

        .header,
        .section {
            margin-bottom: 20px;
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
            margin-bottom: 12px;
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
            /* Tambahkan untuk mencegah overflow teks */
        }

        .status-approved {
            color: green;
        }

        .status-rejected {
            color: red;
        }

        .status-pending { color: orange; }

        .pdf-header { position: fixed; top: -110px; left: 0; right: 0; }
        /* Footer drawn via Dompdf script */

        .page-break {
            page-break-before: always;
            break-before: page;
        }

        table.layout {
            width: 100%;
            border-collapse: collapse;
        }

        table.layout td {
            vertical-align: top;
            padding: 4px 8px;
        }

        /* --- Gaya untuk tabel detail --- */
        table.details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        table.details-table td {
            padding: 4px 6px;
            vertical-align: top;
        }

        /* Kolom label kecil */
        table.details-table td.label-col {
            width: 30%;
            font-weight: bold;
        }

        /* Kolom data mengambil sisa ruang */
        table.details-table td.data-col {
            width: 70%;
        }

        img.invoice {
            width: auto;
            max-width: 100%;
            height: auto;
            max-height: 640px;
            display: block;
            margin: 0;
            object-fit: contain;
            page-break-inside: avoid;
        }

        /* --- Gaya untuk catatan di bawah invoice --- */
        .notes-section {
            margin-top: 20px;
        }

        .notes-section h3 {
            margin-bottom: 10px;
        }

        /* Gaya untuk tabel dua kolom catatan */
        table.grid-2 {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid-2 td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px;
            /* Memberi sedikit jarak antar kolom */
        }

        /* Gaya khusus untuk box catatan */
        .note-box {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 4px;
            background-color: #f8f8f8;
            /* Sama dengan .box */
            page-break-inside: avoid;
            word-wrap: break-word;
            min-height: 40px;
            /* Tinggi minimum untuk konsistensi jika kosong */
        }

        .note-label {
            font-weight: bold;
            font-style: italic;
            display: block;
        }

        .note-content {
            display: block;
            white-space: pre-wrap;
            /* Agar line break (\n) terlihat */
        }

        /* Untuk memastikan tidak ada teks di luar box */
        .no-note {
            font-style: italic;
            color: #777;
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
            {{-- <div style="
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    font-size: 10px;
                    color: #444;
                    z-index: 50;
                ">
                Request #RY{{ $reimbursement->id }} | {{ \Carbon\Carbon::parse($reimbursement->created_at)->format('F d, Y \a\t H:i') }} <br>
                {{ $reimbursement->employee->email }}
            </div> --}}
            <img src="{{ public_path('yaztech-logo-web.png') }}" 
                alt="Yaztech Engineering Solusindo"
                style="
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
                    <h3>Employee Information</h3>
                    <table class="info-table" style="width: 100%; border-collapse: collapse; margin-top: 4px;">
                        <tr>
                            <td style="padding: 2px 4px; vertical-align: top; width: 30%;"><span
                                    class="label">Email:</span></td>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->email ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="label">Name:</span></td>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->name ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="label">Approver 1:</span>
                            </td>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->approver->name ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="label">Divisi:</span></td>
                            <td style="padding: 2px 4px; vertical-align: top;"><span class="text-data">{{
                                    $reimbursement->employee->division->name ?? 'N/A' }}</span></td>
                        </tr>
                    </table>
                </td>

                <!-- Approval Status -->
                <td>
                    <h3>Approval Status</h3>
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

                    <div style="margin-top: 10px;"><span class="label">Approver 2 Status:</span></div>
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
        <h3>Reimbursement Details</h3>
        <!-- Gunakan tabel untuk detail -->
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
                <td class="label-col">Type Reimbursement:</td>
                <td class="data-col">
                    <div class="box">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Total Amount:</td>
                <td class="data-col">
                    <div class="box">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Invoice moved to a dedicated page -->
    <div class="section page-break">
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
