@extends($layout)

@section('title', 'Attendance Records')
@section('header', 'Attendance Records')
@section('subtitle', 'Operational archive and cross-date attendance review')

@section('content')
<div class="space-y-6">

    @include('components.attendance.page-header', [
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
    ])

    @include('components.attendance.flash-messages')
    @php
        $isApproverScope = ($routePrefix ?? '') === 'approver';
    @endphp

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route($routePrefix . '.attendance.records') }}"
            class="grid grid-cols-1 gap-4 {{ $isApproverScope ? 'lg:grid-cols-2 xl:grid-cols-5' : 'lg:grid-cols-3 xl:grid-cols-6' }}">
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
                <label class="mb-2 block text-sm font-medium text-slate-700" for="user_id">Employee</label>
                <select id="user_id" name="user_id"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="">All employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected(($filters['user_id'] ?? '') == $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            @unless($isApproverScope)
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="office_location_id">Office</label>
                    <select id="office_location_id" name="office_location_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">All offices</option>
                        @foreach($officeLocations as $office)
                            <option value="{{ $office->id }}" @selected(($filters['office_location_id'] ?? '') == $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
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
            <div class="flex items-end gap-2 {{ $isApproverScope ? 'lg:col-span-2 xl:col-span-5' : 'lg:col-span-3 xl:col-span-6' }}">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply Filters</span>
                </button>
                <a href="{{ route($routePrefix . '.attendance.records') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Office</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check In</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check Out</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Record</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Flags</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($records as $row)
                        <tr class="hover:bg-slate-50 {{ collect($row['flags'])->pluck('key')->contains('suspicious') ? 'bg-red-50/50' : '' }}">
                            <td class="px-4 py-4 text-sm font-medium text-slate-900">{{ $row['date'] }}</td>
                            <td class="px-4 py-4 text-sm">
                                <p class="font-semibold text-slate-900">{{ $row['employee_name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $row['employee_email'] }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $row['office_name'] }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_in'] }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_out'] }}</td>
                            <td class="px-4 py-4 text-sm">
                                <div class="space-y-2">
                                    @include('components.attendance.status-badge', ['badge' => $row['primary_status']])
                                    <p class="text-xs text-slate-500">{{ $row['record_status_label'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($row['flags'] as $flag)
                                        @include('components.attendance.status-badge', ['badge' => $flag])
                                    @endforeach
                                    @if(empty($row['flags']))
                                        <span class="text-xs text-slate-400">No flag</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <a href="{{ route($routePrefix . '.attendance.show', $row['id']) }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Detail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8">
                                @include('components.attendance.empty-state', ['title' => 'No attendance record found', 'description' => 'The current filter combination did not return any attendance record.', 'icon' => 'fa-solid fa-database'])
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
</div>
@endsection




