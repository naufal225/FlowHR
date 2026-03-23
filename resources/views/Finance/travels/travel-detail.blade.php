@extends('Finance.layouts.app')
@section('title', 'Official Travel Requests')
@section('header', 'Official Travel Requests')
@section('subtitle', 'Manage your official travel claims')

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
                    <a href="{{ route('finance.official-travels.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Official Travel Requests</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Request #TY{{ $officialTravel->id }}</span>
                </div>
            </li>
        </ol>
    </nav>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="relative overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
                <!-- Overlay Checklist -->
                @if($officialTravel->marked_down)
                <div class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                    <i class="text-green-500 bg-white rounded-full fas fa-check-circle text-7xl drop-shadow-lg"></i>
                </div>
                @endif

                <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-bold text-white">Official Travel Request #TY{{ $officialTravel->id
                                }}</h1>
                            <p class="text-sm text-primary-100">Submitted on {{ $officialTravel->created_at->format('M
                                d, Y \a\t H:i') }}</p>
                            <p class="mt-4 text-sm font-medium text-primary-100">Owner Name: {{
                                $officialTravel->employee->name }}</p>
                        </div>
                        <div class="text-right">
                            @if($officialTravel->status_1 === 'rejected' || $officialTravel->status_2 === 'rejected')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-error-100 text-error-800">
                                <i class="mt-1 mr-1 fas fa-times-circle"></i>
                                Rejected
                            </span>
                            @elseif($officialTravel->status_1 === 'approved' && $officialTravel->status_2 ===
                            'approved')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800">
                                <i class="mt-1 mr-1 fas fa-check-circle"></i>
                                Approved
                            </span>
                            @elseif($officialTravel->status_1 === 'pending' || $officialTravel->status_2 === 'pending')
                            <span
                                class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-warning-100 text-warning-800">
                                <i class="mt-1 mr-1 fas fa-clock"></i>
                                {{ (Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 ===
                                'pending' && !\App\Models\Division::where('leader_id', Auth::id())->exists()) ||
                                (\App\Models\Division::where('leader_id', Auth::id())->exists() &&
                                $officialTravel->status_2 === 'pending') ? 'Pending' : 'In Progress' }} Review
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h2 class="text-lg font-bold text-neutral-900">Travel Details</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Email</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200" x-data="{ tooltip: false }">
                                <i class="flex-shrink-0 mr-3 fas fa-envelope text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900" @mouseenter="tooltip = true" @mouseleave="tooltip = false" x-tooltip="'{{ $officialTravel->employee->email }}'">
                                    {{ $officialTravel->employee->email }}
                                </span>
                                <div x-show="tooltip" x-cloak class="absolute px-3 py-2 -mt-12 text-sm text-white bg-gray-900 rounded-lg shadow-lg">
                                    {{ $officialTravel->employee->email }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Customer</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-users text-info-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{ $officialTravel->customer ??
                                    'N/A' }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Start Date</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-alt text-secondary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{
                                    $officialTravel->date_start->format('l, M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">End Date</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-calendar-alt text-secondary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{
                                    $officialTravel->date_end->format('l, M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Total Days</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @php
                                $start = Carbon\Carbon::parse($officialTravel->date_start);
                                $end = Carbon\Carbon::parse($officialTravel->date_end);
                                $totalDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
                                @endphp
                                <i class="mr-3 fas fa-calendar-day text-primary-600"></i>
                                <span class="font-medium text-neutral-900">{{ $totalDays }} day{{ $totalDays > 1 ? 's' :
                                    '' }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Total Costs</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                <i class="mr-3 fas fa-dollar-sign text-primary-600"></i>
                                <span class="font-medium truncate text-neutral-900">{{ 'Rp ' .
                                    number_format($officialTravel->total ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status 1 - Approver 1</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($officialTravel->status_1 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($officialTravel->status_1 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($officialTravel->status_1 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-neutral-700">Status 2 - Approver 2</label>
                            <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                @if($officialTravel->status_2 === 'pending')
                                <i class="mr-3 fas fa-clock text-warning-600"></i>
                                <span class="font-medium text-warning-800">Pending Review</span>
                                @elseif($officialTravel->status_2 === 'approved')
                                <i class="mr-3 fas fa-check-circle text-success-600"></i>
                                <span class="font-medium text-success-800">Approved</span>
                                @elseif($officialTravel->status_2 === 'rejected')
                                <i class="mr-3 fas fa-times-circle text-error-600"></i>
                                <span class="font-medium text-error-800">Rejected</span>
                                @endif
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-neutral-700">Note - Approver 1</label>
                                <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                    <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                    <span class="text-neutral-900 break-all whitespace-pre-line max-w-full max-h-40 overflow-y-auto">{{ $officialTravel->note_1 ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-neutral-700">Note - Approver 2</label>
                                <div class="flex items-start p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                                    <i class="mr-3 fas fa-sticky-note text-info-600"></i>
                                    <span class="text-neutral-900 break-all whitespace-pre-line max-w-full max-h-40 overflow-y-auto">{{ $officialTravel->note_2 ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="space-y-6">
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="px-6 py-4 border-b border-neutral-200">
                    <h3 class="text-lg font-bold text-neutral-900">Actions</h3>
                </div>

                <div class="p-6 space-y-3">
                    @if((Auth::id() === $officialTravel->employee_id && $officialTravel->status_1 === 'pending' &&
                    !\App\Models\Division::where('leader_id', Auth::id())->exists()) ||
                    (\App\Models\Division::where('leader_id', Auth::id())->exists() && $officialTravel->status_2 ===
                    'pending'))
                    <a href="{{ route('finance.official-travels.edit', $officialTravel->id) }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Request
                    </a>
                    <form action="{{ route('finance.official-travels.destroy', $officialTravel->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this official travel request?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-error-600 hover:bg-error-700">
                            <i class="mr-2 fas fa-trash"></i>
                            Delete Request
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('finance.official-travels.index') }}"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-neutral-600 hover:bg-neutral-700">
                        <i class="mr-2 fas fa-arrow-left"></i>
                        Back to List
                    </a>

                    @if ($officialTravel->status_1 == 'approved' && $officialTravel->status_2 == 'approved' &&
                    $officialTravel->marked_down)
                    <button
                        onclick="window.location.href='{{ route('finance.official-travels.exportPdf', $officialTravel->id) }}'"
                        class="flex items-center justify-center w-full px-4 py-2 font-semibold text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
                        <i class="mr-2 fas fa-print"></i>
                        Print Request
                    </button>
                    @endif

                    @if ($officialTravel->status_1 == 'approved' && $officialTravel->status_2 == 'approved' &&
                    !$officialTravel->marked_down && $officialTravel->locked_by === Auth::id() &&
                    $officialTravel->locked_at->addMinutes(60)->isFuture())
                    <form action="{{ route('finance.official-travels.marked') }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to mark selected overtimes as done?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="ids[]" value="{{ $officialTravel->id }}">

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
