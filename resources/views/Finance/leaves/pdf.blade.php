<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Request #LY{{ $leave->id }}</title>
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
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .pdf-header { position: fixed; top: -110px; left: 0; right: 0; }
        /* Footer drawn via script */
    </style>
</head>
<body>

    <div class="pdf-header">
        @include('components.pdf.letterhead')
    </div>

    @if($leave->status_1 === 'approved')
        @include('components.pdf.watermark')
    @endif

    <div class="section">
        <div class="sub-title">Leave Request #LY{{ $leave->id }} | {{ \Carbon\Carbon::parse($leave->created_at)->format('F d, Y \a\t H:i') }}</div>
        <h3>Employee Information</h3>
        <div><span class="label">Email:</span> <span class="value">{{ $leave->employee->email }}</span></div>
        <div><span class="label">Name:</span> <span class="value">{{ $leave->employee->name }}</span></div>
        <div><span class="label">Divisi:</span> <span class="value">{{ $leave->employee->division->name ?? 'N/A' }}</span></div>
    </div>

    
    <div class="section">
        <h3>Leave Details</h3>
        <div class="grid-2">
            <div>
                <div><span class="label">Start Date:</span></div>
                <div class="box">{{ \Carbon\Carbon::parse($leave->date_start)->format('l, M d, Y') }}</div>
            </div>
            <div>
                <div><span class="label">End Date:</span></div>
                <div class="box">{{ \Carbon\Carbon::parse($leave->date_end)->format('l, M d, Y') }}</div>
            </div>
            <div>
                <div><span class="label">Duration:</span></div>
                <div class="box">
                    {{ (int) \Carbon\Carbon::parse($leave->date_start)->diffInDays(\Carbon\Carbon::parse($leave->date_end, ), false) + 1 }}
                    {{ (int) \Carbon\Carbon::parse($leave->date_start)->diffInDays(\Carbon\Carbon::parse($leave->date_end, ), false) + 1 === 1 ? 'day' : 'days' }}
                </div>
            </div>
            <div>
                <div><span class="label">Reason for Leave:</span></div>
                <div class="box">{{ $leave->reason }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Approval Status</h3>
        <div class="grid-2">
            <div>
                <div><span class="label">Status:</span></div>
                <div class="box status-{{ $leave->status_1 }}">
                    @if($leave->status_1 === 'pending')
                        Pending Review
                    @elseif($leave->status_1 === 'approved')
                        Approved
                    @elseif($leave->status_1 === 'rejected')
                        Rejected
                    @endif
                </div>
            </div>
            <div>
                <div><span class="label">Note:</span></div>
                <div class="box">{{ $leave->note_1 ?? '-' }}</div>
            </div>
        </div>
    </div>

</body>
</html>
