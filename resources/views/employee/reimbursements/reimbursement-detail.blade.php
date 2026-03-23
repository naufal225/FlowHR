@extends('Employee.layouts.app')

@section('title', 'Reimbursement Requests')
@section('header', 'Reimbursement Requests')
@section('subtitle', 'Manage your reimbursement claims')

@section('content')
<div class="max-w-4xl mx-auto">
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('employee.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('employee.reimbursements.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Reimbursement Requests</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Klaim #RY{{ $reimbursement->id }}</span>
                </div>
            </li>
        </ol>
    </nav>
    <!-- Main Content -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Left Column - Main Details -->
        <div class="space-y-6 lg:col-span-2">
            <!-- Request Header -->
            <div class="relative overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
                <!-- Overlay Checklist -->
                @if($reimbursement->marked_down)
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                    <i class="text-5xl text-green-500 bg-white rounded-full fas fa-check-circle drop-shadow-lg"></i>
                </div>
                @endif

                <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-white">Reimbursement Claim #RY{{ $reimbursement->id }}
                            </h1>
                            <p class="text-sm text-primary-100">Submitted on {{ $reimbursement->created_at->format('M d,
                                Y \a\t H:i') }}</p>
                        </div>
                        <div class="text-right">
                            @if($reimbursement->status_1 === 'rejected' || $reimbursement->status_2 === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-error-100 text-error-800">
                                <i class="mt-1 mr-1 fas fa-times-circle"></i>
                                Rejected
                            </span>
                            @elseif($reimbursement->status_1 === 'approved' && $reimbursement->status_2 === 'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mt-1 mr-1 fas fa-check-circle"></i>
                                Approved
                            </span>
                            @elseif($reimbursement->status_1 === 'pending' || $reimbursement->status_2 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">
                                <i class="mt-1 mr-1 fas fa-clock"></i>
                                {{ $reimbursement->status_1 === 'pending' ? 'Pending' : 'In Progress' }} Review
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- Reimbursement Details -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-bold text-neutral-900">Claim Details</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <!-- Email -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Email</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200" x-data="{ tooltip: false }">
                                <i class="flex-shrink-0 mr-3 fas fa-envelope text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900" @mouseenter="tooltip = true" @mouseleave="tooltip = false" x-tooltip="'{{ $reimbursement->employee->email }}'">
                                    {{ $reimbursement->employee->email }}
                                </span>
                                <div x-show="tooltip" x-cloak class="absolute px-3 py-2 -mt-12 text-sm text-white bg-gray-900 rounded-lg shadow-lg">
                                    {{ $reimbursement->employee->email }}
                                </div>
                            </div>
                        </div>
                        <!-- Approver -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Approver 1</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-user-check text-info-600"></i>
                                <span class="font-medium text-neutral-900">{{ $reimbursement->approver1->name ?? 'N/A'
                                    }}</span>
                            </div>
                        </div>
                        <!-- Total (was Amount) -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Total Amount</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-dollar-sign text-primary-600"></i>
                                <span class="font-medium text-neutral-900">Rp {{ number_format($reimbursement->total, 0,
                                    ',', '.') }}</span>
                            </div>
                        </div>
                        <!-- Date -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Date of Expense</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-day text-secondary-600"></i>
                                <span class="font-medium text-neutral-900">{{
                                    \Carbon\Carbon::parse($reimbursement->date)->format('l, M d, Y') }}</span>
                            </div>
                        </div>
                        <!-- Customer -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Perusahaan Customer</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-users text-info-600"></i>
                                <span class="font-medium text-neutral-900">{{ $reimbursement->customer ?? 'N/A'
                                    }}</span>
                            </div>
                        </div>
                        <!-- Type Reimbursement -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Type Reimbursement</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-list text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{ $reimbursement->type->name ?? 'N/A'
                                    }}</span>
                            </div>
                        </div>
                        <!-- Status -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status - Approver 1</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($reimbursement->status_1 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($reimbursement->status_1 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($reimbursement->status_1 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status - Approver 2</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($reimbursement->status_2 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($reimbursement->status_2 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($reimbursement->status_2 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <!-- Note -->
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Note - Approver 1</label>
                            <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                <span
                                    class="max-w-full overflow-y-auto break-all whitespace-pre-line text-neutral-900 max-h-40">{{
                                    $reimbursement->note_1 ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Note - Approver 2</label>
                            <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                <span
                                    class="max-w-full overflow-y-auto break-all whitespace-pre-line text-neutral-900 max-h-40">{{
                                    $reimbursement->note_2 ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-neutral-700">Note - Approver 2</label>
                        <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                            <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                            <span
                                class="max-w-full overflow-y-auto break-all whitespace-pre-line text-neutral-900 max-h-40">{{
                                $reimbursement->note_2 ?? '-' }}</span>
                        </div>
                    </div>
                </div>
                <!-- Invoice Path (was Attachment) -->
                <div class="py-6 space-y-2">
                    <label class="text-sm font-semibold text-neutral-700">Invoice</label>
                    <div class="p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                        @if($reimbursement->invoice_path)
                        <a href="{{ Storage::url($reimbursement->invoice_path) }}" target="_blank"
                            class="flex items-center font-medium text-primary-600 hover:text-primary-800">
                            <i class="mr-2 fas fa-file-alt"></i>
                            View Invoice ({{ pathinfo($reimbursement->invoice_path, PATHINFO_EXTENSION) }})
                        </a>
                        @else
                        <p class="text-neutral-500">No invoice provided.</p>
                        @endif
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
                    @if(Auth::id() === $reimbursement->employee_id && $reimbursement->status_1 === 'pending')
                    <a href="{{ route('employee.reimbursements.edit', $reimbursement->id) }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Request
                    </a>
                    <form action="{{ route('employee.reimbursements.destroy', $reimbursement->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this reimbursement request?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-error-600 hover:bg-error-700">
                            <i class="mr-2 fas fa-trash"></i>
                            Delete Request
                        </button>
                    </form>
                    @endif
                    <a href="{{ route('employee.reimbursements.index') }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-neutral-600 hover:bg-neutral-700">
                        <i class="mr-2 fas fa-arrow-left"></i>
                        Back to List
                    </a>

                    @if ($reimbursement->status_1 == 'approved' && $reimbursement->status_2 == 'approved')
                    <button
                        onclick="window.location.href='{{ route('employee.reimbursements.exportPdf', $reimbursement->id) }}'"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
                        <i class="mr-2 fas fa-print"></i>
                        Print Request
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
