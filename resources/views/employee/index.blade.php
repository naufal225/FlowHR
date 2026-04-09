@extends('Employee.layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard Analytics')
@section('subtitle', 'Overview of all employee requests')

@section('content')
@php
    $flags = $featureActive ?? [
        'cuti' => \App\Models\FeatureSetting::isActive('cuti'),
        'reimbursement' => \App\Models\FeatureSetting::isActive('reimbursement'),
        'overtime' => \App\Models\FeatureSetting::isActive('overtime'),
        'perjalanan_dinas' => \App\Models\FeatureSetting::isActive('perjalanan_dinas'),
    ];
    $pendingTotal = ($flags['cuti'] ? $pendingLeaves : 0)
        + ($flags['reimbursement'] ? $pendingReimbursements : 0)
        + ($flags['overtime'] ? $pendingOvertimes : 0)
        + ($flags['perjalanan_dinas'] ? $pendingTravels : 0);
    $approvedTotal = ($flags['cuti'] ? $approvedLeaves : 0)
        + ($flags['reimbursement'] ? $approvedReimbursements : 0)
        + ($flags['overtime'] ? $approvedOvertimes : 0)
        + ($flags['perjalanan_dinas'] ? $approvedTravels : 0);
    $rejectedTotal = ($flags['cuti'] ? $rejectedLeaves : 0)
        + ($flags['reimbursement'] ? $rejectedReimbursements : 0)
        + ($flags['overtime'] ? $rejectedOvertimes : 0)
        + ($flags['perjalanan_dinas'] ? $rejectedTravels : 0);
