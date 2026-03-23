@extends('components.manager.layout.layout-manager')
@section('header', 'Leave Detail')
@section('subtitle', '')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Left Column - Main Details -->
        <div class="space-y-6 lg:col-span-2">
            <div class="relative overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
                <!-- Overlay Checklist -->
                @if($leave->status_1 === 'approved')
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                    <i class="text-5xl text-green-500 bg-white rounded-full fas fa-check-circle drop-shadow-lg"></i>
                </div>
                @endif

                <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-white">Leave Request #LY{{ $leave->id }}</h1>
                            <p class="text-sm text-primary-100">Submitted on {{
                                Carbon\Carbon::parse($leave->created_at)->format('M d, Y \a\t H:i') }}</p>
                        </div>
                        <div class="text-right">
                            @if($leave->status_1 === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-error-100 text-error-800">
                                <i class="mt-1 mr-1 fas fa-times-circle"></i>
                                Rejected
                            </span>
                            @elseif($leave->status_1 === 'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mt-1 mr-1 fas fa-check-circle"></i>
                                Approved
                            </span>
                            @elseif($leave->status_1 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">
                                <i class="mt-1 mr-1 fas fa-clock"></i>
                                {{ $leave->status_1 === 'pending' ? 'Pending' : 'In Progress' }} Review
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
            <div class="px-4 py-3 mx-6 mt-6 border rounded-lg bg-error-50 border-error-200 text-error-700">
                <ul class="pl-5 space-y-1 list-disc">
                    @foreach ($errors->all() as $error)
                    <li class="text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Leave Details -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-bold text-neutral-900">Leave Details</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Email</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200"
                                x-data="{ tooltip: false }">
                                <i class="flex-shrink-0 mr-3 fas fa-envelope text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900" @mouseenter="tooltip = true"
                                    @mouseleave="tooltip = false" x-tooltip="'{{ $leave->employee->email }}'">
                                    {{ $leave->employee->email }}
                                </span>
                                <!-- Tooltip -->
                                <div x-show="tooltip" x-cloak
                                    class="absolute px-3 py-2 -mt-12 text-sm text-white bg-gray-900 rounded-lg shadow-lg">
                                    {{ $leave->employee->email }}
                                </div>
                            </div>
                        </div>

                        <!-- Division -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Division</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-building text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{ $leave->employee->division->name ?? 'N/A'
                                    }}</span>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Start Date</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-alt text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{
                                    \Carbon\Carbon::parse($leave->date_start)->format('l, M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- End Date -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">End Date</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-alt text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{
                                    \Carbon\Carbon::parse($leave->date_end)->format('l, M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Duration</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-clock text-secondary-600"></i>
                                <span class="font-medium text-neutral-900">
                                    @php
                                        $tahunSekarang = now()->year;
                                        $hariLibur = \App\Models\Holiday::whereYear('holiday_date', $tahunSekarang)
                                            ->pluck('holiday_date')
                                            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                                            ->toArray();
                                        $durasi = app()->call(\App\Services\LeaveService::class.'@hitungHariCuti', [
                                            'dateStart' => $leave->date_start,
                                            'dateEnd' => $leave->date_end,
                                            'tahun' => $tahunSekarang,
                                            'hariLibur' => $hariLibur,
                                        ]);
                                    @endphp
                                    {{ $durasi }} {{ $durasi === 1 ? 'day' : 'days' }}
                                </span>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Reason for Leave</label>
                            <div class="p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <p class="leading-relaxed font text-neutral-900">{{ $leave->reason }}</p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($leave->status_1 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($leave->status_1 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($leave->status_1 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Note</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                <span class="text-neutral-900">{{ $leave->note_1 ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <!-- Edit & Delete hanya muncul jika status masih pending dan user adalah pemilik -->
                    @if(Auth::id() === $leave->employee_id && $leave->status_1 === 'pending')
                    <a href="{{ route('manager.leaves.edit', $leave->id) }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Request
                    </a>

                    <button
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg delete-leave-btn bg-error-600 hover:bg-error-700"
                        data-leave-id="{{ $leave->id }}" data-leave-name="Leave Request #{{ $leave->id }}"
                        title="Delete">
                        <i class="mr-2 fas fa-trash"></i>
                        Delete Request
                    </button>

                    <form id="delete-form-{{ $leave->id }}" action="{{ route('manager.leaves.destroy', $leave->id) }}"
                        method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif

                    <!-- Print -->
                    <button onclick="window.location.href='{{ route('manager.leaves.exportPdf', $leave->id) }}'"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
                        <i class="mr-2 fas fa-print"></i>
                        Print Request
                    </button>

                    <!-- Back to List -->
                    <a href="{{ route('manager.leaves.index') }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-neutral-600 hover:bg-neutral-700">
                        <i class="mr-2 fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>

            <!-- Added approval/rejection form for pending requests -->
            @if($leave->status_1 === 'pending')
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Review Request</h3>
                </div>
                <div class="p-6">
                    <form id="approvalForm" method="POST" action="{{ route('manager.leaves.update', $leave) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status_1" id="status_1" value="">

                        <div class="space-y-4">
                            <div>
                                <label for="approval_notes" class="block mb-2 text-sm font-semibold text-neutral-700">
                                    Notes <span class="text-neutral-500">(Optional)</span>
                                </label>
                                <textarea name="note_1" id="approval_notes" rows="4"
                                    class="w-full px-3 py-2 border rounded-lg resize-none border-neutral-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Add any comments or reasons for your decision..."></textarea>
                            </div>

                            <div class="flex flex-col space-y-3">
                                <button type="button" onclick="submitApproval('approved')"
                                    class="flex items-center justify-center w-full px-4 py-3 font-semibold text-white transition-colors duration-200 rounded-lg bg-success-600 hover:bg-success-700 focus:ring-2 focus:ring-success-500 focus:ring-offset-2">
                                    <i class="mr-2 fas fa-check"></i>
                                    Approve Request
                                </button>

                                <button type="button" onclick="submitApproval('rejected')"
                                    class="flex items-center justify-center w-full px-4 py-3 font-semibold text-white transition-colors duration-200 rounded-lg bg-error-600 hover:bg-error-700 focus:ring-2 focus:ring-error-500 focus:ring-offset-2">
                                    <i class="mr-2 fas fa-times"></i>
                                    Reject Request
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Review Request</h3>
                </div>
                <div class="p-6">
                    @if($leave->status_1 === 'pending')
                    <div class="p-4 text-center text-yellow-800 rounded-lg bg-yellow-50">
                        <i class="mb-2 text-xl fas fa-clock"></i>
                        <p class="font-medium">Pending Approver Review</p>
                        <p class="text-sm">Waiting for approval from approver.</p>
                    </div>
                    @elseif($leave->status_1 === 'approved')
                    <div class="p-4 text-center text-green-800 rounded-lg bg-green-50">
                        <i class="mb-2 text-xl fas fa-check-circle"></i>
                        <p class="font-medium">Approved by Approver</p>
                        @if($leave->note_1)
                        <p class="mt-2 text-sm"><strong>Notes:</strong> {{ $leave->note_1 }}</p>
                        @endif
                    </div>
                    @elseif($leave->status_1 === 'rejected')
                    <div class="p-4 text-center text-red-800 rounded-lg bg-red-50">
                        <i class="mb-2 text-xl fas fa-times-circle"></i>
                        <p class="font-medium">Rejected by Approver</p>
                        @if($leave->note_1)
                        <p class="mt-2 text-sm"><strong>Reason:</strong> {{ $leave->note_1 }}</p>
                        @else
                        <p class="text-sm">No reason provided.</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>



@section('partial-modal')
{{-- Updated modal to handle leave requests instead of users --}}
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
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Leave Request</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="leaveName" class="font-medium text-gray-900"></span>?
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

<!-- Added JavaScript for form submission with confirmation -->
<script>
    function submitApproval(action) {
        const actionText = action === 'approved' ? 'approved' : 'rejected';
        // const confirmMessage = `Are you sure you want to ${actionText} this leave request?`;

        // if (confirm(confirmMessage)) {
        document.getElementById('status_1').value = action;
        document.getElementById('approvalForm').submit();
        // }
    }

       let leaveIdToDelete = null;

// Initialize delete functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeDeleteFunctionality();
});

function initializeDeleteFunctionality() {
    // Add event listeners to all delete buttons
    const deleteButtons = document.querySelectorAll('.delete-leave-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const leaveId = this.getAttribute('data-leave-id');
            const leaveName = this.getAttribute('data-leave-name');
            confirmDelete(leaveId, leaveName);
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

function confirmDelete(leaveId, leaveName) {
    leaveIdToDelete = leaveId;
    document.getElementById('leaveName').textContent = leaveName;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeDeleteModal() {
    leaveIdToDelete = null;
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Restore scrolling
}

function executeDelete() {
    if (!leaveIdToDelete) return;

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
    document.getElementById(`delete-form-${leaveIdToDelete}`).submit();
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
