@extends($layout)

@section('title', 'Attendance Overview')
@section('header', 'Attendance Overview')
@section('subtitle', 'Monitoring today attendance status')

@section('content')
<div class="space-y-6">

    @include('components.attendance.page-header', [
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
        'sideMeta' => ['label' => 'Monitoring Date', 'value' => $todayLabel],
    ])

    @include('components.attendance.flash-messages')

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.attendance.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="office_location_id">Office</label>
                    <select id="office_location_id" name="office_location_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:min-w-[18rem]">
                        @foreach($officeLocations as $office)
                            <option value="{{ $office->id }}" @selected($selectedOffice?->id === $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                        <i class="fa-solid fa-filter"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </form>

            <div class="flex flex-wrap gap-2">
                @foreach($quickFilters as $key => $label)
                    <a href="{{ route($routePrefix . '.attendance.index', array_filter(['office_location_id' => $selectedOffice?->id, 'quick' => $key])) }}"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition {{ $quickFilter === $key ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' }}">
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
        @include('components.attendance.quick-stat', ['label' => 'Total Employee', 'value' => $stats['total'], 'icon' => 'fa-solid fa-users', 'iconContainerClasses' => 'bg-slate-100 text-slate-700'])
        @include('components.attendance.quick-stat', ['label' => 'Checked In', 'value' => $stats['checked_in'], 'icon' => 'fa-solid fa-right-to-bracket', 'iconContainerClasses' => 'bg-sky-100 text-sky-700'])
        @include('components.attendance.quick-stat', ['label' => 'Late', 'value' => $stats['late'], 'icon' => 'fa-solid fa-clock', 'iconContainerClasses' => 'bg-amber-100 text-amber-700'])
        @include('components.attendance.quick-stat', ['label' => 'Not Checked In', 'value' => $stats['not_checked_in'], 'icon' => 'fa-solid fa-user-clock', 'iconContainerClasses' => 'bg-rose-100 text-rose-700'])
        @include('components.attendance.quick-stat', ['label' => 'Complete', 'value' => $stats['complete'], 'icon' => 'fa-solid fa-circle-check', 'iconContainerClasses' => 'bg-emerald-100 text-emerald-700'])
        @include('components.attendance.quick-stat', ['label' => 'Suspicious', 'value' => $stats['suspicious'], 'icon' => 'fa-solid fa-shield-halved', 'iconContainerClasses' => 'bg-red-100 text-red-700'])
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.65fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Today Monitoring</h2>
                    <p class="mt-1 text-sm text-slate-600">Operational view for present, late, missing, and suspicious attendance states.</p>
                </div>
                <span class="text-sm font-medium text-slate-500">{{ $rows->count() }} rows</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Office</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check In</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Check Out</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($rows as $row)
                            <tr class="hover:bg-slate-50 {{ $row['is_suspicious'] ? 'bg-red-50/50' : '' }}">
                                <td class="px-4 py-4 text-sm">
                                    <p class="font-semibold text-slate-900">{{ $row['employee_name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $row['division_name'] }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $row['office_name'] }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_in'] }}</td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $row['check_out'] }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @include('components.attendance.status-badge', ['badge' => $row['status']])
                                        @foreach($row['flags'] as $flag)
                                            @include('components.attendance.status-badge', ['badge' => $flag])
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">{{ $row['status_description'] }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    @if($row['detail_id'])
                                        <a href="{{ route($routePrefix . '.attendance.show', $row['detail_id']) }}"
                                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>Detail</span>
                                        </a>
                                    @else
                                        <span class="inline-flex items-center rounded-xl bg-slate-100 px-3 py-2 text-sm font-medium text-slate-500">No detail</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8">
                                    @include('components.attendance.empty-state', ['title' => 'No employee found for monitoring', 'description' => 'Adjust the office filter or make sure employee accounts are active for the selected office.', 'icon' => 'fa-solid fa-users-slash'])
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Priority Issues</h2>
                <div class="mt-5 space-y-3">
                    @forelse($priorityIssues as $issue)
                        <div class="rounded-2xl border {{ $issue['status']['surface_classes'] }} p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $issue['employee_name'] }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $issue['status_description'] }}</p>
                                </div>
                                @include('components.attendance.status-badge', ['badge' => $issue['status']])
                            </div>
                        </div>
                    @empty
                        @include('components.attendance.empty-state', ['title' => 'No priority issue right now', 'description' => 'Suspicious, late, and missing attendance entries will appear here for faster follow-up.', 'icon' => 'fa-solid fa-circle-check'])
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection



