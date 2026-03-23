@extends('components.admin.layout.layout-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')

{{-- ===== STAT CARDS ===== --}}
<div class="mb-2">
    <p class="text-sm text-gray-500">Showing data for <span class="font-medium text-gray-700">{{ now()->format('F Y') }}</span></p>
</div>

<div class="grid grid-cols-2 gap-4 mb-6 lg:grid-cols-4">
    {{-- Total Employees --}}
    <div class="p-5 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-start justify-between">
            <div>
                <p class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase">Total Employees</p>
                <p class="text-2xl font-bold text-gray-900">{{ $total_employees }}</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-50 shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Pending Approvals --}}
    <div class="p-5 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-start justify-between">
            <div>
                <p class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase">Pending</p>
                <p class="text-2xl font-bold text-gray-900">{{ $total_pending }}</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0 bg-amber-50">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Approved --}}
    <div class="p-5 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-start justify-between">
            <div>
                <p class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase">Approved</p>
                <p class="text-2xl font-bold text-gray-900">{{ $total_approved }}</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-50 shrink-0">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Rejected --}}
    <div class="p-5 bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="flex items-start justify-between">
            <div>
                <p class="mb-1 text-xs font-medium tracking-wide text-gray-500 uppercase">Rejected</p>
                <p class="text-2xl font-bold text-gray-900">{{ $total_rejected }}</p>
            </div>
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-red-50 shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
        </div>
    </div>
</div>

{{-- ===== MAIN CONTENT GRID ===== --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- LEFT COLUMN: Charts (2/3 width) --}}
    <div class="space-y-6 lg:col-span-2">

        {{-- Calendar --}}
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Employee Leave Calendar</h3>
            </div>
            <div class="p-5">
                <div id="calendar">
                    {{-- Calendar Header --}}
                    <div class="flex items-center justify-between mb-4">
                        <button id="prev" class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                            <i class="text-xs fas fa-chevron-left"></i>
                        </button>
                        <h2 id="monthYear" class="text-sm font-semibold text-gray-800"></h2>
                        <button id="next" class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                            <i class="text-xs fas fa-chevron-right"></i>
                        </button>
                    </div>

                    {{-- Day Labels --}}
                    <div class="grid grid-cols-7 mb-1">
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                        <div class="py-1 text-xs font-medium text-center text-gray-400">{{ $day }}</div>
                        @endforeach
                    </div>

                    {{-- Date Grid --}}
                    <div id="dates" class="grid grid-cols-7"></div>
                </div>
                <p class="mt-3 text-xs text-center text-gray-400">
                    <span class="inline-block w-2 h-2 mr-1 align-middle bg-red-500 rounded-full"></span>
                    Click a marked date to see employees on leave
                </p>
            </div>
        </div>

        {{-- Monthly Requests Chart --}}
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Monthly Requests Overview</h3>
                <p class="text-xs text-gray-400">Total requests per month</p>
            </div>
            <div class="p-5">
                <div style="height: 220px;">
                    <canvas id="monthlyRequestsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Request Types Distribution --}}
        <div class="bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-800">Request Types Distribution</h3>
                <p class="text-xs text-gray-400">Breakdown by request type</p>
            </div>
            <div class="p-5">
                <div style="height: 220px;">
                    <canvas id="requestTypesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT COLUMN: Recent Requests (1/3 width) --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl lg:col-span-1">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Recent Requests</h3>
            <p class="text-xs text-gray-400">Latest submissions</p>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentRequests as $request)
            <div class="flex items-center gap-3 px-5 py-4 transition cursor-pointer hover:bg-gray-50"
                onclick="window.location.href='{{ $request['url'] }}'">

                {{-- Type Icon --}}
                @php
                    $iconMap = [
                        App\Enums\TypeRequest::Leaves->value        => ['bg-blue-50',   'text-blue-500',   'fa-calendar-alt'],
                        App\Enums\TypeRequest::Reimbursements->value => ['bg-purple-50', 'text-purple-500', 'fa-receipt'],
                        App\Enums\TypeRequest::Overtimes->value     => ['bg-green-50',  'text-green-500',  'fa-clock'],
                        App\Enums\TypeRequest::Travels->value       => ['bg-yellow-50', 'text-yellow-500', 'fa-plane'],
                    ];
                    [$iconBg, $iconColor, $iconClass] = $iconMap[$request['type']] ?? ['bg-gray-50', 'text-gray-400', 'fa-file'];
                @endphp
                <div class="flex items-center justify-center flex-shrink-0 w-9 h-9 rounded-lg {{ $iconBg }}">
                    <i class="text-sm {{ $iconColor }} fas {{ $iconClass }}"></i>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">
                        {{ $request['title'] ?? ($request['type'] === App\Enums\TypeRequest::Overtimes->value ? 'Overtime Request' : 'Travel Request') }}
                    </p>

                    @if(isset($request['employee_name']))
                    <div class="flex items-center gap-1.5 mt-0.5">
                        @if(isset($request['url_profile']) && $request['url_profile'])
                        <img src="{{ $request['url_profile'] }}" alt="{{ $request['employee_name'] }}"
                            class="object-cover w-4 h-4 rounded-full shrink-0">
                        @else
                        <div class="flex items-center justify-center w-4 h-4 text-blue-600 rounded-full bg-blue-50 shrink-0" style="font-size:9px">
                            {{ substr($request['employee_name'], 0, 1) }}
                        </div>
                        @endif
                        <span class="text-xs text-gray-500 truncate">{{ $request['employee_name'] }}</span>
                        @if(isset($request['division_name']))
                        <span class="text-gray-300">·</span>
                        <span class="text-xs text-gray-400 truncate">{{ $request['division_name'] }}</span>
                        @endif
                    </div>
                    @endif

                    <p class="mt-0.5 text-xs text-gray-400">{{ $request['date'] }}</p>
                </div>

                {{-- Status Badge --}}
                @php
                    $hasDouble = isset($request['status_2']) && $request['status_2'] !== null;
                    if ($hasDouble) {
                        $isApproved = $request['status_1'] === 'approved' && $request['status_2'] === 'approved';
                        $isRejected = $request['status_1'] === 'rejected' || $request['status_2'] === 'rejected';
                    } else {
                        $isApproved = $request['status_1'] === 'approved';
                        $isRejected = $request['status_1'] === 'rejected';
                    }
                @endphp
                @if($isApproved)
                <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">Approved</span>
                @elseif($isRejected)
                <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Rejected</span>
                @else
                <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">Pending</span>
                @endif
            </div>
            @empty
            <div class="py-12 text-center">
                <i class="mb-3 text-3xl text-gray-200 fas fa-inbox"></i>
                <p class="text-sm text-gray-400">No recent requests found.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== LEAVE MODAL ===== --}}
