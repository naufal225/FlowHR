@extends('employee.layouts.app')

@section('title', 'Attendance History')
@section('header', 'Attendance History')
@section('subtitle', 'Review attendance history and correction requests')

@section('content')
<div class="space-y-6">
    @include('components.attendance.page-header', [
        'eyebrow' => 'Employee Attendance',
        'title' => 'Attendance History',
        'subtitle' => 'Filter your attendance records, inspect suspicious states, and open record details for correction.',
    ])

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('employee.attendance.history') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="start_date">Start Date</label>
                <input id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] ?? '' }}"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="end_date">End Date</label>
                <input id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] ?? '' }}"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="record_status">Record Status</label>
                <select id="record_status" name="record_status"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All statuses</option>
                    <option value="complete" @selected(($filters['record_status'] ?? '') === 'complete')>Complete</option>
                    <option value="ongoing" @selected(($filters['record_status'] ?? '') === 'ongoing')>Ongoing</option>
                    <option value="incomplete" @selected(($filters['record_status'] ?? '') === 'incomplete')>Incomplete</option>
                </select>
            </div>
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="is_suspicious">Suspicious</label>
                <select id="is_suspicious" name="is_suspicious"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['is_suspicious'] ?? '') === '1')>Suspicious only</option>
                    <option value="0" @selected(($filters['is_suspicious'] ?? '') === '0')>Non suspicious only</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route('employee.attendance.history') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.7fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check In</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check Out</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Late</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Early Leave</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($records as $row)
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
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $row['late_label'] }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $row['early_leave_label'] }}</td>
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
                                <td colspan="7" class="px-4 py-8">
                                    @include('components.attendance.empty-state', [
                                        'title' => 'No attendance record found',
                                        'description' => 'Adjust the date range or filters to find the attendance history you are looking for.',
                                        'icon' => 'fa-solid fa-calendar-xmark',
                                    ])
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="mt-4 border-t border-slate-100 pt-4">
                    {{ $records->links() }}
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @include('components.attendance.state-panel', [
                'title' => 'Need a correction?',
                'description' => 'Open any attendance detail to submit a correction request with the exact check-in or check-out time. Correction remains available without keeping the old combined attendance page.',
                'icon' => 'fa-solid fa-pen-to-square',
                'classes' => 'border-sky-200 bg-sky-50',
                'iconClasses' => 'bg-sky-100 text-sky-700',
            ])

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Recent Corrections</h2>
                <p class="mt-1 text-sm text-slate-600">Latest correction requests tied to your attendance records.</p>

                <div class="mt-5 space-y-3">
                    @forelse($recentCorrections as $correction)
                        @php
                            $badge = match($correction->status) {
                                'approved' => ['label' => 'Approved', 'icon' => 'fa-solid fa-circle-check', 'pill_classes' => 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'],
                                'rejected' => ['label' => 'Rejected', 'icon' => 'fa-solid fa-circle-xmark', 'pill_classes' => 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200'],
                                default => ['label' => 'Pending', 'icon' => 'fa-solid fa-clock', 'pill_classes' => 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'],
                            };
                        @endphp
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $correction->attendance?->work_date?->translatedFormat('D, d M Y') ?? 'Attendance record' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">Submitted {{ $correction->created_at?->translatedFormat('d M Y H:i') }}</p>
                                </div>
                                @include('components.attendance.status-badge', ['badge' => $badge])
                            </div>
                            <p class="mt-3 text-sm text-slate-600">{{ $correction->reason }}</p>
                        </div>
                    @empty
                        @include('components.attendance.empty-state', [
                            'title' => 'No correction request yet',
                            'description' => 'Correction requests will appear here after you submit them from the attendance detail page.',
                            'icon' => 'fa-solid fa-file-circle-plus',
                        ])
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
