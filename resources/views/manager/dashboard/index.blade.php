@extends('components.manager.layout.layout-manager')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
@php
    $flags = $featureActive ?? [
        'cuti' => \App\Models\FeatureSetting::isActive('cuti'),
        'reimbursement' => \App\Models\FeatureSetting::isActive('reimbursement'),
        'overtime' => \App\Models\FeatureSetting::isActive('overtime'),
        'perjalanan_dinas' => \App\Models\FeatureSetting::isActive('perjalanan_dinas'),
    ];
@endphp
<x-dashboard.attendance-state-card :dashboardAttendanceState="$dashboardAttendanceState" />
<!-- Stats Cards - Light Neutral Background (15%) -->
<div class="mb-6">
    <p class="text-gray-600">Showing data for {{ now()->format('F Y') }}</p>
</div>

<div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
    <!-- Total Employees -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Total Employees</p>
                <p class="text-3xl font-bold text-gray-900">{{ $total_employees }}</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Pending Approvals - Warning Amber (5%) -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                <p class="text-3xl font-bold text-gray-900">{{ $total_pending }}</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-amber-100">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Approved Requests - Accent Green (10%) -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Approved This Month</p>
                <p class="text-3xl font-bold text-gray-900">{{ $total_approved }}</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Rejected Requests - Error Red (5%) -->
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Rejected Requests</p>
                <p class="text-3xl font-bold text-gray-900">{{ $total_rejected }}</p>
            </div>
            <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>
    </div>
</div>
<!-- Action Buttons -->
<div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 lg:grid-cols-4">
    @if($flags['cuti'])
    <button onclick="window.location.href='{{ route('manager.leaves.create') }}'" @if($sisaCuti <=0) disabled @endif
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
    <a href="{{ route('manager.reimbursements.create') }}"
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
    <a href="{{ route('manager.overtimes.create') }}"
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
    <a href="{{ route('manager.official-travels.create') }}"
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
        calendarSize="x-tall"
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
                            {{ $request['title'] ?? ($request['type'] === App\Enums\TypeRequest::Overtimes->value ? 'Overtime
                            Request' : 'Travel Request') }}
                        </h4>

                        <!-- Profile Picture, Name, and Division -->
                        @if(isset($request['employee_name']))
                        <div class="flex items-center gap-2 mt-1">
                            @if(isset($request['url_profile']) && $request['url_profile'])
                            <img src="{{ $request['url_profile'] }}" alt="{{ $request['employee_name'] }}"
                                class="object-cover w-6 h-6 border border-gray-200 rounded-full">
                            @else
                            <!-- Default profile dengan background abu-abu terang -->
                            <div
                                class="flex items-center justify-center w-6 h-6 text-xs text-blue-600 border border-blue-100 rounded-full bg-blue-50">
                                {{ substr($request['employee_name'], 0, 1) }}
                            </div>
                            @endif

                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-800">
                                    {{ $request['employee_name'] }}
                                </span>
                                @if(isset($request['division_name']))
                                <span class="text-xs text-gray-500">
                                    {{ $request['division_name'] }}
                                </span>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Date -->
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $request['date'] }}
                        </p>
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

<script>
    const chartData = {
            months: @json($months),
            reimbursements: @json($reimbursementsChartData),
            overtimes: @json($overtimesChartData),
            leaves: @json($leavesChartData),
            officialTravels: @json($officialTravelsChartData),
            reimbursementsTotal: @json($reimbursementsRupiahChartData),
            pendings: @json($total_pending),
            approveds: @json($total_approved),
            rejecteds: @json($total_rejected)
        };
</script>
@push('scripts')
<script>
    // Chart.js Configuration
    Chart.defaults.font.family = "Inter, system-ui, sans-serif";
    Chart.defaults.color = "#6B7280";

    // Color scheme based on the design system
    const colors = {
        primary: "#2563EB", // Blue
        secondary: "#0EA5E9", // Sky Blue
        accent: "#10B981", // Green
        warning: "#F59E0B", // Amber
        error: "#EF4444", // Red
        neutral: "#6B7280", // Gray
        light: "#F3F4F6", // Light Gray
    };

    // Handle window resize
    window.addEventListener("resize", function () {
        if (window.innerWidth >= 1024) {
            sidebarOverlay.classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }
    });

</script>
@endpush
@endsection
