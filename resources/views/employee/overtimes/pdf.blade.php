<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overtime Request #OY{{ $overtime->id }}</title>
    <style>
        @page { margin: 130px 30px 30px 30px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.6;
            margin: 0;
        }
        .header, .section {
            margin-bottom: 30px;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .title {
            font-size: 24px;
            text-align: center;
            font-weight: bold;
        }
        .sub-title {
            font-size: 11px;
            margin-bottom: 12px;
        }
        .label {
            font-weight: bold;
            width: 160px;
            display: inline-block;
        }
        .value {
            display: inline-block;
        }
        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 4px;
            background-color: #f8f8f8;
        }
        .status-approved { color: green; }
        .status-rejected { color: red; }
        .status-pending { color: orange; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .pdf-header { position: fixed; top: -110px; left: 0; right: 0; }
        /* Footer drawn via script */
    </style>
</head>
<body>

    <div class="pdf-header">
        @include('components.pdf.letterhead')
    </div>

    @if($overtime->marked_down)
        <div style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            z-index: 9999;
        ">
            <div style="
                    position: absolute;
                    bottom: 20px;
                    left: 20px;
                    font-size: 10px;
                    color: #444;
                ">
                Request #OY{{ $overtime->id }} | {{ \Carbon\Carbon::parse($overtime->created_at)->format('F d, Y \a\t H:i') }} <br>
                {{ $overtime->employee->email }}
            </div>
            <img src="{{ public_path('yaztech-logo-web.png') }}" 
                alt="Yaztech Engineering Solusindo"
                style="
                    position: absolute;
                    bottom: 20px;
                    right: 20px;
                    width: 12rem;
                    opacity: 0.3;
                ">
        </div>
    @endif

    <div class="section">
        <div class="sub-title">Overtime Request #OY{{ $overtime->id }} | {{ \Carbon\Carbon::parse($overtime->created_at)->format('F d, Y \a\t H:i') }}</div>
        <h3>Employee Information</h3>
        <div><span class="label">Email:</span> <span class="value">{{ $overtime->employee->email }}</span></div>
        <div><span class="label">Name:</span> <span class="value">{{ $overtime->employee->name }}</span></div>
        <div><span class="label">Approver 1:</span> <span class="value">{{ $overtime->approver->name ?? 'N/A' }}</span></div>
        <div><span class="label">Divisi:</span> <span class="value">{{ $overtime->employee->division->name ?? 'N/A' }}</span></div>
    </div>

    

    <div class="section">
        <h3>Overtime Details</h3>
        <div class="grid-2">
            <div>
                <div><span class="label">Start s/d End Date:</span></div>
                <div class="box">{{ \Carbon\Carbon::parse($overtime->date_start)->format('l, M d, Y \a\t H:i') }} <b>s/d</b> {{ \Carbon\Carbon::parse($overtime->date_end)->format('l, M d, Y \a\t H:i') }}</div>
            </div>
            <div>
                <div><span class="label">Total Overtime Hours:</span></div>
                @php
                    // Parsing waktu input
                    $start = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_start, 'Asia/Jakarta');
                    $end = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_end, 'Asia/Jakarta');

                    // Hitung langsung dari date_start
                    $overtimeMinutes = $start->diffInMinutes($end);
                    $overtimeHours = $overtimeMinutes / 60;

                    $hours = floor($overtimeMinutes / 60);
                    $minutes = $overtimeMinutes % 60;
                @endphp

                <div class="box">{{ $hours }} jam {{ $minutes }} menit</div>
            </div>
            <div>
                <div><span class="label">Customer:</span></div>
                <div class="box">{{ $overtime->customer }}</div>
            </div>
            <div>
                <div><span class="label">Total Amount:</span></div>
                <div class="box">
                    {{ 'Rp ' . number_format($overtime->total ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Approval Status</h3>
        <div class="grid-2">
            <div>
                <div><span class="label">Approver 1 Status:</span></div>
                <div class="box status-{{ $overtime->status_1 }}">
                    @if($overtime->status_1 === 'pending')
                        Pending Review
                    @elseif($overtime->status_1 === 'approved')
                        Approved
                    @elseif($overtime->status_1 === 'rejected')
                        Rejected
                    @endif
                </div>
            </div>
            <div>
                <div><span class="label">Approver 2 Status:</span></div>
                <div class="box status-{{ $overtime->status_2 }}">
                    @if($overtime->status_2 === 'pending')
                        Pending Review
                    @elseif($overtime->status_2 === 'approved')
                        Approved
                    @elseif($overtime->status_2 === 'rejected')
                        Rejected
                    @endif
                </div>
            </div>
            <div>
                <div><span class="label">Approver 1 Note:</span></div>
                <div class="box">{{ $overtime->note_1 ?? '-' }}</div>
            </div>
            <div>
                <div><span class="label">Approver 2 Note:</span></div>
                <div class="box">{{ $overtime->note_2 ?? '-' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