@endphp
<x-dashboard.attendance-state-card :dashboardAttendanceState="$dashboardAttendanceState" />
<!-- Statistics Cards - Adjusted for mobile -->
<div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2 lg:grid-cols-4 md:gap-5 md:mb-8">
    <!-- Pending Approvals Card -->
    <div
        class="p-4 transition-shadow duration-300 bg-white border rounded-xl shadow-soft md:p-6 border-neutral-200 hover:shadow-medium">
        <div class="flex items-center justify-between">
            <div>
                <p class="mb-1 text-xs font-medium text-neutral-600 md:text-sm">Pending Approvals</p>
                <p class="text-2xl font-bold md:text-3xl text-neutral-900">{{ $pendingTotal }}</p>
                <p class="mt-1 text-xs text-neutral-500">Awaiting review</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 md:w-12 md:h-12 bg-warning-100 rounded-xl">
                <i class="text-lg fas fa-clock text-warning-600 md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Approved This Month Card -->
    <div
        class="p-4 transition-shadow duration-300 bg-white border rounded-xl shadow-soft md:p-6 border-neutral-200 hover:shadow-medium">
        <div class="flex items-center justify-between">
            <div>
                <p class="mb-1 text-xs font-medium text-neutral-600 md:text-sm">Approved This Month</p>
                <p class="text-2xl font-bold md:text-3xl text-neutral-900">{{ $approvedTotal }}</p>
                <p class="mt-1 text-xs text-neutral-500">Successfully approved</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 md:w-12 md:h-12 bg-success-100 rounded-xl">
                <i class="text-lg fas fa-check-circle text-success-600 md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Rejected Requests Card -->
    <div
        class="p-4 transition-shadow duration-300 bg-white border rounded-xl shadow-soft md:p-6 border-neutral-200 hover:shadow-medium">
        <div class="flex items-center justify-between">
            <div>
                <p class="mb-1 text-xs font-medium text-neutral-600 md:text-sm">Rejected This Month</p>
                <p class="text-2xl font-bold md:text-3xl text-neutral-900">{{ $rejectedTotal }}</p>
                <p class="mt-1 text-xs text-neutral-500">Rejected approved</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 md:w-12 md:h-12 bg-error-100 rounded-xl">
                <i class="text-lg fas fa-times-circle text-error-600 md:text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Remaining Days Requests Card -->
    @if($flags['cuti'])
    <div
        class="p-4 transition-shadow duration-300 bg-white border rounded-xl shadow-soft md:p-6 border-neutral-200 hover:shadow-medium">
        <div class="flex items-center justify-between">
            <div>
                <p class="mb-1 text-xs font-medium text-neutral-600 md:text-sm">Remaining Days</p>
                <p class="text-2xl font-bold md:text-3xl text-neutral-900">{{ $sisaCuti }}/{{ (int)
                    \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20)) }} ({{ now()->year }})
                </p>
                <p class="mt-1 text-xs text-neutral-500">Remaining leave</p>
            </div>
            <div
                class="w-10 h-10 md:w-12 md:h-12 {{ $sisaCuti <= 0 ? 'bg-error-100 text-error-600' : ($sisaCuti > ((int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN', 20)) / 2) ? 'bg-success-100 text-success-600' : 'bg-warning-100 text-warning-600')}} rounded-xl flex items-center justify-center">
                @if ($sisaCuti <= 0) <i class="text-lg fas fa-times-circle md:text-xl"></i>
                    @elseif ($sisaCuti > ((int) \App\Helpers\CostSettingsHelper::get('ANNUAL_LEAVE', env('CUTI_TAHUNAN',
                    20)) / 2))
                    <i class="text-lg fas fa-check-circle md:text-xl"></i>
                    @else
                    <i class="text-lg fas fa-exclamation-circle md:text-xl"></i>
                    @endif
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Action Buttons -->
<div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-4">
    @if($flags['cuti'])
    <button onclick="window.location.href='{{ route('employee.leaves.create') }}'" @if($sisaCuti <=0) disabled @endif
        class="bg-primary-600 hover:bg-primary-700 text-white rounded-lg p-4 hover:shadow-md transition-all @if($sisaCuti <= 0) cursor-not-allowed @else cursor-pointer @endif">
        <div class="flex flex-col items-center text-center">
            <div class="flex items-center justify-center w-12 h-12 mb-3 bg-white rounded-full bg-opacity-20">
                <i class="text-xl fas fa-calendar-plus text-primary-600"></i>
            </div>
            <h3 class="mb-1 font-semibold">Request Leave</h3>
            <p class="text-sm text-primary-100">Submit new leave request</p>
        </div>
    </button>
    @endif

    @if($flags['reimbursement'])
    <a href="{{ route('employee.reimbursements.create') }}"
        class="p-4 text-white transition-all rounded-lg bg-secondary-600 hover:bg-secondary-700 hover:shadow-md">
        <div class="flex flex-col items-center text-center">
            <div class="flex items-center justify-center w-12 h-12 mb-3 bg-white rounded-full bg-opacity-20">
                <i class="text-xl fas fa-receipt text-secondary-600"></i>
            </div>
            <h3 class="mb-1 font-semibold">Submit Reimbursement</h3>
            <p class="text-sm text-secondary-100">Upload expense receipts</p>
        </div>
    </a>
    @endif

    @if($flags['overtime'])
    <a href="{{ route('employee.overtimes.create') }}"
        class="p-4 text-white transition-all rounded-lg bg-success-600 hover:bg-success-700 hover:shadow-md">
        <div class="flex flex-col items-center text-center">
            <div class="flex items-center justify-center w-12 h-12 mb-3 bg-white rounded-full bg-opacity-20">
                <i class="text-xl fas fa-clock text-success-600"></i>
            </div>
            <h3 class="mb-1 font-semibold">Request Overtime</h3>
            <p class="text-sm text-success-100">Log overtime hours</p>
        </div>
    </a>
    @endif

    @if($flags['perjalanan_dinas'])
    <a href="{{ route('employee.official-travels.create') }}"
        class="p-4 text-white transition-all rounded-lg bg-warning-600 hover:bg-warning-700 hover:shadow-md">
        <div class="flex flex-col items-center text-center">
            <div class="flex items-center justify-center w-12 h-12 mb-3 bg-white rounded-full bg-opacity-20">
                <i class="text-xl fas fa-plane text-warning-600"></i>
            </div>
            <h3 class="mb-1 font-semibold">Request Travel</h3>
            <p class="text-sm text-warning-100">Plan business trip</p>
        </div>
    </a>
    @endif
</div>

