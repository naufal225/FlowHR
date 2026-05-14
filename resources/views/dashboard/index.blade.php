@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')
@section('subtitle', 'Selamat datang, ' . Auth::user()->name)

@section('content')

{{-- Attendance state card --}}
@if($dashboardAttendanceState)
<x-dashboard.attendance-state-card :dashboardAttendanceState="$dashboardAttendanceState" />
@endif

{{-- ── Personal Stats (all roles) ─────────────────────────────────────── --}}
<section class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Total Pending</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $myStats['total_pending'] }}</p>
        <p class="mt-0.5 text-xs text-gray-400">Semua pengajuanmu</p>
    </article>
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $myStats['total_approved'] }}</p>
        <p class="mt-0.5 text-xs text-gray-400">Bulan ini</p>
    </article>
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Rejected</p>
        <p class="mt-1 text-2xl font-bold text-rose-500">{{ $myStats['total_rejected'] }}</p>
        <p class="mt-0.5 text-xs text-gray-400">Bulan ini</p>
    </article>
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Sisa Cuti</p>
        <p class="mt-1 text-2xl font-bold text-blue-600">{{ $myStats['sisa_cuti'] }}</p>
        <p class="mt-0.5 text-xs text-gray-400">Hari tersisa</p>
    </article>
</section>

{{-- ── Org Stats (admin / manager / approver) ─────────────────────────── --}}
@if($orgStats)
<section class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Total Karyawan</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $orgStats['totalEmployees'] }}</p>
    </article>
    <article class="p-5 bg-white border shadow-sm border-amber-100 bg-amber-50 rounded-2xl">
        <p class="text-xs font-semibold tracking-wide uppercase text-amber-600">Org Pending</p>
        <p class="mt-1 text-2xl font-bold text-amber-700">{{ $orgStats['orgPending'] }}</p>
        <p class="mt-0.5 text-xs text-amber-500">Semua pengajuan</p>
    </article>
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Org Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $orgStats['orgApproved'] }}</p>
    </article>
    <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Org Rejected</p>
        <p class="mt-1 text-2xl font-bold text-rose-500">{{ $orgStats['orgRejected'] }}</p>
    </article>
</section>
@endif

<section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
    {{-- Left: Calendar + Quick Actions ─────────────────────────────────── --}}
    <div class="space-y-6 lg:col-span-7">

        {{-- Quick Actions --}}
        <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
            <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-800">Quick Actions</h3>
            </header>
            <div class="flex flex-wrap gap-3 p-5">
                @if($featureActive['cuti'])
                <a href="{{ route('leaves.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-calendar-plus"></i> Request Leave
                </a>
                @endif
                @if($featureActive['reimbursement'])
                <a href="{{ route('reimbursements.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700">
                    <i class="fas fa-receipt"></i> Submit Reimbursement
                </a>
                @endif
                @if($featureActive['overtime'])
                <a href="{{ route('overtimes.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                    <i class="fas fa-clock"></i> Request Overtime
                </a>
                @endif
                @if($featureActive['perjalanan_dinas'])
                <a href="{{ route('official-travels.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-lg bg-amber-600 hover:bg-amber-700">
                    <i class="fas fa-plane"></i> Request Travel
                </a>
                @endif

                {{-- Approval shortcuts (approver/manager) --}}
                @if($permissions['canApproveLeave'] || $permissions['canApproveStage1'])
                <a href="{{ route('leaves.index', ['status' => 'pending']) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600">
                    <i class="fas fa-check-circle"></i> Pending Approvals
                </a>
                @endif
            </div>
        </article>

        {{-- Leave Calendar --}}
        @if($featureActive['cuti'] && count($cutiPerTanggal))
        <x-dashboard.leave-calendar-widget
            :approvedByDate="$cutiPerTanggal"
            :holidayDates="$holidayDates ?? []"
            :holidaysByDate="$holidaysByDate ?? []"
            title="Leave Calendar"
            helperText="Klik tanggal untuk melihat karyawan cuti."
            :fillHeight="false" />
        @endif
    </div>

    {{-- Right: Recent Requests ─────────────────────────────────────────── --}}
    <aside class="lg:col-span-5">
        <article class="sticky overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl top-6">
            <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-800">Recent Requests</h3>
                <p class="text-xs text-gray-400">Latest submissions</p>
            </header>
            <div class="divide-y divide-gray-50 max-h-[calc(100vh-14rem)] overflow-y-auto">
                @forelse($recentRequests as $req)
                    @php
                        $iconMap = [
                            \App\Enums\TypeRequest::Leaves->value       => ['bg-blue-50',   'text-blue-500',   'fa-calendar-alt', 'Leave'],
                            \App\Enums\TypeRequest::Reimbursements->value=> ['bg-purple-50', 'text-purple-500', 'fa-receipt',      'Reimbursement'],
                            \App\Enums\TypeRequest::Overtimes->value     => ['bg-green-50',  'text-green-500',  'fa-clock',        'Overtime'],
                            \App\Enums\TypeRequest::Travels->value       => ['bg-amber-50',  'text-amber-500',  'fa-plane',        'Travel'],
                        ];
                        [$iconBg, $iconColor, $iconClass, $label] = $iconMap[$req['type']] ?? ['bg-gray-50', 'text-gray-400', 'fa-file', 'Request'];
                        $hasDouble  = isset($req['status_2']) && $req['status_2'] !== null;
                        $isApproved = $hasDouble
                            ? $req['status_1'] === 'approved' && $req['status_2'] === 'approved'
                            : $req['status_1'] === 'approved';
                        $isRejected = $hasDouble
                            ? $req['status_1'] === 'rejected' || $req['status_2'] === 'rejected'
                            : $req['status_1'] === 'rejected';
                    @endphp
                    <a href="{{ $req['url'] }}" class="block px-5 py-4 transition-colors hover:bg-gray-50">
                        <div class="flex items-start gap-3">
                            <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-lg {{ $iconBg }}">
                                <i class="text-base {{ $iconColor }} fas {{ $iconClass }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $req['title'] ?? $label }}</p>
                                @if(isset($req['employee_name']))
                                <p class="text-xs text-gray-500 truncate">{{ $req['employee_name'] }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-400">{{ $req['date'] }}</p>
                                    @if($isApproved)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Approved
                                    </span>
                                    @elseif($isRejected)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-600">
                                        <span class="w-1.5 h-1.5 mr-1 bg-rose-500 rounded-full"></span>Rejected
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                                        <span class="w-1.5 h-1.5 mr-1 bg-amber-500 rounded-full"></span>Pending
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                <div class="py-12 text-center">
                    <i class="mb-3 text-3xl text-gray-200 fas fa-inbox"></i>
                    <p class="text-sm text-gray-400">Belum ada pengajuan.</p>
                </div>
                @endforelse
            </div>
        </article>
    </aside>
</section>

@endsection