<div id="cutiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/40 backdrop-blur-sm">
    <div id="cutiModalContent"
        class="bg-white rounded-2xl mx-4 w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col shadow-xl transform transition-all duration-150 scale-95 opacity-0">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Employees on Leave</h2>
                <span id="currentPageInfo" class="text-xs text-gray-400">Page 1 of 1</span>
            </div>
            <div class="flex items-center gap-2">
                <button id="prevPage"
                    class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="text-xs fas fa-chevron-left"></i>
                </button>
                <button id="nextPage"
                    class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="text-xs fas fa-chevron-right"></i>
                </button>
                <button onclick="closeModal()"
                    class="flex items-center justify-center ml-1 text-gray-400 transition rounded-lg w-7 h-7 hover:bg-gray-100 hover:text-gray-600">
                    <i class="text-xs fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 p-5 overflow-hidden">
            <div id="cutiContainer" class="overflow-hidden">
                <div id="cutiPages" class="flex transition-transform duration-300 ease-in-out"></div>
            </div>
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
    // ===== CHART.JS =====
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = "#9CA3AF";

    const colors = {
        primary:   "#3B82F6",
        accent:    "#10B981",
        warning:   "#F59E0B",
        secondary: "#06B6D4",
        error:     "#EF4444",
        purple:    "#8B5CF6",
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Monthly Requests Chart
        const monthlyCtx = document.getElementById('monthlyRequestsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: chartData.months,
                datasets: [
                    { label: 'Reimbursements', data: chartData.reimbursements, backgroundColor: colors.primary + 'CC' },
                    { label: 'Overtimes',       data: chartData.overtimes,       backgroundColor: colors.accent + 'CC' },
                    { label: 'Leaves',          data: chartData.leaves,          backgroundColor: colors.warning + 'CC' },
                    { label: 'Official Travels',data: chartData.officialTravels, backgroundColor: colors.secondary + 'CC' },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 10, padding: 12, font: { size: 11 } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });

        // Doughnut Chart
        const typesCtx = document.getElementById('requestTypesChart').getContext('2d');
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
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 11 } } }
                },
                cutout: '65%'
            }
        });
    });

    // ===== CALENDAR =====
    const monthYear      = document.getElementById("monthYear");
    const datesContainer = document.getElementById("dates");
    const prevBtn        = document.getElementById("prev");
    const nextBtn        = document.getElementById("next");

    let today        = new Date();
    let currentMonth = today.getMonth();
    let currentYear  = today.getFullYear();

    const monthNames = ["January","February","March","April","May","June",
                        "July","August","September","October","November","December"];

    const cutiPerTanggal = @json($cutiPerTanggal);

    function renderCalendar(month, year) {
        datesContainer.innerHTML = "";
        monthYear.textContent = monthNames[month] + " " + year;

        const firstDay    = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            datesContainer.innerHTML += `<div></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr  = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday  = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const hasCuti  = !!cutiPerTanggal[dateStr];

            datesContainer.innerHTML += `
                <div onclick="showEvent('${dateStr}')"
                    class="relative flex items-center justify-center mx-auto my-0.5 w-8 h-8 rounded-lg text-xs cursor-pointer transition
                        ${isToday ? 'bg-blue-600 text-white font-bold' : 'text-gray-700 hover:bg-gray-100'}">
                    ${day}
                    ${hasCuti ? `<span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>` : ''}
                </div>
            `;
        }
    }

    // ===== MODAL =====
    let currentPage  = 1;
    let totalPages   = 1;
    const itemsPerPage = 6;

    function showEvent(dateStr) {
        const modal        = document.getElementById('cutiModal');
        const modalContent = document.getElementById('cutiModalContent');
        const cutiPages    = document.getElementById('cutiPages');

        cutiPages.innerHTML = "";
        currentPage = 1;

        if (cutiPerTanggal[dateStr]) {
            const employees = cutiPerTanggal[dateStr];
            totalPages = Math.ceil(employees.length / itemsPerPage);

            for (let page = 0; page < totalPages; page++) {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'w-full flex-shrink-0 grid grid-cols-2 gap-3';

                const start = page * itemsPerPage;
                const end   = Math.min(start + itemsPerPage, employees.length);

                for (let i = start; i < end; i++) {
                    const c = employees[i];
                    const letter = c.employee ? c.employee[0].toUpperCase() : '?';

                    pageDiv.innerHTML += `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            ${c.url_profile
                                ? `<img src="${c.url_profile}" alt="${c.employee}" class="object-cover rounded-full w-9 h-9 shrink-0">`
                                : `<div class="flex items-center justify-center text-xs font-semibold text-blue-600 bg-blue-100 rounded-full w-9 h-9 shrink-0">${letter}</div>`
                            }
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">${c.employee ?? '-'}</p>
                                <p class="text-xs text-gray-400 truncate">${c.email ?? '-'}</p>
                            </div>
                        </div>
                    `;
                }

                cutiPages.appendChild(pageDiv);
            }
        } else {
            cutiPages.innerHTML = `
                <div class="w-full py-10 text-center">
                    <i class="mb-2 text-2xl text-gray-200 fas fa-calendar-times"></i>
                    <p class="text-sm text-gray-400">No employees on leave on this date.</p>
                </div>
            `;
            totalPages = 1;
        }

        updatePaginationInfo();
        modal.classList.remove("hidden");
        setTimeout(() => {
            modalContent.classList.remove("scale-95", "opacity-0");
            modalContent.classList.add("scale-100", "opacity-100");
        }, 10);
    }

    function updatePaginationInfo() {
        document.getElementById('currentPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('nextPage').disabled = currentPage === totalPages;
        document.getElementById('cutiPages').style.transform = `translateX(-${(currentPage - 1) * 100}%)`;
    }

    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; updatePaginationInfo(); }
    });
    document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < totalPages) { currentPage++; updatePaginationInfo(); }
    });

    function closeModal() {
        const modal        = document.getElementById('cutiModal');
        const modalContent = document.getElementById('cutiModalContent');
        modalContent.classList.add("scale-95", "opacity-0");
        modalContent.classList.remove("scale-100", "opacity-100");
        setTimeout(() => modal.classList.add("hidden"), 150);
    }

    prevBtn.onclick = () => {
        currentMonth--;
        if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        renderCalendar(currentMonth, currentYear);
    };
    nextBtn.onclick = () => {
        currentMonth++;
        if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        renderCalendar(currentMonth, currentYear);
    };

    renderCalendar(currentMonth, currentYear);
</script>
@endpush
@endsection
