@extends('Employee.layouts.app')

@section('title', 'Leave Requests')
@section('header', 'Leave Requests')
@section('subtitle', 'Manage your leave requests')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Leave Requests</h1>
                <p class="text-neutral-600">Manage and track your leave requests</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <button onclick="window.location.href='{{ route('employee.leaves.create') }}'" class="btn-primary @if($sisaCuti <= 0) cursor-not-allowed @else cursor-pointer @endif" @if($sisaCuti <= 0) disabled @endif>
                    <i class="mr-2 fas fa-plus"></i>
                    New Leave Request
                </button>
            </div>
        </div>
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary-100 text-primary-500">
                        <i class="text-xl fas fa-calendar-alt"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Total Requests</p>
                        <p class="text-lg font-semibold">{{ $totalRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-warning-100 text-warning-600">
                        <i class="text-xl fas fa-clock"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Pending</p>
                        <p class="text-lg font-semibold">{{ $pendingRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-success-100 text-success-600">
                        <i class="text-xl fas fa-check-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Approved</p>
                        <p class="text-lg font-semibold">{{ $approvedRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-error-100 text-error-600">
                        <i class="text-xl fas fa-times-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Rejected</p>
                        <p class="text-lg font-semibold">{{ $rejectedRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full {{ $sisaCuti <= 0 ? 'bg-error-100 text-error-600' : ($sisaCuti > ((int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20)) / 2) ? 'bg-success-100 text-success-600' : 'bg-warning-100 text-warning-600')}}">
                        <i class="text-xl fas fa-calendar-xmark"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Remaining days</p>
                        <p class="text-lg font-semibold">{{ $sisaCuti }}/{{ (int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20)) }} ({{ now()->year }})</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <form method="GET" action="{{ route('employee.leaves.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-neutral-700">Status</label>
                        <select name="status" class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-neutral-700">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-neutral-700">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <div class="flex flex-col pt-3 space-y-3 border-t sm:flex-row sm:justify-end sm:space-x-3 sm:space-y-0 border-gray-300/80">
                    <!-- Filter Button -->
                    <button type="submit"
                        class="flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-300 bg-blue-600 shadow-sm cursor-pointer justify-center-safe rounded-xl hover:bg-blue-700 hover:shadow-md">
                        <i class="mr-2 fas fa-search"></i>
                        Filter
                    </button>

                    <!-- Reset Button -->
                    <button onclick="window.location.href = '{{ route('employee.leaves.index') }}'" type="button"
                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 bg-gray-100 shadow-sm cursor-pointer justify-center-safe rounded-xl hover:bg-gray-200 hover:shadow-md">
                            <i class="mr-2 fas fa-refresh"></i>
                            Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Divider -->
        <div class="mt-6 mb-10 transform scale-y-50 border-t border-gray-300/80"></div>

        <!-- Leaves Table -->
        <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Duration</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Status</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Approver</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($leaves as $leave)
                            <tr class="transition-colors duration-200 hover:bg-neutral-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900">#LY{{ $leave->id }}</div>
                                        <div class="text-sm text-neutral-500">{{ $leave->created_at->format('M d, Y') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">
                                        {{ \Carbon\Carbon::parse($leave->date_start)->format('M d') }} - {{ \Carbon\Carbon::parse($leave->date_end)->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-neutral-500">
                                        @php
                                            $tahunSekarang = now()->year;
                                            $hariLibur = \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
                                                ->pluck('holiday_date')
                                                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                                                ->toArray();

                                            $start = \Carbon\Carbon::parse($leave->date_start);
                                            $end   = \Carbon\Carbon::parse($leave->date_end);

                                            $durasi = app()->call('App\Http\Controllers\EmployeeController\LeaveController@hitungHariCuti', [
                                                'start' => $start,
                                                'end' => $end,
                                                'tahunSekarang' => $tahunSekarang,
                                                'hariLibur' => $hariLibur,
                                            ]);
                                        @endphp

                                        {{ $durasi }} {{ $durasi === 1 ? 'day' : 'days' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($leave->status_1 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @elseif($leave->status_1 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Approved
                                        </span>
                                    @elseif($leave->status_1 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $leave->approver1->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-md whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('employee.leaves.show', $leave->id) }}" class="text-primary-600 hover:text-primary-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if(Auth::id() === $leave->employee_id && $leave->status_1 === 'pending')
                                            <a href="{{ route('employee.leaves.edit', $leave->id) }}" class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('employee.leaves.destroy', $leave->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-error-600 hover:text-error-900" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="h-64 text-center align-middle">
                                    <div class="flex flex-col items-center justify-center h-full text-neutral-400">
                                        <i class="mb-4 text-4xl fas fa-inbox"></i>
                                        <p class="text-lg font-medium">No leave requests found</p>
                                        <p class="text-sm">Create your first leave request to get started</p>
                                        <button
                                            onclick="window.location.href='{{ route('employee.leaves.create') }}'"
                                            @if($sisaCuti <= 0) disabled @endif
                                            class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 @if($sisaCuti <= 0) cursor-not-allowed @else cursor-pointer @endif">
                                                <i class="mr-2 fas fa-plus"></i>
                                                New Leave Request
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaves->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
