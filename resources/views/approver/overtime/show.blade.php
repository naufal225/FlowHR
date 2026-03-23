@extends('components.approver.layout.layout-approver')
@section('header', 'Overtime Detail')
@section('subtitle', '')

@php
// Parsing waktu input
$start = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_start, 'Asia/Jakarta');
$end = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $overtime->date_end, 'Asia/Jakarta');

// Hitung langsung dari date_start
$overtimeMinutes = $start->diffInMinutes($end);
$overtimeHours = $overtimeMinutes / 60;

$hours = floor($overtimeMinutes / 60);
$minutes = $overtimeMinutes % 60;
@endphp

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Main Grid -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Left Column - Main Details -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Header -->
            <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                    @if($errors->any())
                    <div class="flex items-start p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-red-800">Please correct the following errors:</h4>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-white">Overtime Request #{{ $overtime->id }}</h1>
                            <p class="text-sm text-primary-100">
                                Submitted on {{ \Carbon\Carbon::parse($overtime->created_at)->format('M d, Y \a\t H:i')
                                }}
                            </p>
                        </div>
                        <div class="text-right">
                            @if($overtime->status_1 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">
                                <i class="mr-1 fas fa-clock"></i>
                                Pending Review
                            </span>
                            @elseif($overtime->status_1 === 'approved' && $overtime->status_2 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mr-1 fas fa-check-circle"></i>
                                Approved by You
                            </span>
                            @elseif($overtime->status_1 === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-error-100 text-error-800">
                                <i class="mr-1 fas fa-times-circle"></i>
                                Rejected by You
                            </span>
                            @elseif($overtime->status_1 === 'approved' && $overtime->status_2 === 'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mr-1 fas fa-check-circle"></i>
                                Fully Approved
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

            <!-- Overtime Details -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-bold text-neutral-900">Overtime Details</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Email</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200" x-data="{ tooltip: false }">
                                <i class="flex-shrink-0 mr-3 fas fa-envelope text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900" @mouseenter="tooltip = true" @mouseleave="tooltip = false" x-tooltip="'{{ $overtime->employee->email }}'">
                                    {{ $overtime->employee->email }}
                                </span>
                                <div x-show="tooltip" x-cloak class="absolute px-3 py-2 -mt-12 text-sm text-white bg-gray-900 rounded-lg shadow-lg">
                                    {{ $overtime->employee->email }}
                                </div>
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Customer</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-users text-info-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{ $overtime->customer ?? 'N/A'
                                    }}</span>
                            </div>
                        </div>

                        <!-- Total Hours -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Total Overtime Hours</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-hourglass-half text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{ $hours }} jam {{ $minutes }} menit</span>
                            </div>
                        </div>

                        <!-- Work Hours Breakdown -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Total Costs</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-dollar-sign text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{ 'Rp ' .
                                    number_format($overtime->total ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <!-- Start Date & Time -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Start Date & Time</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-day text-secondary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{
                                    $overtime->date_start->translatedFormat('d/m/Y \a\t H:i') }}</span>
                            </div>
                        </div>

                        <!-- End Date & Time -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">End Date & Time</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-day text-secondary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{
                                    $overtime->date_end->translatedFormat('d/m/Y \a\t H:i') }}</span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status 1 - Approver 1</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($overtime->status_1 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($overtime->status_1 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($overtime->status_1 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status 2 - Approver 2</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($overtime->status_2 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($overtime->status_2 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($overtime->status_2 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Note - Approver 1</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                <span class="text-neutral-900">{{ $overtime->note_1 ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Note - Approver 2</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                <span class="text-neutral-900">{{ $overtime->note_2 ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    @if(Auth::id() === $overtime->employee_id && $overtime->status_1 === 'pending')
                    <a href="{{ route('approver.overtimes.edit', $overtime->id) }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Request
                    </a>

                    <button
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg delete-overtime-btn bg-error-600 hover:bg-error-700"
                        data-overtime-id="{{ $overtime->id }}"
                        data-overtime-name="Overtime Request #{{ $overtime->id }}" title="Delete">
                        <i class="mr-2 fas fa-trash"></i>
                        Delete Request
                    </button>

                    <form id="delete-form-{{ $overtime->id }}"
                        action="{{ route('approver.overtimes.destroy', $overtime->id) }}" method="POST"
                        style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif

                    @if($overtime->status_1 === 'approved' && $overtime->status_2 === 'approved')
                    <button onclick="window.location.href='{{ route('approver.overtimes.exportPdf', $overtime->id) }}'"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
                        <i class="mr-2 fas fa-print"></i>
                        Print Request
                    </button>
                    @endif

                    <a href="{{ route('approver.overtimes.index') }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-neutral-600 hover:bg-neutral-700">
                        <i class="mr-2 fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
            </div>

            <!-- Review Request -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Your Review</h3>
                </div>
                <div class="p-6">
                    @if($overtime->status_1 === 'approved')
                    <div class="p-4 text-center text-green-800 rounded-lg bg-green-50">
                        <i class="mb-2 text-xl fas fa-check-circle"></i>
                        <p class="font-medium">You Approved This Request</p>
                        @if($overtime->note_1)
                        <p class="mt-2 text-sm"><strong>Notes:</strong> {{ $overtime->note_1 }}</p>
                        @endif
                    </div>
                    @elseif($overtime->status_1 === 'rejected')
                    <div class="p-4 text-center text-red-800 rounded-lg bg-red-50">
                        <i class="mb-2 text-xl fas fa-times-circle"></i>
                        <p class="font-medium">You Rejected This Request</p>
                        @if($overtime->note_1)
                        <p class="mt-2 text-sm"><strong>Reason:</strong> {{ $overtime->note_1 }}</p>
                        @else
                        <p class="text-sm">No reason provided.</p>
                        @endif
                    </div>
                    @elseif($overtime->status_1 === 'pending')
                    <form id="approvalForm" method="POST" action="{{ route('approver.overtimes.update', $overtime) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status_1" id="status_1" value="" />

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
                    @else
                    <div class="p-4 text-center text-gray-800 rounded-lg bg-gray-50">
                        <i class="mb-2 text-xl fas fa-info-circle"></i>
                        <p class="font-medium">Review Completed</p>
                        <p class="text-sm">This request has been reviewed.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('partial-modal')
<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto" data-inline-hidden style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-opacity-50" onclick="closeDeleteModal()"></div>
        <div
            class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>

            <div class="text-center">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Overtime Request</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="overtimeName" class="font-medium text-gray-900"></span>?
                    This action cannot be undone.
                </p>
            </div>

            <div class="flex justify-center space-x-3">
                <button type="button" id="cancelDeleteButton"
                    class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
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
    let overtimeIdToDelete = null;

    document.addEventListener('DOMContentLoaded', function() {
        initializeDeleteFunctionality();
    });

    function initializeDeleteFunctionality() {
        const deleteButtons = document.querySelectorAll('.delete-overtime-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-overtime-id');
                const name = this.getAttribute('data-overtime-name');
                confirmDelete(id, name);
            });
        });

        document.getElementById('cancelDeleteButton')?.addEventListener('click', closeDeleteModal);
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', executeDelete);
    }

    function confirmDelete(id, name) {
        overtimeIdToDelete = id;
        document.getElementById('overtimeName').textContent = name;
        document.getElementById('deleteConfirmModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        overtimeIdToDelete = null;
        document.getElementById('deleteConfirmModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function executeDelete() {
        if (!overtimeIdToDelete) return;

        const btn = document.getElementById('confirmDeleteBtn');
        const text = document.getElementById('deleteButtonText');
        const spinner = document.getElementById('deleteSpinner');
        const cancel = document.getElementById('cancelDeleteButton');

        cancel.disabled = true;
        btn.disabled = true;
        text.textContent = 'Deleting...';
        spinner.classList.remove('hidden');

        document.getElementById(`delete-form-${overtimeIdToDelete}`).submit();
    }

    function submitApproval(action) {

            document.getElementById('status_1').value = action;
            document.getElementById('approvalForm').submit();

    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endpush
