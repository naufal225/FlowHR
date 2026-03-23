@extends('components.admin.layout.layout-admin')
@section('header', 'Manage Holidays')
@section('subtitle', 'Manage holiday dates')

@section('content')
<main class="relative z-10 flex-1 p-0 space-y-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Holidays</h1>
            <p class="text-neutral-600">Manage and track holiday dates</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('admin.holidays.create') }}" class="btn-primary">
                <i class="mr-2 fas fa-plus"></i>
                Add New Holiday
            </a>
        </div>
    </div>

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

    <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
        <form id="filterForm" method="GET" action="{{ route('admin.holidays.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
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
                    <a href="{{ route('admin.holidays.index') }}" class="flex justify-center items-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl shadow-sm
                        hover:bg-gray-200 hover:shadow-md transition-all duration-300">
                        <i class="mr-2 fas fa-refresh"></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Holiday Name</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Created At</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-neutral-200">
                    @forelse($holidays as $holiday)
                    <tr class="transition-colors duration-200 hover:bg-neutral-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-neutral-900">
                                {{ \Carbon\Carbon::parse($holiday->holiday_date)->format('M d, Y') }}
                            </div>
                            <div class="text-sm text-neutral-500">
                                {{ \Carbon\Carbon::parse($holiday->holiday_date)->format('l') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-neutral-900">{{ $holiday->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-neutral-500">
                                {{ $holiday->created_at->format('M d, Y') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-md font-medium whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.holidays.show', $holiday->id) }}"
                                    class="text-primary-600 hover:text-primary-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.holidays.edit', $holiday->id) }}"
                                    class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="delete-holiday-btn text-error-600 hover:text-error-900"
                                    data-holiday-id="{{ $holiday->id }}" data-holiday-name="{{ $holiday->name }}"
                                    title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-neutral-400">
                                <i class="mb-4 text-4xl fas fa-calendar"></i>
                                <p class="text-lg font-medium">No holidays found</p>
                                <a href="{{ route('admin.holidays.create') }}"
                                    class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                    <i class="mr-2 fas fa-plus"></i>
                                    Add New Holiday
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($holidays->hasPages())
        <div class="px-6 py-4 border-t border-neutral-200">
            {{ $holidays->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    @foreach($holidays as $holiday)
    <form id="delete-form-{{ $holiday->id }}" action="{{ route('admin.holidays.destroy', $holiday->id) }}" method="POST"
        style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

</main>

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
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Holiday</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="holidayName" class="font-medium text-gray-900"></span>?
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
    let holidayIdToDelete = null;

    document.addEventListener('DOMContentLoaded', function() {
        initializeDeleteFunctionality();
    });

    function initializeDeleteFunctionality() {
        // Add event listeners to all delete buttons
        const deleteButtons = document.querySelectorAll('.delete-holiday-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const holidayId = this.getAttribute('data-holiday-id');
                const holidayName = this.getAttribute('data-holiday-name');
                confirmDelete(holidayId, holidayName);
            });
        });

        // Add event listener for confirm delete button
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', executeDelete);
        }

        // Add event listener for cancel button
        const cancelButton = document.getElementById('cancelDeleteButton');
        if (cancelButton) {
            cancelButton.addEventListener('click', closeDeleteModal);
        }
    }

    function confirmDelete(holidayId, holidayName) {
        holidayIdToDelete = holidayId;
        document.getElementById('holidayName').textContent = holidayName;
        document.getElementById('deleteConfirmModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeDeleteModal() {
        holidayIdToDelete = null;
        document.getElementById('deleteConfirmModal').classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }

    function executeDelete() {
        if (!holidayIdToDelete) return;

        // Show loading state
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteText = document.getElementById('deleteButtonText');
        const deleteSpinner = document.getElementById('deleteSpinner');
        const cancelButton = document.getElementById('cancelDeleteButton');

        cancelButton.disabled = true;
        deleteBtn.disabled = true;
        deleteText.textContent = 'Deleting...';
        deleteSpinner.classList.remove('hidden');

        // Submit the form
        document.getElementById(`delete-form-${holidayIdToDelete}`).submit();
    }

    // Close modal when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endpush
@endsection
