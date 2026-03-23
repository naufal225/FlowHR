<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Official Travel Request #TY{{ $travel->id }}</title>
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
            text-align: center;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            vertical-align: top;
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

        .status-pending { color: orange; }

        .pdf-header { position: fixed; top: -110px; left:0; right:0; }
        /* Footer drawn via script */

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
            width: 35%;
            font-weight: bold;
        }

        /* Kolom data mengambil sisa ruang */
        table.details-table td.data-col {
            width: 65%;
        }

        /* --- Gaya untuk catatan di bawah --- */
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
        }

        /* Gaya khusus untuk box catatan */
        .note-box {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 4px;
            background-color: #f8f8f8;
            page-break-inside: avoid;
            word-wrap: break-word;
            min-height: 40px;
        }

        .note-label {
            font-weight: bold;
            font-style: italic;
            display: block;
        }

        .note-content {
            display: block;
            white-space: pre-wrap;
        }

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

@if($travel->marked_down)
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            z-index: 9999;
        ">
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
        <div class="sub-title">Official Travel Request #TY{{ $travel->id }} | {{
            \Carbon\Carbon::parse($travel->created_at)->format('F d, Y \a\t H:i') }}</div>

        <table class="layout">
            <tr>
                <!-- Employee Information -->
                <td>
                    <h3>Employee Information</h3>
                    <table class="details-table">
                        <tr>
                            <td class="label-col">Email:</td>
                            <td class="data-col">{{ $travel->employee->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Name:</td>
                            <td class="data-col">{{ $travel->employee->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Approver 1:</td>
                            <td class="data-col">{{ $travel->approver->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Divisi:</td>
                            <td class="data-col">{{ $travel->employee->division->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- Approval Status -->
                <td>
                    <h3>Approval Status</h3>
                    <div><span class="label">Approver 1 Status:</span></div>
                    <div class="box status-{{ $travel->status_1 }}">
                        @if($travel->status_1 === 'pending')
                        Pending Review
                        @elseif($travel->status_1 === 'approved')
                        Approved
                        @elseif($travel->status_1 === 'rejected')
                        Rejected
                        @endif
                    </div>

                    <div style="margin-top: 10px;"><span class="label">Approver 2 Status:</span></div>
                    <div class="box status-{{ $travel->status_2 }}">
                        @if($travel->status_2 === 'pending')
                        Pending Review
                        @elseif($travel->status_2 === 'approved')
                        Approved
                        @elseif($travel->status_2 === 'rejected')
                        Rejected
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Official Travel Details</h3>
        <!-- Gunakan tabel untuk detail -->
        <table class="details-table">
            <tr>
                <td class="label-col">Start Date:</td>
                <td class="data-col">
                    <div class="box">{{ \Carbon\Carbon::parse($travel->date_start)->format('l, M d, Y') }}</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">End Date:</td>
                <td class="data-col">
                    <div class="box">{{ \Carbon\Carbon::parse($travel->date_end)->format('l, M d, Y') }}</div>
                </td>
            </tr>
            <tr>
                @php
                $startDate = \Carbon\Carbon::parse($travel->date_start);
                $endDate = \Carbon\Carbon::parse($travel->date_end);
                $durationDays = $startDate->diffInDays($endDate) + 1; // +1 untuk inklusif
                $travelRatePerDay = (int) env('TRAVEL_COSTS_PER_DAY', 125000);
                @endphp
                <td class="label-col">Duration (Days):</td>
                <td class="data-col">
                    <div class="box">{{ $durationDays }} day(s)</div>
                </td>
            </tr>
            <tr>
                <td class="label-col">Travel Rate (Rp {{ number_format($travelRatePerDay, 0, ',', '.') }}/day):</td>
                <td class="data-col">
                    <div class="box">Rp {{ number_format($travelRatePerDay * $durationDays, 0, ',', '.') }}</div>
                </td>
            </tr>
            
        </table>
    </div>

    <!-- Catatan di bawah -->
    <div class="notes-section">
        <h3>Notes</h3>
        <table class="grid-2">
            <tr>
                <td>
                    <div><span class="note-label">Note from Approver 1:</span></div>
                    <div class="note-box">
                        @if($travel->note_1)
                        <span class="note-content">{{ $travel->note_1 }}</span>
                        @else
                        <span class="no-note">No note provided.</span>
                        @endif
                    </div>
                </td>
                <td>
                    <div><span class="note-label">Note from Approver 2:</span></div>
                    <div class="note-box">
                        @if($travel->note_2)
                        <span class="note-content">{{ $travel->note_2 }}</span>
                        @else
                        <span class="no-note">No note provided.</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <!-- Akhir Catatan -->

    

</body>

</html>

