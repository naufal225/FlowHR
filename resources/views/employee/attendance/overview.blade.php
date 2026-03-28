@extends('employee.layouts.app')

@section('title', 'Attendance Overview')
@section('header', 'Attendance')
@section('subtitle', 'Today overview and operational attendance status')

@section('content')
@php
    $actionMap = [
        'not_checked_in' => [
            'title' => 'Check-in still pending',
            'description' => 'Attendance scanning happens in the mobile app. Use the mobile attendance flow to record check-in for today.',
            'icon' => 'fa-solid fa-mobile-screen-button',
            'classes' => 'border-rose-200 bg-rose-50',
            'iconClasses' => 'bg-rose-100 text-rose-700',
        ],
        'checked_in' => [
            'title' => 'Check-out still pending',
            'description' => 'Your check-in has been captured. Complete the day from mobile when your shift finishes.',
            'icon' => 'fa-solid fa-hourglass-half',
            'classes' => 'border-sky-200 bg-sky-50',
            'iconClasses' => 'bg-sky-100 text-sky-700',
        ],
        'complete' => [
            'title' => 'Attendance completed',
            'description' => 'Today attendance is already complete. Use the history page if you need to review or audit an older record.',
            'icon' => 'fa-solid fa-circle-check',
            'classes' => 'border-emerald-200 bg-emerald-50',
            'iconClasses' => 'bg-emerald-100 text-emerald-700',
        ],
        'on_leave' => [
            'title' => 'Covered by leave',
            'description' => 'You have an approved leave for today, so no attendance action is required.',
            'icon' => 'fa-solid fa-umbrella-beach',
            'classes' => 'border-slate-200 bg-slate-50',
            'iconClasses' => 'bg-slate-100 text-slate-700',
        ],
        'off_day' => [
            'title' => 'No attendance expected',
            'description' => 'Today is marked as a non-working day, so attendance scanning is not required.',
            'icon' => 'fa-solid fa-calendar-day',
            'classes' => 'border-slate-200 bg-slate-50',
            'iconClasses' => 'bg-slate-100 text-slate-700',
        ],
        'absent' => [
            'title' => 'Attendance requires review',
            'description' => 'No valid attendance record is available for today. Check your history and submit a correction only if the record is inaccurate.',
            'icon' => 'fa-solid fa-triangle-exclamation',
            'classes' => 'border-rose-200 bg-rose-50',
            'iconClasses' => 'bg-rose-100 text-rose-700',
        ],
        'config_issue' => [
            'title' => 'Attendance setup needs attention',
            'description' => $todayState['description'],
            'icon' => 'fa-solid fa-gear',
            'classes' => 'border-red-200 bg-red-50',
            'iconClasses' => 'bg-red-100 text-red-700',
        ],
    ];

    $actionPanel = $actionMap[$todayState['key']] ?? $actionMap['complete'];
@endphp

