@extends('components.admin.layout.layout-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
    <x-dashboard.attendance-state-card :dashboardAttendanceState="$dashboardAttendanceState" />

    @if(Auth::user()->hasRole('manager') || Auth::user()->hasRole('approver'))
    <div class="mb-6 rounded-2xl border border-orange-100 bg-orange-50 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100">
                    <i class="fas fa-tasks text-orange-500"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-orange-800">Approval Queue</p>
                    <p class="text-xs text-orange-600">
                        You have approval responsibilities as
                        @if(Auth::user()->hasRole('manager') && Auth::user()->hasRole('approver'))
                            Manager &amp; Team Leader
                        @elseif(Auth::user()->hasRole('manager'))
                            Manager
                        @else
                            Team Leader
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(Auth::user()->hasRole('manager'))
                    <a href="{{ route('manager.dashboard') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-orange-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-orange-600 transition-colors">
                        <i class="fas fa-external-link-alt text-[10px]"></i> Manager View
                    </a>
                @endif
                @if(Auth::user()->hasRole('approver'))
                    <a href="{{ route('approver.dashboard') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-600 transition-colors">
                        <i class="fas fa-external-link-alt text-[10px]"></i> Team Leader View
                    </a>
                @endif
            </div>
        </div>
    </div>
    @endif

    @include('components.dashboard.requests-overview')
@endsection

