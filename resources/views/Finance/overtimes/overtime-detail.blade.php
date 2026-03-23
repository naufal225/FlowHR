@extends('Finance.layouts.app')

@section('title', 'Overtime Request Details')
@section('header', 'Overtime Request Details')
@section('subtitle', 'View overtime request information')

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
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('finance.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('finance.overtimes.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Overtime Requests</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Request #{{ $overtime->id }}</span>
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
                @if($overtime->marked_down)
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                    <i class="text-green-500 bg-white rounded-full fas fa-check-circle text-7xl drop-shadow-lg"></i>
                </div>
                @endif

                <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-white">Overtime Request #{{ $overtime->id }}</h1>
                            <p class="text-sm text-primary-100">Submitted on {{ $overtime->created_at->format('M d, Y
                                \a\t H:i') }}</p>
                            <p class="mt-4 text-sm font-medium text-primary-100">Owner Name: {{
                                $overtime->employee->name }}</p>
                        </div>
                        <div class="text-right">
                            @if($overtime->status_1 === 'rejected' || $overtime->status_2 === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-error-100 text-error-800">
                                <i class="mt-1 mr-1 fas fa-times-circle"></i>
                                Rejected
                            </span>
                            @elseif($overtime->status_1 === 'approved' && $overtime->status_2 === 'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mt-1 mr-1 fas fa-check-circle"></i>
                                Approved
                            </span>
                            @elseif($overtime->status_1 === 'pending' || $overtime->status_2 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">
                                <i class="mt-1 mr-1 fas fa-clock"></i>
                                {{ (Auth::id() === $overtime->employee_id && $overtime->status_1 === 'pending' &&
                                !\App\Models\Division::where('leader_id', Auth::id())->exists()) ||
                                (\App\Models\Division::where('leader_id', Auth::id())->exists() && $overtime->status_2
                                === 'pending') ? 'Pending' : 'In Progress' }} Review
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

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
                                <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                    <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                    <span class="max-w-full overflow-y-auto break-all whitespace-pre-line text-neutral-900 max-h-40">{{ $overtime->note_1 ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-neutral-700">Note - Approver 2</label>
                                <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                    <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                    <span class="max-w-full overflow-y-auto break-all whitespace-pre-line text-neutral-900 max-h-40">{{ $overtime->note_2 ?? '-' }}</span>
                                </div>\
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
                    @if((Auth::id() === $overtime->employee_id && $overtime->status_1 === 'pending' &&
                    !\App\Models\Division::where('leader_id', Auth::id())->exists()) ||
                    (\App\Models\Division::where('leader_id', Auth::id())->exists() && $overtime->status_2 ===
                    'pending'))
                    <a href="{{ route('finance.overtimes.edit', $overtime->id) }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Request
                    </a>
                    <form action="{{ route('finance.overtimes.destroy', $overtime->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this overtime request?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-error-600 hover:bg-error-700">
                            <i class="mr-2 fas fa-trash"></i>
                            Delete Request
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('finance.overtimes.index') }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-neutral-600 hover:bg-neutral-700">
                        <i class="mr-2 fas fa-arrow-left"></i>
                        Back to List
                    </a>

                    @if ($overtime->status_1 == 'approved' && $overtime->status_2 == 'approved' &&
                    $overtime->marked_down)
                    <button onclick="window.location.href='{{ route('finance.overtimes.exportPdf', $overtime->id) }}'"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
                        <i class="mr-2 fas fa-print"></i>
                        Print Request
                    </button>
                    @endif

                    @if ($overtime->status_1 == 'approved' && $overtime->status_2 == 'approved' &&
                    !$overtime->marked_down && $overtime->locked_by === Auth::id() &&
                    $overtime->locked_at->addMinutes(60)->isFuture())
                    <form action="{{ route('finance.overtimes.marked') }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to mark selected overtimes as done?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="ids[]" value="{{ $overtime->id }}">

                        <button type="submit"
                            class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-success-600 hover:bg-success-700">
                            <i class="mr-2 fas fa-check"></i>
                            Mark as done
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