<div class="space-y-6">
    @include('components.attendance.page-header', [
        'eyebrow' => 'Employee Attendance',
        'title' => 'Attendance',
        'subtitle' => 'Today is the main operational page for your attendance status.',
        'sideMeta' => [
            'label' => 'Today',
            'value' => $todayState['date_label'],
        ],
    ])

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.8fr_1fr]">
        <div class="rounded-3xl border {{ $todayState['badge']['surface_classes'] }} p-6 shadow-sm">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        @include('components.attendance.status-badge', ['badge' => $todayState['badge']])
                        @foreach($todayState['flags'] as $flag)
                            @include('components.attendance.status-badge', ['badge' => $flag])
                        @endforeach
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">{{ $todayState['badge']['label'] }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $todayState['description'] }}</p>
                    </div>
                </div>
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                    @include('components.attendance.info-item', ['label' => 'Check In', 'value' => $todayState['check_in']])
                    @include('components.attendance.info-item', ['label' => 'Check Out', 'value' => $todayState['check_out']])
                </div>
            </div>
        </div>

        @include('components.attendance.state-panel', [
            'title' => $actionPanel['title'],
            'description' => $actionPanel['description'],
            'icon' => $actionPanel['icon'],
            'classes' => $actionPanel['classes'],
            'iconClasses' => $actionPanel['iconClasses'],
        ])
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.35fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Work Information</h2>
                    <p class="mt-1 text-sm text-slate-600">Policy and office rules currently used for attendance.</p>
                </div>
                <a href="{{ route('employee.attendance.history') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>View history</span>
                </a>
            </div>

            @if($policySummary)
                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @include('components.attendance.info-item', ['label' => 'Office', 'value' => $policySummary['office_name'], 'helper' => $policySummary['office_address']])
                    @include('components.attendance.info-item', ['label' => 'Work Start', 'value' => $policySummary['work_start']])
                    @include('components.attendance.info-item', ['label' => 'Work End', 'value' => $policySummary['work_end']])
                    @include('components.attendance.info-item', ['label' => 'Late Tolerance', 'value' => $policySummary['late_tolerance']])
                    @include('components.attendance.info-item', ['label' => 'Allowed Radius', 'value' => $policySummary['allowed_radius']])
                    @include('components.attendance.info-item', ['label' => 'Min Accuracy', 'value' => $policySummary['min_accuracy'], 'helper' => 'QR rotates every ' . $policySummary['qr_rotation']])
                </div>
            @else
                @include('components.attendance.empty-state', [
                    'title' => 'Attendance policy is not available',
                    'description' => $policyError ?: 'Your assigned office does not have an active attendance policy yet.',
                    'icon' => 'fa-solid fa-gear',
                ])
            @endif
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Attention Notes</h2>
            <div class="mt-5 space-y-3">
                @if($todayState['key'] === 'config_issue')
                    @include('components.attendance.state-panel', [
                        'title' => 'Attendance setup issue',
                        'description' => $todayState['description'],
                        'icon' => 'fa-solid fa-gear',
                        'classes' => 'border-red-200 bg-red-50',
                        'iconClasses' => 'bg-red-100 text-red-700',
                    ])
                @elseif(collect($todayState['flags'])->pluck('key')->contains('suspicious'))
                    @include('components.attendance.state-panel', [
                        'title' => 'Suspicious attendance detected',
                        'description' => 'The record for today was flagged as suspicious. Open the detail page or contact admin if you need this reviewed.',
                        'icon' => 'fa-solid fa-shield-halved',
                        'classes' => 'border-red-200 bg-red-50',
                        'iconClasses' => 'bg-red-100 text-red-700',
                    ])
                @else
                    @include('components.attendance.state-panel', [
                        'title' => 'No critical issue',
                        'description' => 'Your today attendance state does not show any urgent warning at the moment.',
                        'icon' => 'fa-solid fa-circle-info',
                        'classes' => 'border-slate-200 bg-slate-50',
                        'iconClasses' => 'bg-slate-100 text-slate-700',
                    ])
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Recent Attendance</h2>
                <p class="mt-1 text-sm text-slate-600">The latest attendance records for quick follow-up and correction review.</p>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check In</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check Out</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($recentHistory as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $row['date'] }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_in'] }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_out'] }}</td>
                            <td class="px-4 py-4 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    @include('components.attendance.status-badge', ['badge' => $row['primary_status']])
                                    @foreach($row['flags'] as $flag)
                                        @include('components.attendance.status-badge', ['badge' => $flag])
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <a href="{{ route('employee.attendance.show', $row['id']) }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8">
                                @include('components.attendance.empty-state', [
                                    'title' => 'No attendance history yet',
                                    'description' => 'Once you start using mobile attendance, the most recent records will appear here for quick review.',
                                    'icon' => 'fa-solid fa-calendar-days',
                                    'actionHref' => route('employee.attendance.history'),
                                    'actionLabel' => 'Open full history',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
