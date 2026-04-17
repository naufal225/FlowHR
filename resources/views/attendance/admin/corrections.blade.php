@extends($layout)

@section('title', 'Attendance Corrections')
@section('header', 'Attendance Corrections')
@section('subtitle', 'Review employee attendance correction requests')

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
        <form method="GET" action="{{ route($routePrefix . '.attendance.corrections.index') }}"
            class="grid grid-cols-1 gap-4 {{ $isApproverScope ? 'lg:grid-cols-3' : 'lg:grid-cols-4' }}">
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700" for="status">Status</label>
                <select id="status" name="status"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                @if($isApproverScope)
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="user_id">Employee</label>
                    <select id="user_id" name="user_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">All employees</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) request('user_id') === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                @else
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="employee_name">Employee Name</label>
                    <input id="employee_name" name="employee_name" type="text"
                        value="{{ $employeeNameFilter ?? request('employee_name') }}"
                        placeholder="Search employee name"
                        class="w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-sky-500 focus:ring-sky-500">
                @endif
            </div>
            @unless($isApproverScope)
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="office_location_id">Office</label>
                    <select id="office_location_id" name="office_location_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        <option value="">All offices</option>
                        @foreach($officeLocations as $office)
                            <option value="{{ $office->id }}" @selected((string) request('office_location_id') === (string) $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endunless
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply</span>
                </button>
                <a href="{{ route($routePrefix . '.attendance.corrections.index') }}"
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Submitted</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Request</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($corrections as $correction)
                        @php
                            $badge = $statusBadgeMap[$correction->status] ?? $statusBadgeMap['pending'];
                            $requestedTimes = collect([
                                $correction->requested_check_in_time ? 'IN ' . $correction->requested_check_in_time->setTimezone('Asia/Jakarta')->format('d M Y H:i') : null,
                                $correction->requested_check_out_time ? 'OUT ' . $correction->requested_check_out_time->setTimezone('Asia/Jakarta')->format('d M Y H:i') : null,
                            ])->filter()->implode(' | ');
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-4 text-sm text-slate-600">{{ $correction->created_at?->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="px-4 py-4 text-sm">
                                <p class="font-semibold text-slate-900">{{ $correction->attendance?->user?->name ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $correction->attendance?->user?->email ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <p>{{ $correction->attendance?->work_date?->translatedFormat('D, d M Y') ?? '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $correction->attendance?->officeLocation?->name ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                <p>{{ $requestedTimes ?: '-' }}</p>
                                <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $correction->reason }}</p>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <div class="space-y-2">
                                    @include('components.attendance.status-badge', ['badge' => $badge])
                                    @if($correction->reviewer)
                                        <p class="text-xs text-slate-500">By {{ $correction->reviewer->name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <a href="{{ route($routePrefix . '.attendance.corrections.show', $correction->id) }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                    <span>Review</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8">
                                @include('components.attendance.empty-state', [
                                    'title' => 'No correction request found',
                                    'description' => 'No attendance correction matches the current filter combination.',
                                    'icon' => 'fa-solid fa-inbox',
                                ])
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($corrections->hasPages())
            <div class="mt-4 border-t border-slate-100 pt-4">
                {{ $corrections->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
