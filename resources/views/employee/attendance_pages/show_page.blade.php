@extends('employee.layouts.app')

@section('title', 'Attendance Detail')
@section('header', 'Attendance Detail')
@section('subtitle', 'Daily attendance transparency and correction')

@section('content')
<div class="space-y-6">
    @include('components.attendance.page-header', [
        'title' => 'Attendance Detail',
        'subtitle' => 'Review the full daily record before submitting any correction.',
        'backHref' => route('employee.attendance.history'),
        'backLabel' => 'Back to history',
        'sideMeta' => ['label' => 'Work Date', 'value' => $detail['date']],
    ])

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
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:w-[28rem]">
                @foreach($detail['summary'] as $summaryItem)
                    @include('components.attendance.info-item', ['label' => $summaryItem['label'], 'value' => $summaryItem['value']])
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Check-in Information</h2>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @include('components.attendance.info-item', ['label' => 'Actual Time', 'value' => $detail['check_in']['time']])
                @include('components.attendance.info-item', ['label' => 'Recorded At', 'value' => $detail['check_in']['recorded_at']])
                @include('components.attendance.info-item', ['label' => 'Latitude', 'value' => $detail['check_in']['latitude']])
                @include('components.attendance.info-item', ['label' => 'Longitude', 'value' => $detail['check_in']['longitude']])
                @include('components.attendance.info-item', ['label' => 'Accuracy', 'value' => $detail['check_in']['accuracy']])
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Check-out Information</h2>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @include('components.attendance.info-item', ['label' => 'Actual Time', 'value' => $detail['check_out']['time']])
                @include('components.attendance.info-item', ['label' => 'Recorded At', 'value' => $detail['check_out']['recorded_at']])
                @include('components.attendance.info-item', ['label' => 'Latitude', 'value' => $detail['check_out']['latitude']])
                @include('components.attendance.info-item', ['label' => 'Longitude', 'value' => $detail['check_out']['longitude']])
                @include('components.attendance.info-item', ['label' => 'Accuracy', 'value' => $detail['check_out']['accuracy']])
            </div>
        </div>
    </div>

    @if($detail['notes'])
        @include('components.attendance.state-panel', ['title' => 'Attendance Note', 'description' => $detail['notes'], 'icon' => 'fa-solid fa-note-sticky', 'classes' => 'border-slate-200 bg-slate-50', 'iconClasses' => 'bg-slate-100 text-slate-700'])
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.55fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Attendance Timeline</h2>
            <p class="mt-1 text-sm text-slate-600">Only non-sensitive log metadata is shown on the employee side.</p>
            <div class="mt-5">
                @include('components.attendance.logs-list', ['logs' => $detail['logs'], 'showSensitive' => false])
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Request Correction</h2>
                <p class="mt-1 text-sm text-slate-600">Submit a correction only if this record is inaccurate.</p>

                @if($hasPendingCorrection)
                    @include('components.attendance.state-panel', ['title' => 'Pending correction already exists', 'description' => 'This attendance record already has a pending correction request, so a new one cannot be submitted yet.', 'icon' => 'fa-solid fa-clock', 'classes' => 'mt-5 border-amber-200 bg-amber-50', 'iconClasses' => 'bg-amber-100 text-amber-700'])
                @else
                    <form method="POST" action="{{ route('employee.attendance.corrections.store') }}" class="mt-5 space-y-4">
                        @csrf
                        <input type="hidden" name="attendance_record_id" value="{{ $attendanceRecord->id }}">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700" for="requested_check_in_time">Corrected Check In</label>
                                <input id="requested_check_in_time" name="requested_check_in_time" type="datetime-local"
                                    value="{{ old('requested_check_in_time', $attendanceRecord->check_in_at?->format('Y-m-d\TH:i')) }}"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('requested_check_in_time')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700" for="requested_check_out_time">Corrected Check Out</label>
                                <input id="requested_check_out_time" name="requested_check_out_time" type="datetime-local"
                                    value="{{ old('requested_check_out_time', $attendanceRecord->check_out_at?->format('Y-m-d\TH:i')) }}"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('requested_check_out_time')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700" for="reason">Reason</label>
                                <textarea id="reason" name="reason" rows="4"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                    placeholder="Explain why this record needs correction.">{{ old('reason') }}</textarea>
                                @error('reason')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('attendance_record_id')
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>Submit Correction</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Correction History</h2>
                <div class="mt-5 space-y-3">
                    @forelse($corrections as $correction)
                        @php
                            $badge = match($correction->status) {
                                'approved' => ['label' => 'Approved', 'icon' => 'fa-solid fa-circle-check', 'pill_classes' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'],
                                'rejected' => ['label' => 'Rejected', 'icon' => 'fa-solid fa-circle-xmark', 'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200'],
                                default => ['label' => 'Pending', 'icon' => 'fa-solid fa-clock', 'pill_classes' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'],
                            };
                        @endphp
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900">{{ $correction->created_at?->translatedFormat('d M Y H:i') }}</p>
                                @include('components.attendance.status-badge', ['badge' => $badge])
                            </div>
                            <p class="mt-3 text-sm text-slate-600">{{ $correction->reason }}</p>
                            @if($correction->reviewer_note)
                                <p class="mt-2 text-xs text-slate-500">Reviewer note: {{ $correction->reviewer_note }}</p>
                            @endif
                        </div>
                    @empty
                        @include('components.attendance.empty-state', ['title' => 'No correction submitted yet', 'description' => 'If this record is inaccurate, you can submit the first correction request from the panel above.', 'icon' => 'fa-solid fa-file-pen'])
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
