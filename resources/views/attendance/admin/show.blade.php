@extends($layout)

@section('title', 'Attendance Detail')
@section('header', 'Attendance Detail')
@section('subtitle', 'Audit and investigate a single attendance record')

@section('content')
<div class="space-y-6">
    @include('components.attendance.page-header', [
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
        'backHref' => route($routePrefix . '.attendance.records'),
        'backLabel' => 'Back to records',
        'sideMeta' => ['label' => 'Work Date', 'value' => $detail['date']],
    ])

    @include('components.attendance.flash-messages')

    <div class="rounded-3xl border {{ $detail['primary_status']['surface_classes'] }} p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    @include('components.attendance.status-badge', ['badge' => $detail['primary_status']])
                    @foreach($detail['flags'] as $flag)
                        @include('components.attendance.status-badge', ['badge' => $flag])
                    @endforeach
                </div>
                @if($detail['suspicious_reason'])
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ $detail['suspicious_reason'] }}</p>
                @endif
            </div>
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[30rem]">
                @foreach($detail['summary'] as $summaryItem)
                    @include('components.attendance.info-item', ['label' => $summaryItem['label'], 'value' => $summaryItem['value']])
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.2fr_1fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Employee Information</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @include('components.attendance.info-item', ['label' => 'Employee', 'value' => $detail['employee']['name'], 'helper' => $detail['employee']['email']])
                @include('components.attendance.info-item', ['label' => 'Office', 'value' => $detail['employee']['office']])
                @include('components.attendance.info-item', ['label' => 'Division', 'value' => $detail['employee']['division']])
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Check-in Detail</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @include('components.attendance.info-item', ['label' => 'Actual Time', 'value' => $detail['check_in']['time']])
                @include('components.attendance.info-item', ['label' => 'Recorded At', 'value' => $detail['check_in']['recorded_at']])
                @include('components.attendance.info-item', ['label' => 'Latitude', 'value' => $detail['check_in']['latitude']])
                @include('components.attendance.info-item', ['label' => 'Longitude', 'value' => $detail['check_in']['longitude']])
                @include('components.attendance.info-item', ['label' => 'Accuracy', 'value' => $detail['check_in']['accuracy']])
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Check-out Detail</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @include('components.attendance.info-item', ['label' => 'Actual Time', 'value' => $detail['check_out']['time']])
                @include('components.attendance.info-item', ['label' => 'Recorded At', 'value' => $detail['check_out']['recorded_at']])
                @include('components.attendance.info-item', ['label' => 'Latitude', 'value' => $detail['check_out']['latitude']])
                @include('components.attendance.info-item', ['label' => 'Longitude', 'value' => $detail['check_out']['longitude']])
                @include('components.attendance.info-item', ['label' => 'Accuracy', 'value' => $detail['check_out']['accuracy']])
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr_1.85fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Audit Metadata</h2>
                <div class="mt-5 grid grid-cols-1 gap-3">
                    @include('components.attendance.info-item', ['label' => 'QR Generated At', 'value' => $detail['qr']['generated_at']])
                    @include('components.attendance.info-item', ['label' => 'QR Expires At', 'value' => $detail['qr']['expired_at']])
                    @include('components.attendance.info-item', ['label' => 'QR Token', 'value' => $detail['qr']['masked_token']])
                    @if($detail['notes'])
                        @include('components.attendance.info-item', ['label' => 'Notes', 'value' => $detail['notes']])
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Logs Timeline</h2>
            <p class="mt-1 text-sm text-slate-600">Chronological attendance logs with sensitive audit metadata enabled for admin roles.</p>
            <div class="mt-5">
                @include('components.attendance.logs-list', ['logs' => $detail['logs'], 'showSensitive' => true])
            </div>
        </div>
    </div>
</div>
@endsection