<!-- Divider -->
<div class="mt-6 mb-10 transform scale-y-50 border-t border-gray-300/80"></div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-stretch">
    <!-- Calendar Section -->
    @if($flags['cuti'])
    <x-dashboard.leave-calendar-widget
        :approvedByDate="$cutiPerTanggal"
        :holidayDates="$holidayDates ?? []"
        :holidaysByDate="$holidaysByDate ?? []"
        calendarSize="tall"
        title="Employee Leave Calendar"
        helperText="Klik tanggal untuk melihat daftar karyawan yang cuti." />
    @endif

    <!-- Recent Requests Section -->
    <div class="mb-8 bg-white border border-gray-200 rounded-lg lg:mb-0 lg:h-full flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Recent Requests</h3>
            <p class="text-sm text-gray-500">Your latest submissions</p>
        </div>
        <div class="p-6 lg:flex-1 lg:overflow-y-auto">
            @php
                $recentFiltered = collect($recentRequests)->filter(function ($r) use ($flags) {
                    return ($r['type'] === App\Enums\TypeRequest::Leaves->value && $flags['cuti'])
                        || ($r['type'] === App\Enums\TypeRequest::Reimbursements->value && $flags['reimbursement'])
                        || ($r['type'] === App\Enums\TypeRequest::Overtimes->value && $flags['overtime'])
                        || ($r['type'] === App\Enums\TypeRequest::Travels->value && $flags['perjalanan_dinas']);
                })->values();
            @endphp
            @forelse($recentFiltered as $request)
            <div class="flex items-center justify-between py-4 border-b border-gray-100 cursor-pointer last:border-0"
                onclick="window.location.href='{{ $request['url'] }}'">
                <!-- Kiri: ikon + judul -->
                <div class="flex items-center min-w-0">
                    @if($request['type'] === App\Enums\TypeRequest::Leaves->value)
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 mr-4 bg-blue-100 rounded-lg">
                        <i class="text-blue-600 fas fa-calendar-alt"></i>
                    </div>
                    @elseif($request['type'] === App\Enums\TypeRequest::Reimbursements->value)
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 mr-4 bg-purple-100 rounded-lg">
                        <i class="text-purple-600 fas fa-receipt"></i>
                    </div>
                    @elseif($request['type'] === App\Enums\TypeRequest::Overtimes->value)
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 mr-4 bg-green-100 rounded-lg">
                        <i class="text-green-600 fas fa-clock"></i>
                    </div>
                    @elseif($request['type'] === App\Enums\TypeRequest::Travels->value)
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 mr-4 bg-yellow-100 rounded-lg">
                        <i class="text-yellow-600 fas fa-plane"></i>
                    </div>
                    @endif
                    <div class="min-w-0">
                        <h4 class="font-medium text-gray-800 truncate">
                            {{ $request['title'] ?? ($request['type'] === App\Enums\TypeRequest::Overtimes->value ?
                            'Overtime Request' : 'Travel Request') }}
                        </h4>
                        <p class="text-sm text-gray-500 truncate">{{ $request['date'] }}</p>
                    </div>
                </div>

                <!-- Kanan: status + arrow -->
                <div class="flex items-center flex-shrink-0 ml-3">
                    @if(isset($request['status_2']) && $request['status_2'] !== null)
                    {{-- Jika ada status_2, maka cek keduanya --}}
                    @if($request['status_1'] === 'approved' && $request['status_2'] === 'approved')
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-green-800 bg-green-100 rounded-full">Approved</span>
                    @elseif($request['status_1'] === 'rejected' || $request['status_2'] === 'rejected')
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-red-800 bg-red-100 rounded-full">Rejected</span>
                    @else
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">Pending</span>
                    @endif
                    @else
                    {{-- Jika tidak ada status_2, cek hanya status_1 --}}
                    @if($request['status_1'] === 'approved')
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-green-800 bg-green-100 rounded-full">Approved</span>
                    @elseif($request['status_1'] === 'rejected')
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-red-800 bg-red-100 rounded-full">Rejected</span>
                    @else
                    <span
                        class="px-3 py-1 mr-3 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">Pending</span>
                    @endif
                    @endif
                </div>
            </div>
            @empty
            <div class="py-8 text-center">
                <i class="mb-3 text-4xl text-gray-300 fas fa-inbox"></i>
                <p class="text-gray-500">No recent requests found.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@endsection
