@extends('Finance.layouts.app')

@section('title', 'Reimbursement Requests')
@section('header', 'Reimbursement Requests')
@section('subtitle', 'Manage employee reimbursement claims')

@section('content')
    <div class="space-y-6">
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900">Reimbursement Requests</h1>
                <p class="text-neutral-600">Manage and track employee reimbursement claims</p>
            </div>

            <div class="mt-4 sm:mt-0">
                <button onclick="window.location.href='{{ route('finance.reimbursements.create') }}'" class="cursor-pointer btn-primary">
                    <i class="mr-2 fas fa-plus"></i>
                    New Reimbursement Request
                </button>
            </div>
        </div>

        <!-- Statistics Yours Cards -->
        <p class="mb-2 text-sm text-neutral-500 ms-4">Your Requests</p>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-4">
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary-100 text-primary-500">
                        <i class="text-xl fas fa-receipt"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Total Requests</p>
                        <p class="text-lg font-semibold">{{ $totalYoursRequests }}</p>
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
                        <p class="text-lg font-semibold">{{ $pendingYoursRequests }}</p>
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
                        <p class="text-lg font-semibold">{{ $approvedYoursRequests }}</p>
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
                        <p class="text-lg font-semibold">{{ $rejectedYoursRequests }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics All Employee Cards -->
        <p class="mb-2 text-sm text-neutral-500 ms-4">All Requests</p>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-primary-100 text-primary-500">
                        <i class="text-xl fas fa-receipt"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Total All Requests</p>
                        <p class="text-lg font-semibold">{{ $totalRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-success-100 text-success-500">
                        <i class="text-xl fas fa-check-circle"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Total All Approved</p>
                        <p class="text-lg font-semibold">{{ $approvedRequests }}</p>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-success-100 text-success-500">
                        <i class="text-xl fas fa-list-check"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-neutral-500">Total All Marked</p>
                        <p class="text-lg font-semibold">{{ $markedRequests . '/' . $totalAllNoMark }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <form method="GET" action="{{ route('finance.reimbursements.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <!-- Status -->
                    <div>
                        <label for="status" class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                            class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">All</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-neutral-700">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- To Date -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-neutral-700">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col pt-3 space-y-3 border-t sm:flex-row sm:justify-end sm:space-x-3 sm:space-y-0 border-gray-300/80">

                    <!-- Filter -->
                    <button type="submit"
                        class="flex items-center w-full px-4 py-2 text-sm font-medium text-white transition-all duration-300 bg-blue-600 shadow-sm justify-center-safe rounded-xl hover:bg-blue-700 hover:shadow-md sm:w-auto">
                        <i class="mr-2 fas fa-search"></i> Filter
                    </button>

                    <!-- Reset -->
                    <button type="button" onclick="window.location.href = '{{ route('finance.reimbursements.index') }}'"
                        class="flex items-center w-full px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 bg-gray-100 shadow-sm justify-center-safe rounded-xl hover:bg-gray-200 hover:shadow-md sm:w-auto">
                        <i class="mr-2 fas fa-refresh"></i> Reset
                    </button>

                    <!-- Bulk Request -->
                    <button type="button"
                        onclick="window.location.href='{{ route('finance.reimbursements.bulkExport', [
                            'status' => request('status'),
                            'from_date' => request('from_date'),
                            'to_date' => request('to_date'),
                        ]) }}'"
                        class="flex items-center w-full px-4 py-2 text-sm font-medium text-white transition-all duration-300 bg-green-600 shadow-sm justify-center-safe rounded-xl hover:bg-green-700 hover:shadow-md sm:w-auto">
                        <i class="mr-2 fas fa-layer-group"></i> Bulk Request
                    </button>
                </div>
            </form>
        </div>

        <!-- Divider -->
        <div class="mt-6 mb-10 transform scale-y-50 border-t border-gray-300/80"></div>

        <!-- Your reimbursement Employee Table -->
        <p class="mb-2 text-sm text-neutral-500 ms-4">Your reimbursement requests are listed below.</p>
        <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Employee</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Total</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Type</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Date</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Status 1 - Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Status 2 - Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Perusahaan Customer</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($yourReimbursements as $reimbursement)
                            <tr class="transition-colors duration-200 hover:bg-neutral-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900">#RY{{ $reimbursement->id }}</div>
                                        <div class="text-sm text-neutral-500">{{ $reimbursement->created_at->format('M d, Y') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-8 h-8 mr-3 rounded-full bg-success-100">
                                            @if($reimbursement->employee->url_profile)
                                                <img class="object-cover rounded-full"
                                                    src="{{ $reimbursement->employee->url_profile }}" alt="{{ $reimbursement->employee->name }}">
                                            @else
                                                <span class="text-xs font-semibold text-success-600">{{ substr($reimbursement->employee->name, 0, 1) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-neutral-900">{{ $reimbursement->employee->name }}</div>
                                            <div class="text-sm text-neutral-500">{{ $reimbursement->employee->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ \Carbon\Carbon::parse($reimbursement->date)->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_1 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @elseif($reimbursement->status_1 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Approved
                                        </span>
                                    @elseif($reimbursement->status_1 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_2 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @elseif($reimbursement->status_2 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Approved
                                        </span>
                                    @elseif($reimbursement->status_2 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver1->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver2->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->customer ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium whitespace-nowrap text-md">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('finance.reimbursements.show', $reimbursement->id) }}" class="text-primary-600 hover:text-primary-900" title="View Details">
                                            <i class="text-lg fas fa-eye"></i>
                                        </a>

                                        @if((Auth::id() === $reimbursement->employee_id && $reimbursement->status_1 === 'pending' && !\App\Models\Division::where('leader_id', Auth::id())->exists()) || (\App\Models\Division::where('leader_id', Auth::id())->exists() && $reimbursement->status_2 === 'pending'))
                                            <a href="{{ route('finance.reimbursements.edit', $reimbursement->id) }}" class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('finance.reimbursements.destroy', $reimbursement->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
                                <td colspan="11" class="px-6 py-12 text-center">
                                    <div class="text-neutral-400">
                                        <i class="mb-4 text-4xl fas fa-receipt"></i>
                                        <p class="text-lg font-medium">No reimbursement requests found</p>
                                        <p class="text-sm">Create your first reimbursement request to get started</p>
                                        <a href="{{ route('finance.reimbursements.create') }}" class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                            <i class="mr-2 fas fa-plus"></i>
                                            New Reimbursement Request
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($yourReimbursements->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $yourReimbursements->links() }}
                </div>
            @endif
        </div>

        <!-- Reimbursement All Employee Table -->
        <form action="{{ route('finance.reimbursements.marked') }}" method="POST"
            onsubmit="return confirm('Are you sure you want to mark selected reimbursements as done?')" id="bulk-mark-form">
            @csrf
            @method('PATCH')

            <div class="flex flex-row items-center justify-between gap-2 p-4 mb-2 max-md:flex-col">
                <p class="text-sm text-neutral-500 max-md:mb-2">All employee reimbursement requests are listed below.</p>
                <div class="flex flex-row w-full gap-2 sm:w-auto">
                    <button type="button" id="mark-all-btn"
                            class="w-full px-4 py-2 text-white rounded-lg sm:w-auto bg-primary-600 hover:bg-primary-700">
                        <i class="mr-1 fas fa-list-check"></i> Mark All (Locked)
                    </button>
                    <button type="submit"
                            class="w-full px-4 py-2 text-white rounded-lg sm:w-auto bg-success-600 hover:bg-success-700 disabled:opacity-50"
                            id="bulk-mark-btn"
                            disabled>
                        <i class="mr-1 fas fa-check"></i> Mark Selected Done
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <!-- checkbox select all -->
                            <th class="px-4 py-3">
                                <input type="checkbox" id="select-all" class="form-checkbox">
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Employee</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Total</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Type</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Date</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Status 1 - Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Status 2 - Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Perusahaan Customer</th>
                            <th class="px-6 py-3 text-xs font-medium text-left uppercase text-neutral-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($allReimbursements as $reimbursement)
                            <tr class="transition-colors duration-200 hover:bg-neutral-50">
                                <!-- Checkbox per row -->
                                <td class="px-4 py-4">
                                    @if(!$reimbursement->marked_down && $reimbursement->locked_by === Auth::id())
                                        <input type="checkbox"
                                            name="ids[]"
                                            value="{{ $reimbursement->id }}"
                                            class="row-checkbox form-checkbox">
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900">#RY{{ $reimbursement->id }}</div>
                                        <div class="text-sm text-neutral-500">{{ $reimbursement->created_at->format('M d, Y') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-8 h-8 mr-3 rounded-full bg-success-100">
                                            @if($reimbursement->employee->url_profile)
                                                <img class="object-cover rounded-full"
                                                    src="{{ $reimbursement->employee->url_profile }}"
                                                    alt="{{ $reimbursement->employee->name }}">
                                            @else
                                                <span class="text-xs font-semibold text-success-600">
                                                    {{ substr($reimbursement->employee->name, 0, 1) }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-neutral-900">{{ $reimbursement->employee->name }}</div>
                                            <div class="text-sm text-neutral-500">{{ $reimbursement->employee->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">
                                        Rp {{ number_format($reimbursement->total, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">
                                        {{ \Carbon\Carbon::parse($reimbursement->date)->format('M d, Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_1 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i> Pending
                                        </span>
                                    @elseif($reimbursement->status_1 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i> Approved
                                        </span>
                                    @elseif($reimbursement->status_1 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i> Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_2 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i> Pending
                                        </span>
                                    @elseif($reimbursement->status_2 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i> Approved
                                        </span>
                                    @elseif($reimbursement->status_2 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i> Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver1->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver2->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->customer ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium whitespace-nowrap text-md">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('finance.reimbursements.show', $reimbursement->id) }}"
                                           class="text-primary-600 hover:text-primary-900" title="View Details">
                                            <i class="text-lg fas fa-eye"></i>
                                        </a>
                                        @if(!$reimbursement->marked_down && $reimbursement->locked_by === Auth::id())
                                            <form action="{{ route('finance.reimbursements.marked') }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Mark this reimbursement as done?')">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="ids[]" value="{{ $reimbursement->id }}">
                                                <button type="submit" class="text-success-600 hover:text-success-800" title="Mark Done">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-6 py-12 text-center">
                                    <div class="text-neutral-400">
                                        <i class="mb-4 text-4xl fas fa-receipt"></i>
                                        <p class="text-lg font-medium">No reimbursement employee (No marked done) requests found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Reimbursement All Employee (Marked done) Table -->
        <p class="mb-2 text-sm text-neutral-500 ms-4">All employee reimbursement (Marked done) requests are listed below.</p>
        <div class="mb-6 overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Request ID</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Employee</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Total</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Type</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Date</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Status 1 - Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Status 2 - Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Approver 1</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Approver 2</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Perusahaan Customer</th>
                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($allReimbursementsDone as $reimbursement)
                            <tr class="transition-colors duration-200 hover:bg-neutral-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-neutral-900">#RY{{ $reimbursement->id }}</div>
                                        <div class="text-sm text-neutral-500">{{ $reimbursement->created_at->format('M d, Y') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-8 h-8 mr-3 rounded-full bg-success-100">
                                            @if($reimbursement->employee->url_profile)
                                                <img class="object-cover rounded-full"
                                                    src="{{ $reimbursement->employee->url_profile }}" alt="{{ $reimbursement->employee->name }}">
                                            @else
                                                <span class="text-xs font-semibold text-success-600">{{ substr($reimbursement->employee->name, 0, 1) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-neutral-900">{{ $reimbursement->employee->name }}</div>
                                            <div class="text-sm text-neutral-500">{{ $reimbursement->employee->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">Rp {{ number_format($reimbursement->total, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->type->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ \Carbon\Carbon::parse($reimbursement->date)->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_1 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @elseif($reimbursement->status_1 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Approved
                                        </span>
                                    @elseif($reimbursement->status_1 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($reimbursement->status_2 === 'pending')
                                        <span class="badge-pending text-warning-600">
                                            <i class="mr-1 fas fa-clock"></i>
                                            Pending
                                        </span>
                                    @elseif($reimbursement->status_2 === 'approved')
                                        <span class="badge-approved text-success-600">
                                            <i class="mr-1 fas fa-check-circle"></i>
                                            Approved
                                        </span>
                                    @elseif($reimbursement->status_2 === 'rejected')
                                        <span class="badge-rejected text-error-600">
                                            <i class="mr-1 fas fa-times-circle"></i>
                                            Rejected
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver1->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->approver2->name ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-neutral-900">{{ $reimbursement->customer ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium whitespace-nowrap text-md">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('finance.reimbursements.show', $reimbursement->id) }}" class="text-primary-600 hover:text-primary-900" title="View Details">
                                            <i class="text-lg fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-12 text-center">
                                    <div class="text-neutral-400">
                                        <i class="mb-4 text-4xl fas fa-receipt"></i>
                                        <p class="text-lg font-medium">No reimbursement employee (Marked done) requests found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($allReimbursementsDone->hasPages())
                <div class="px-6 py-4 border-t border-neutral-200">
                    {{ $allReimbursementsDone->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
@push('scripts')
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const bulkBtn = document.getElementById('bulk-mark-btn');
    const markAllBtn = document.getElementById('mark-all-btn');
    const bulkForm = document.getElementById('bulk-mark-form');

    function toggleButtons() {
        const anyChecked = document.querySelectorAll('.row-checkbox:checked').length > 0;
        bulkBtn.disabled = !anyChecked;
    }

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        toggleButtons();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleButtons);
    });

    markAllBtn?.addEventListener('click', function () {
        const rows = document.querySelectorAll('.row-checkbox');
        let any = false;
        rows.forEach(cb => { if (!cb.disabled) { cb.checked = true; any = true; } });
        toggleButtons();
        if (any) {
            bulkForm.submit();
        }
    });
@endpush
