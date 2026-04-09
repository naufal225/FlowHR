<div class="space-y-6">
    {{-- Stats Cards Grid --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Total Employees</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $total_employees }}</p>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-blue-50">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                </div>
            </div>
        </article>

        <article class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Pending</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $total_pending }}</p>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-amber-50">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </article>

        <article class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Approved</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $total_approved }}</p>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-emerald-50">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </article>

        <article class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-2xl hover:shadow-md">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Rejected</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $total_rejected }}</p>
                </div>
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-rose-50">
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
        </article>
    </section>

    {{-- Main Content: Calendar + Charts + Recent Requests --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Left Column: Calendar + Charts --}}
        <div class="space-y-6 lg:col-span-7">
            {{-- Calendar Widget --}}
            <x-dashboard.leave-calendar-widget
                :approvedByDate="$cutiPerTanggal"
                :holidayDates="$holidayDates ?? []"
                :holidaysByDate="$holidaysByDate ?? []"
                title="Employee Leave Calendar"
                helperText="Klik tanggal untuk melihat daftar karyawan yang cuti."
                :fillHeight="false" />

            {{-- Monthly Requests Chart --}}
            <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-800">Monthly Requests Overview</h3>
                    <p class="text-xs text-gray-400">Total requests per month</p>
                </header>
                <div class="p-5">
                    <div class="h-64">
                        <canvas id="monthlyRequestsChart"></canvas>
                    </div>
                </div>
            </article>

            {{-- Request Types Distribution Chart --}}
            <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-800">Request Types Distribution</h3>
                    <p class="text-xs text-gray-400">Breakdown by request type</p>
                </header>
                <div class="p-5">
                    <div class="h-64">
                        <canvas id="requestTypesChart"></canvas>
                    </div>
                </div>
            </article>
        </div>

        {{-- Right Column: Recent Requests --}}
        <aside class="lg:col-span-5">
            <article class="sticky overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl top-6">
                <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-800">Recent Requests</h3>
                    <p class="text-xs text-gray-400">Latest submissions</p>
                </header>

                <div class="divide-y divide-gray-50 max-h-[calc(100vh-12rem)] overflow-y-auto">
                    @forelse($recentRequests as $request)
                        @php
                            $iconMap = [
                                App\Enums\TypeRequest::Leaves->value => ['bg-blue-50', 'text-blue-500', 'fa-calendar-alt', 'Leave Request'],
                                App\Enums\TypeRequest::Reimbursements->value => ['bg-purple-50', 'text-purple-500', 'fa-receipt', 'Reimbursement'],
                                App\Enums\TypeRequest::Overtimes->value => ['bg-green-50', 'text-green-500', 'fa-clock', 'Overtime'],
                                App\Enums\TypeRequest::Travels->value => ['bg-amber-50', 'text-amber-500', 'fa-plane', 'Travel'],
                            ];
                            [$iconBg, $iconColor, $iconClass, $defaultTitle] = $iconMap[$request['type']] ?? ['bg-gray-50', 'text-gray-400', 'fa-file', 'Request'];

                            $hasDouble = isset($request['status_2']) && $request['status_2'] !== null;
                            if ($hasDouble) {
                                $isApproved = $request['status_1'] === 'approved' && $request['status_2'] === 'approved';
                                $isRejected = $request['status_1'] === 'rejected' || $request['status_2'] === 'rejected';
                            } else {
                                $isApproved = $request['status_1'] === 'approved';
                                $isRejected = $request['status_1'] === 'rejected';
                            }
                        @endphp

                        <a href="{{ $request['url'] }}"
                            class="block px-5 py-4 transition-colors hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-inset">
                            <div class="flex items-start gap-3">
                                <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-lg {{ $iconBg }}">
                                    <i class="text-base {{ $iconColor }} fas {{ $iconClass }}"></i>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">
                                        {{ $request['title'] ?? $defaultTitle }}
                                    </p>

                                    @if (isset($request['employee_name']))
                                        <div class="flex items-center gap-2 mt-1.5">
                                            @if (isset($request['url_profile']) && $request['url_profile'])
                                                <img src="{{ $request['url_profile'] }}" alt="{{ $request['employee_name'] }}"
                                                    class="object-cover w-5 h-5 border border-gray-200 rounded-full shrink-0">
                                            @else
                                                <div class="flex items-center justify-center w-5 h-5 text-[10px] font-medium text-blue-600 rounded-full bg-blue-50 shrink-0 border border-blue-100">
                                                    {{ substr($request['employee_name'], 0, 1) }}
                                                </div>
                                            @endif
                                            <div class="flex flex-col min-w-0">
                                                <span class="text-xs font-medium text-gray-700 truncate">{{ $request['employee_name'] }}</span>
                                                @if (isset($request['division_name']))
                                                    <span class="text-[10px] text-gray-400 truncate">{{ $request['division_name'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex items-center justify-between mt-2.5 gap-x-2">
                                        <p class="text-xs text-gray-400">{{ $request['date'] }}</p>

                                        @if ($isApproved)
                                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-emerald-500 rounded-full"></span>
                                                Approved
                                            </span>
                                        @elseif($isRejected)
                                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-rose-500 rounded-full"></span>
                                                Rejected
                                            </span>
                                        @else
                                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-amber-500 rounded-full"></span>
                                                Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="py-12 text-center">
                            <div class="inline-flex items-center justify-center mx-auto mb-3 rounded-full w-14 h-14 bg-gray-50">
                                <i class="text-2xl text-gray-300 fas fa-inbox"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-600">No recent requests</p>
                            <p class="mt-1 text-xs text-gray-400">New requests will appear here</p>
                        </div>
                    @endforelse
                </div>
            </article>
        </aside>
    </section>
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
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = "#6B7280";

    const colors = {
        primary: "#3B82F6",
        accent: "#10B981",
        warning: "#F59E0B",
        secondary: "#06B6D4",
        error: "#EF4444",
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Requests Chart
        const monthlyCanvas = document.getElementById('monthlyRequestsChart');
        if (monthlyCanvas) {
            const monthlyCtx = monthlyCanvas.getContext('2d');
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: chartData.months,
                    datasets: [
                        {
                            label: 'Reimbursements',
                            data: chartData.reimbursements,
                            backgroundColor: colors.primary + 'CC',
                            borderRadius: 6,
                            barPercentage: 0.7,
                        },
                        {
                            label: 'Overtimes',
                            data: chartData.overtimes,
                            backgroundColor: colors.accent + 'CC',
                            borderRadius: 6,
                            barPercentage: 0.7,
                        },
                        {
                            label: 'Leaves',
                            data: chartData.leaves,
                            backgroundColor: colors.warning + 'CC',
                            borderRadius: 6,
                            barPercentage: 0.7,
                        },
                        {
                            label: 'Official Travels',
                            data: chartData.officialTravels,
                            backgroundColor: colors.secondary + 'CC',
                            borderRadius: 6,
                            barPercentage: 0.7,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 10,
                                padding: 15,
                                font: { size: 10 },
                                usePointStyle: true,
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                            ticks: { font: { size: 10 }, precision: 0, padding: 8 }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { font: { size: 10 }, padding: 8 }
                        }
                    }
                }
            });
        }

        // Request Types Distribution Chart
        const typesCanvas = document.getElementById('requestTypesChart');
        if (typesCanvas) {
            const typesCtx = typesCanvas.getContext('2d');
            const totalR = chartData.reimbursements.reduce((a, b) => a + b, 0);
            const totalO = chartData.overtimes.reduce((a, b) => a + b, 0);
            const totalL = chartData.leaves.reduce((a, b) => a + b, 0);
            const totalT = chartData.officialTravels.reduce((a, b) => a + b, 0);

            new Chart(typesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Reimbursements', 'Overtimes', 'Leaves', 'Official Travels'],
                    datasets: [{
                        data: [totalR, totalO, totalL, totalT],
                        backgroundColor: [colors.primary, colors.accent, colors.warning, colors.secondary],
                        borderWidth: 0,
                        spacing: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 15,
                                font: { size: 10 },
                                usePointStyle: true,
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    });

</script>
@endpush
