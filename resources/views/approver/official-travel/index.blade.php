@extends('components.approver.layout.layout-approver')

@section('header', 'Manage Official Travels')
@section('subtitle', 'Manage official travels data')

@section('content')
<main class="relative z-10 flex-1 p-0 space-y-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Official Travel Requests</h1>
            <p class="text-neutral-600">Manage and track official travel requests</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <div class="flex flex-col items-center gap-3 mt-4 sm:mt-0 sm:flex-row">
                <div class="mt-4 sm:mt-0">
                    <button onclick="window.location.href='{{ route('approver.official-travels.create') }}'"
                        class="btn-primary">
                        <i class="mr-2 fas fa-plus"></i>
                        New Official Travel Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="">
        <!-- Success Message -->
        @if(session('success'))
        <div class="flex items-center p-4 my-6 border border-green-200 bg-green-50 rounded-xl">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
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
                <div class="p-3 rounded-full bg-warning-100 text-warning-500">
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
                <div class="p-3 rounded-full bg-success-100 text-success-500">
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
                <div class="p-3 rounded-full bg-error-100 text-error-500">
                    <i class="text-xl fas fa-times-circle"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Rejected</p>
                    <p class="text-lg font-semibold">{{ $rejectedRequests }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
        <form id="filterForm" method="GET" action="{{ route('approver.official-travels.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label class="block mb-2 text-sm font-medium text-neutral-700">Status</label>
                    <select name="status" id="statusFilter"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved
                        </option>
                        <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-neutral-700">From Date</label>
                    <input type="date" name="from_date" id="fromDateFilter" value="{{ request('from_date') }}"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-neutral-700">To Date</label>
                    <input type="date" name="to_date" id="toDateFilter" value="{{ request('to_date') }}"
                        class="w-full py-2.5 px-3 border border-gray-300 rounded-xl shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="flex justify-center items-center cursor-pointer px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl shadow-sm
                        hover:bg-blue-700 hover:shadow-md transition-all duration-300 mr-2">
                        <i class="mr-2 fas fa-search"></i>
                        Filter
                    </button>
                    <a href="{{ route('approver.official-travels.index') }}" class="flex justify-center items-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl shadow-sm
                        hover:bg-gray-200 hover:shadow-md transition-all duration-300">
                        <i class="mr-2 fas fa-refresh"></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- My Official Travel Requests Table -->
        <x-request-tabs />
    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl" data-request-tab-panel="my">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">My Official Travel Requests</h3>
                <span class="px-3 py-1 text-sm font-medium text-green-800 bg-blue-100 rounded-full">
                    {{ $ownRequests->total() }} requests
                </span>
            </div>
        </div>
        <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Request ID</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Duration</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Days</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Costs</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Status - Approver 1</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Status - Approver 2</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Approver 1</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Approver 2</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Customer</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($ownRequests as $officialTravel)
                        <tr class="transition-colors duration-200 hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">#TY{{ $officialTravel->id }}</div>
                                    <div class="text-sm text-neutral-500">{{ $officialTravel->created_at->format('M d,
                                        Y') }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->date_start->format('M d Y') }}
                                </div>
                                <div class="text-sm text-neutral-500">to {{ $officialTravel->date_end->format('M d Y')
                                    }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $start = \Carbon\Carbon::parse($officialTravel->date_start);
                                $end = \Carbon\Carbon::parse($officialTravel->date_end);
                                $totalDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
                                @endphp
                                <div class="text-sm font-bold text-neutral-900">{{ $totalDays }} day{{ $totalDays > 1 ?
                                    's' : '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-success-600">{{ '+Rp' .
                                    number_format($officialTravel->total ?? 0, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($officialTravel->status_1 === 'pending')
                                <span class="badge-pending text-warning-600">
                                    <i class="mr-1 fas fa-clock"></i>
                                    Pending
                                </span>
                                @elseif($officialTravel->status_1 === 'approved')
                                <span class="badge-approved text-success-600">
                                    <i class="mr-1 fas fa-check-circle"></i>
                                    Approved
                                </span>
                                @elseif($officialTravel->status_1 === 'rejected')
                                <span class="badge-rejected text-error-600">
                                    <i class="mr-1 fas fa-times-circle"></i>
                                    Rejected
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($officialTravel->status_2 === 'pending')
                                <span class="badge-pending text-warning-600">
                                    <i class="mr-1 fas fa-clock"></i>
                                    Pending
                                </span>
                                @elseif($officialTravel->status_2 === 'approved')
                                <span class="badge-approved text-success-600">
                                    <i class="mr-1 fas fa-check-circle"></i>
                                    Approved
                                </span>
                                @elseif($officialTravel->status_2 === 'rejected')
                                <span class="badge-rejected text-error-600">
                                    <i class="mr-1 fas fa-times-circle"></i>
                                    Rejected
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->approver1->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->approver2->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->customer ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-md whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('approver.official-travels.show', $officialTravel->id) }}"
                                        class="text-primary-600 hover:text-primary-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 ===
                                    'pending')
                                    <a href="{{ route('approver.official-travels.edit', $officialTravel->id) }}"
                                        class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Tombol Delete dengan atribut data -->
                                    <button type="button"
                                        class="delete-officialTravel-btn text-error-600 hover:text-error-900"
                                        data-officialTravel-id="{{ $officialTravel->id }}"
                                        data-officialTravel-name="Official Travel Request #TY{{ $officialTravel->id }}"
                                        data-table="own" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="text-neutral-400">
                                    <i class="mb-4 text-4xl fas fa-plane"></i>
                                    <p class="text-lg font-medium">No official travel requests found</p>
                                    <p class="text-sm">Submit your first travel request to get started</p>
                                    <a href="{{ route('approver.official-travels.create') }}"
                                        class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        New Travel Request
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ownRequests->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $ownRequests->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- All Official Travel Requests Table -->
        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl hidden" data-request-tab-panel="all">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">All Official Travel Requests</h3>
                <span class="px-3 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full">
                    {{ $allUsersRequests->total() }} requests
                </span>
            </div>
        </div>
        <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Request ID</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Employee</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Duration</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Days</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Costs</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Status - Approver 1</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Status - Approver 2</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Approver 1</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Approver 2</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Customer</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($allUsersRequests as $officialTravel)
                        <tr class="transition-colors duration-200 hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-neutral-900">#TY{{ $officialTravel->id }}</div>
                                    <div class="text-sm text-neutral-500">{{ $officialTravel->created_at->format('M d,
                                        Y') }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-10 h-10">
                                        @if($officialTravel->employee->url_profile)
                                        <img class="object-cover w-10 h-10 rounded-full"
                                            src="{{ $officialTravel->employee->url_profile }}"
                                            alt="{{ $officialTravel->employee->name }}">
                                        @else
                                        <div
                                            class="flex items-center justify-center w-10 h-10 bg-gray-300 rounded-full">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ strtoupper(substr($officialTravel->employee->name, 0, 1)) }}
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-neutral-900">{{
                                            $officialTravel->employee->name }}</div>
                                        <div class="text-sm text-neutral-500">{{ $officialTravel->employee->email }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->date_start->format('M d Y') }}
                                </div>
                                <div class="text-sm text-neutral-500">to {{ $officialTravel->date_end->format('M d Y')
                                    }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                $start = \Carbon\Carbon::parse($officialTravel->date_start);
                                $end = \Carbon\Carbon::parse($officialTravel->date_end);
                                $totalDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
                                @endphp
                                <div class="text-sm font-bold text-neutral-900">{{ $totalDays }} day{{ $totalDays > 1 ?
                                    's' : '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-success-600">{{ '+Rp' .
                                    number_format($officialTravel->total ?? 0, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($officialTravel->status_1 === 'pending')
                                <span class="badge-pending text-warning-600">
                                    <i class="mr-1 fas fa-clock"></i>
                                    Pending
                                </span>
                                @elseif($officialTravel->status_1 === 'approved')
                                <span class="badge-approved text-success-600">
                                    <i class="mr-1 fas fa-check-circle"></i>
                                    Approved
                                </span>
                                @elseif($officialTravel->status_1 === 'rejected')
                                <span class="badge-rejected text-error-600">
                                    <i class="mr-1 fas fa-times-circle"></i>
                                    Rejected
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($officialTravel->status_2 === 'pending')
                                <span class="badge-pending text-warning-600">
                                    <i class="mr-1 fas fa-clock"></i>
                                    Pending
                                </span>
                                @elseif($officialTravel->status_2 === 'approved')
                                <span class="badge-approved text-success-600">
                                    <i class="mr-1 fas fa-check-circle"></i>
                                    Approved
                                </span>
                                @elseif($officialTravel->status_2 === 'rejected')
                                <span class="badge-rejected text-error-600">
                                    <i class="mr-1 fas fa-times-circle"></i>
                                    Rejected
                                </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->approver1->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->approver2->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">{{ $officialTravel->customer ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 font-medium text-md whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('approver.official-travels.show', $officialTravel->id) }}"
                                        class="text-primary-600 hover:text-primary-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 ===
                                    'pending')
                                    <a href="{{ route('approver.official-travels.edit', $officialTravel->id) }}"
                                        class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Tombol Delete dengan atribut data -->
                                    <button type="button"
                                        class="delete-officialTravel-btn text-error-600 hover:text-error-900"
                                        data-officialTravel-id="{{ $officialTravel->id }}"
                                        data-officialTravel-name="Official Travel Request #TY{{ $officialTravel->id }}"
                                        data-table="all" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="text-neutral-400">
                                    <i class="mb-4 text-4xl fas fa-plane"></i>
                                    <p class="text-lg font-medium">No official travel requests found</p>
                                    <p class="text-sm">Submit your first travel request to get started</p>
                                    <a href="{{ route('approver.official-travels.create') }}"
                                        class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        New Travel Request
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($allUsersRequests->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $allUsersRequests->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Hidden Delete Forms -->
    @foreach($ownRequests as $officialTravel)
    @if(Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 === 'pending')
    <form id="own-delete-form-{{ $officialTravel->id }}"
        action="{{ route('approver.official-travels.destroy', $officialTravel->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    @endif
    @endforeach

    @foreach($allUsersRequests as $officialTravel)
    @if(Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 === 'pending')
    <form id="all-delete-form-{{ $officialTravel->id }}"
        action="{{ route('approver.official-travels.destroy', $officialTravel->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    @endif
    @endforeach

    <div id="toast" class="fixed z-50 hidden top-4 right-4">
        <div id="toastContent" class="px-6 py-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <span id="toastMessage"></span>
                <button onclick="hideToast()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</main>
@endsection

@section('partial-modal')
<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto" data-inline-hidden style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-opacity-75 " onclick="closeDeleteModal()"></div>
        <div
            class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <div class="text-center">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Official Travel Request</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="officialTravelName"
                        class="font-medium text-gray-900"></span>?
                    This action cannot be undone.
                </p>
            </div>
            <div class="flex justify-center space-x-3">
                <button type="button" id="cancelDeleteButton"
                    class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg cancel-delete-btn hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="confirmDeleteBtn"
                    class="z-40 px-4 py-2 text-sm font-medium text-white transition-colors bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <span id="deleteButtonText">Delete</span>
                    <svg id="deleteSpinner" class="hidden w-4 h-4 ml-2 -mr-1 text-white animate-spin" data-inline-hidden style="display: none;" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastContent = document.getElementById('toastContent');
        const toastMessage = document.getElementById('toastMessage');

        toastMessage.textContent = message;

        if (type === 'success') {
            toastContent.className = 'px-6 py-4 rounded-lg shadow-lg bg-green-500 text-white';
        } else {
            toastContent.className = 'px-6 py-4 rounded-lg shadow-lg bg-red-500 text-white';
        }

        toast.classList.remove('hidden');

        setTimeout(() => {
            hideToast();
        }, 5000);
    }

    function hideToast() {
        document.getElementById('toast').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        initializeDeleteFunctionality();
    });

    let officialTravelIdToDelete = null;
    let deleteTableType = null; // Tambahkan variabel untuk jenis tabel

    function initializeDeleteFunctionality() {
        // Tambahkan event listeners ke semua tombol delete
        const deleteButtons = document.querySelectorAll('.delete-officialTravel-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const officialTravelId = this.getAttribute('data-officialTravel-id');
                const officialTravelName = this.getAttribute('data-officialTravel-name');
                const tableType = this.getAttribute('data-table'); // Ambil jenis tabel
                confirmDelete(officialTravelId, officialTravelName, tableType); // Kirim jenis tabel
            });
        });

        // Tambahkan event listener untuk tombol konfirmasi delete
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', executeDelete);
        }

        // Tambahkan event listener untuk tombol cancel
        const cancelButton = document.getElementById('cancelDeleteButton');
        if (cancelButton) {
            cancelButton.addEventListener('click', closeDeleteModal);
        }
    }

    function confirmDelete(officialTravelId, officialTravelName, tableType) {
        officialTravelIdToDelete = officialTravelId;
        deleteTableType = tableType; // Simpan jenis tabel
        document.getElementById('officialTravelName').textContent = officialTravelName;
        document.getElementById('deleteConfirmModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Cegah scrolling latar belakang
    }

    function closeDeleteModal() {
        officialTravelIdToDelete = null;
        deleteTableType = null; // Reset jenis tabel
        document.getElementById('deleteConfirmModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; // Pulihkan scrolling
    }

    function executeDelete() {
        if (!officialTravelIdToDelete || !deleteTableType) return; // Pastikan ID dan jenis tabel ada

        // Tunjukkan loading state
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteText = document.getElementById('deleteButtonText');
        const deleteSpinner = document.getElementById('deleteSpinner');
        const cancelButton = document.getElementById('cancelDeleteButton');

        cancelButton.disabled = true;
        deleteBtn.disabled = true;
        deleteText.textContent = 'Deleting...';
        deleteSpinner.classList.remove('hidden');

        // Buat ID form berdasarkan jenis tabel dan ID request
        const formId = `${deleteTableType}-delete-form-${officialTravelIdToDelete}`;
        const form = document.getElementById(formId);

        if (form) {
            form.submit();
        } else {
            console.error('Delete form not found:', formId);
            showToast('Error: Could not find delete form', 'error');
            closeDeleteModal();
        }
    }

    // Tutup modal saat menekan tombol Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endpush
