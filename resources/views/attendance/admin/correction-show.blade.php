@extends($layout)

@section('title', 'Attendance Correction Detail')
@section('header', 'Attendance Correction Detail')
@section('subtitle', 'Review a single attendance correction request')

@section('content')
<div class="space-y-6">
    @php
        $badge = $statusBadgeMap[$correction->status] ?? $statusBadgeMap['pending'];
        $requestedCheckIn = $correction->requested_check_in_time?->setTimezone('Asia/Jakarta')->translatedFormat('D, d M Y H:i') ?? '-';
        $requestedCheckOut = $correction->requested_check_out_time?->setTimezone('Asia/Jakarta')->translatedFormat('D, d M Y H:i') ?? '-';
    @endphp

    @include('components.attendance.page-header', [
        'eyebrow' => strtoupper(str_replace('-', ' ', $routePrefix)) . ' Attendance',
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
        'backHref' => route($routePrefix . '.attendance.corrections.index'),
        'backLabel' => 'Back to corrections',
        'sideMeta' => ['label' => 'Submitted', 'value' => $correction->created_at?->setTimezone('Asia/Jakarta')->translatedFormat('D, d M Y H:i') ?? '-'],
    ])

    @include('components.attendance.flash-messages')

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-3">
                @include('components.attendance.status-badge', ['badge' => $badge])
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $correction->attendance?->user?->name ?? '-' }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $correction->attendance?->user?->email ?? '-' }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $correction->attendance?->work_date?->translatedFormat('D, d M Y') ?? '-' }} · {{ $correction->attendance?->officeLocation?->name ?? '-' }}</p>
                </div>
            </div>
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[32rem]">
                @include('components.attendance.info-item', ['label' => 'Requested Check In', 'value' => $requestedCheckIn])
                @include('components.attendance.info-item', ['label' => 'Requested Check Out', 'value' => $requestedCheckOut])
                @include('components.attendance.info-item', ['label' => 'Reviewed By', 'value' => $correction->reviewer?->name ?? '-'])
                @include('components.attendance.info-item', ['label' => 'Reviewed At', 'value' => $correction->reviewed_at?->setTimezone('Asia/Jakarta')->translatedFormat('D, d M Y H:i') ?? '-'])
            </div>
        </div>
        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee Reason</p>
            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $correction->reason }}</p>
            @if($correction->reviewer_note)
                <div class="mt-4 border-t border-slate-200 pt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reviewer Note</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ $correction->reviewer_note }}</p>
                </div>
            @endif
        </div>
    </div>

    @if($correction->status === 'pending')
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Approve Correction</h2>
                <p class="mt-1 text-sm text-slate-600">Approving will update the live attendance record and recalculate its operational status.</p>
                <form method="POST" action="{{ route($routePrefix . '.attendance.corrections.review', $correction->id) }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="approve_reviewer_note">Reviewer Note</label>
                        <textarea id="approve_reviewer_note" name="reviewer_note" rows="4"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            placeholder="Optional note for the employee.">{{ old('action') === 'approve' ? old('reviewer_note') : '' }}</textarea>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Approve and Apply</span>
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-rose-200 bg-rose-50 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Reject Correction</h2>
                <p class="mt-1 text-sm text-slate-600">Rejecting will keep the current attendance record unchanged and require a reviewer note.</p>
                <form method="POST" action="{{ route($routePrefix . '.attendance.corrections.review', $correction->id) }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="reject_reviewer_note">Reviewer Note</label>
                        <textarea id="reject_reviewer_note" name="reviewer_note" rows="4"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                            placeholder="Explain why this correction is rejected.">{{ old('action') === 'reject' ? old('reviewer_note') : '' }}</textarea>
                        @error('reviewer_note')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Reject Request</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Original Snapshot</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @include('components.attendance.info-item', ['label' => 'Check In', 'value' => $originalSnapshot['check_in_at']])
                @include('components.attendance.info-item', ['label' => 'Check Out', 'value' => $originalSnapshot['check_out_at']])
                @include('components.attendance.info-item', ['label' => 'Check-in Status', 'value' => $originalSnapshot['check_in_status']])
                @include('components.attendance.info-item', ['label' => 'Check-out Status', 'value' => $originalSnapshot['check_out_status']])
                @include('components.attendance.info-item', ['label' => 'Record Status', 'value' => $originalSnapshot['record_status']])
                @include('components.attendance.info-item', ['label' => 'Late Minutes', 'value' => $originalSnapshot['late_minutes']])
                @include('components.attendance.info-item', ['label' => 'Early Leave', 'value' => $originalSnapshot['early_leave_minutes']])
                @include('components.attendance.info-item', ['label' => 'Overtime', 'value' => $originalSnapshot['overtime_minutes']])
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Requested Final Values</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @include('components.attendance.info-item', ['label' => 'Requested Check In', 'value' => $requestedCheckIn])
                @include('components.attendance.info-item', ['label' => 'Requested Check Out', 'value' => $requestedCheckOut])
                @include('components.attendance.info-item', ['label' => 'Current Live Check In', 'value' => $detail['check_in']['time']])
                @include('components.attendance.info-item', ['label' => 'Current Live Check Out', 'value' => $detail['check_out']['time']])
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Live Attendance Summary</h2>
            <div class="mt-5 grid grid-cols-1 gap-3">
                @foreach($detail['summary'] as $summaryItem)
                    @include('components.attendance.info-item', ['label' => $summaryItem['label'], 'value' => $summaryItem['value']])
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.15fr_1.85fr]">
        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Employee Information</h2>
                <div class="mt-5 grid grid-cols-1 gap-3">
                    @include('components.attendance.info-item', ['label' => 'Employee', 'value' => $detail['employee']['name'], 'helper' => $detail['employee']['email']])
                    @include('components.attendance.info-item', ['label' => 'Office', 'value' => $detail['employee']['office']])
                    @include('components.attendance.info-item', ['label' => 'Division', 'value' => $detail['employee']['division']])
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
