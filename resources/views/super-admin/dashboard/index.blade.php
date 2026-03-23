@extends('components.super-admin.layout.layout-super-admin')

@section('header', 'Dashboard')
@section('subtitle', 'Welcome Back!')

@section('content')
<div class="flex flex-col gap-6 xl:flex-row">

    <!-- Left Column: Stats & Charts -->
    <div class="flex-1 space-y-6">

        <!-- Stats Cards Grid -->
        <div>
            <p class="mb-4 text-gray-600">Showing data for <span class="font-semibold">{{ now()->format('F Y') }}</span></p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <!-- Total Employees -->
                <div class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Employees</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 lg:text-3xl">{{ $total_employees }}</p>
                        </div>
                        <div class="flex items-center justify-center bg-blue-100 rounded-lg w-11 h-11">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 lg:text-3xl">{{ $total_pending }}</p>
                        </div>
                        <div class="flex items-center justify-center rounded-lg w-11 h-11 bg-amber-100">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Approved Requests -->
                <div class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Approved This Month</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 lg:text-3xl">{{ $total_approved }}</p>
                        </div>
                        <div class="flex items-center justify-center bg-green-100 rounded-lg w-11 h-11">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Rejected Requests -->
                <div class="p-5 transition-shadow bg-white border border-gray-100 shadow-sm rounded-xl hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Rejected Requests</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900 lg:text-3xl">{{ $total_rejected }}</p>
                        </div>
                        <div class="flex items-center justify-center bg-red-100 rounded-lg w-11 h-11">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="space-y-6">
            <!-- Monthly Requests Chart -->
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Monthly Requests Overview</h3>
                    <p class="text-sm text-gray-500">Total requests per month</p>
                </div>
                <div class="p-6">
                    <canvas id="monthlyRequestsChart" class="w-full" height="250"></canvas>
                </div>
            </div>

            <!-- Request Types Distribution -->
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800">Request Types Distribution</h3>
                    <p class="text-sm text-gray-500">Breakdown by request type</p>
                </div>
                <div class="p-6">
                    <canvas id="requestTypesChart" class="w-full" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Calendar & Recent Requests -->
    <div class="space-y-6 xl:w-80 2xl:w-96">

        <!-- Calendar Widget -->
        <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-center text-gray-800">Employee Leave Calendar</h3>
            </div>
            <div class="p-4">
                <div id="calendar" class="bg-white rounded-lg">
                    <!-- Header: Month Navigation -->
                    <div class="flex items-center justify-between mb-3">
                        <button id="prev" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="text-sm text-gray-500 fas fa-chevron-left"></i>
                        </button>
                        <h2 id="monthYear" class="text-sm font-bold text-gray-800"></h2>
                        <button id="next" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="text-sm text-gray-500 fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Day Labels -->
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Min</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Sen</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Sel</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Rab</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Kam</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Jum</div>
                        <div class="py-1 text-xs font-medium text-center text-gray-400">Sab</div>
                    </div>

                    <!-- Dates Grid -->
                    <div id="dates" class="grid grid-cols-7 gap-1"></div>
                </div>
                <p class="mt-3 text-xs text-center text-gray-400">
                    * Klik tanggal bertanda <span class="font-medium text-red-500">●</span> untuk detail
                </p>
            </div>
        </div>

        <!-- Recent Requests Widget -->
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-800">Recent Requests</h3>
            </div>
            <div class="overflow-y-auto divide-y divide-gray-100 max-h-96">
                @forelse($recentRequests as $request)
                <div class="p-4 transition-colors cursor-pointer hover:bg-gray-50"
                    onclick="window.location.href='{{ $request['url'] }}'">
                    <div class="flex items-start gap-3">
                        <!-- Icon Type -->
                        <div class="flex-shrink-0">
                            @if($request['type'] === App\Enums\TypeRequest::Leaves->value)
                                <div class="flex items-center justify-center bg-blue-100 rounded-lg w-9 h-9">
                                    <i class="text-sm text-blue-600 fas fa-calendar-alt"></i>
                                </div>
                            @elseif($request['type'] === App\Enums\TypeRequest::Reimbursements->value)
                                <div class="flex items-center justify-center bg-purple-100 rounded-lg w-9 h-9">
                                    <i class="text-sm text-purple-600 fas fa-receipt"></i>
                                </div>
                            @elseif($request['type'] === App\Enums\TypeRequest::Overtimes->value)
                                <div class="flex items-center justify-center bg-green-100 rounded-lg w-9 h-9">
                                    <i class="text-sm text-green-600 fas fa-clock"></i>
                                </div>
                            @elseif($request['type'] === App\Enums\TypeRequest::Travels->value)
                                <div class="flex items-center justify-center rounded-lg w-9 h-9 bg-amber-100">
                                    <i class="text-sm fas fa-plane text-amber-600"></i>
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-gray-800 truncate">
                                {{ $request['title'] ?? ($request['type'] === App\Enums\TypeRequest::Overtimes->value ? 'Overtime Request' : 'Travel Request') }}
                            </h4>

                            @if(isset($request['employee_name']))
                            <div class="flex items-center gap-2 mt-1">
                                @if(isset($request['url_profile']) && $request['url_profile'])
                                    <img src="{{ $request['url_profile'] }}" alt="{{ $request['employee_name'] }}"
                                        class="object-cover w-5 h-5 border border-gray-200 rounded-full">
                                @else
                                    <div class="flex items-center justify-center w-5 h-5 border border-blue-100 rounded-full bg-blue-50">
                                        <span class="text-xs font-medium text-blue-600">{{ substr($request['employee_name'], 0, 1) }}</span>
                                    </div>
                                @endif
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-medium text-gray-700 truncate">{{ $request['employee_name'] }}</span>
                                    @if(isset($request['division_name']))
                                        <span class="text-xs text-gray-400 truncate">{{ $request['division_name'] }}</span>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-400">{{ $request['date'] }}</span>

                                @if(isset($request['status_2']) && $request['status_2'] !== null)
                                    @if($request['status_1'] === 'approved' && $request['status_2'] === 'approved')
                                        <span class="px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full">Approved</span>
                                    @elseif($request['status_1'] === 'rejected' || $request['status_2'] === 'rejected')
                                        <span class="px-2 py-0.5 text-xs font-medium text-red-700 bg-red-100 rounded-full">Rejected</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium text-amber-700 bg-amber-100 rounded-full">Pending</span>
                                    @endif
                                @else
                                    @if($request['status_1'] === 'approved')
                                        <span class="px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full">Approved</span>
                                    @elseif($request['status_1'] === 'rejected')
                                        <span class="px-2 py-0.5 text-xs font-medium text-red-700 bg-red-100 rounded-full">Rejected</span>
                                    @else
                                        <span class="px-2 py-0.5 text-xs font-medium text-amber-700 bg-amber-100 rounded-full">Pending</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-6 text-center">
                    <i class="mb-2 text-3xl text-gray-300 fas fa-inbox"></i>
                    <p class="text-sm text-gray-500">No recent requests found.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal: Employee on Leave (Refined) -->
<div id="cutiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden p-4 bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-hidden shadow-2xl transform transition-all duration-200 scale-95 opacity-0"
        id="cutiModalContent">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-800">Employees on Leave</h2>
            <button onclick="closeModal()" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="text-gray-400 fas fa-times hover:text-gray-600"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="px-5 py-4">
            <!-- Pagination Controls -->
            <div class="flex items-center justify-between mb-4">
                <span id="currentPageInfo" class="text-xs text-gray-500">Page 1 of 1</span>
                <div class="flex gap-1.5">
                    <button id="prevPage" class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="text-xs text-gray-600 fas fa-chevron-left"></i>
                    </button>
                    <button id="nextPage" class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <i class="text-xs text-gray-600 fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Employee List Container -->
            <div id="cutiContainer" class="min-h-48">
                <div id="cutiPages" class="flex transition-transform duration-300 ease-in-out"></div>
            </div>

            <!-- Page Indicators (Mobile) -->
            <div class="flex justify-center gap-1.5 mt-4 sm:hidden" id="pageIndicators"></div>
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

    const colors = {
        primary: "#3B82F6",
        secondary: "#06B6D4",
        accent: "#10B981",
        warning: "#F59E0B",
        error: "#EF4444",
        info: "#8B5CF6",
        neutral: "#6B7280",
        light: "#F3F4F6",
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Requests Chart
        const monthlyCtx = document.getElementById('monthlyRequestsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: chartData.months,
                datasets: [
                    { label: 'Reimbursements', data: chartData.reimbursements, borderColor: colors.primary, backgroundColor: colors.primary + 'CC', tension: 0.4, fill: true },
                    { label: 'Overtimes', data: chartData.overtimes, borderColor: colors.accent, backgroundColor: colors.accent + 'CC', tension: 0.4, fill: true },
                    { label: 'Leaves', data: chartData.leaves, borderColor: colors.warning, backgroundColor: colors.warning + 'CC', tension: 0.4, fill: true },
                    { label: 'Official Travels', data: chartData.officialTravels, borderColor: colors.secondary, backgroundColor: colors.secondary + 'CC', tension: 0.4, fill: true }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });

        // Request Types Distribution
        const typesCtx = document.getElementById('requestTypesChart').getContext('2d');
        const totalReimbursements = chartData.reimbursements.reduce((a,b) => a+b, 0);
        const totalOvertimes = chartData.overtimes.reduce((a,b) => a+b, 0);
        const totalLeaves = chartData.leaves.reduce((a,b) => a+b, 0);
        const totalTravels = chartData.officialTravels.reduce((a,b) => a+b, 0);

        new Chart(typesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Reimbursements', 'Overtimes', 'Leaves', 'Official Travels'],
                datasets: [{
                    data: [totalReimbursements, totalOvertimes, totalLeaves, totalTravels],
                    backgroundColor: [colors.primary, colors.accent, colors.warning, colors.secondary],
                    borderWidth: 2, borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15, font: { size: 11 } } } },
                cutout: '65%'
            }
        });
    });

    // Calendar Logic
    const monthYear = document.getElementById("monthYear");
    const datesContainer = document.getElementById("dates");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");
    let today = new Date(), currentMonth = today.getMonth(), currentYear = today.getFullYear();
    const monthNames = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];
    const cutiPerTanggal = @json($cutiPerTanggal);

    function renderCalendar(month, year) {
        datesContainer.innerHTML = "";
        monthYear.textContent = `${monthNames[month]} ${year}`;

        let firstDay = new Date(year, month, 1).getDay();
        let daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty slots
        for (let i = 0; i < firstDay; i++) {
            datesContainer.innerHTML += `<div class="aspect-square"></div>`;
        }

        // Date cells
        for (let day = 1; day <= daysInMonth; day++) {
            let dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            let isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());
            let hasLeave = cutiPerTanggal[dateStr];

            datesContainer.innerHTML += `
                <button onclick="showEvent('${dateStr}')"
                    class="aspect-square flex flex-col items-center justify-center rounded-lg text-xs font-medium transition-all relative group
                    ${isToday ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-200' : 'text-gray-700 hover:bg-gray-100'}
                    ${hasLeave ? 'hover:bg-red-50' : ''}">
                    <span>${day}</span>
                    ${hasLeave ? '<span class="absolute bottom-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>' : ''}
                </button>
            `;
        }
    }

    // Modal Pagination
    let currentPage = 1, totalPages = 1, itemsPerPage = 6;

    function showEvent(dateStr) {
        const modal = document.getElementById('cutiModal');
        const modalContent = document.getElementById('cutiModalContent');
        const cutiPages = document.getElementById('cutiPages');
        const pageIndicators = document.getElementById('pageIndicators');

        cutiPages.innerHTML = "";
        pageIndicators.innerHTML = "";
        currentPage = 1;

        if (cutiPerTanggal[dateStr]) {
            const employees = cutiPerTanggal[dateStr];
            totalPages = Math.ceil(employees.length / itemsPerPage);

            employees.forEach((cuti, index) => {
                const pageIndex = Math.floor(index / itemsPerPage);
                let pageEl = cutiPages.children[pageIndex];

                if (!pageEl) {
                    pageEl = document.createElement('div');
                    pageEl.className = 'w-full flex-shrink-0 grid grid-cols-2 gap-2 h-[52px]';
                    cutiPages.appendChild(pageEl);
                }

                let firstLetter = cuti.employee ? cuti.employee.substring(0, 1).toUpperCase() : "?";
                pageEl.innerHTML += `
                    <div class="flex items-center gap-2 p-2.5 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        ${cuti.url_profile ?
                            `<img src="${cuti.url_profile}" class="object-cover w-8 h-8 border border-gray-200 rounded-full">` :
                            `<div class="flex items-center justify-center w-8 h-8 border border-blue-100 rounded-full bg-blue-50"><span class="text-xs font-medium text-blue-600">${firstLetter}</span></div>`
                        }
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-800 truncate">${cuti.employee ?? '-'}</p>
                            <p class="text-[10px] text-gray-400 truncate">${cuti.email ?? '-'}</p>
                        </div>
                    </div>
                `;
            });

            // Generate indicators
            for (let i = 0; i < totalPages; i++) {
                const indicator = document.createElement('div');
                indicator.className = `w-1.5 h-1.5 rounded-full transition-colors ${i === 0 ? 'bg-blue-500' : 'bg-gray-300'}`;
                indicator.dataset.page = i + 1;
                pageIndicators.appendChild(indicator);
            }
            updatePaginationInfo();
        } else {
            cutiPages.innerHTML = `<div class="w-full py-6 text-sm text-center text-gray-500">Tidak ada karyawan cuti pada tanggal ini</div>`;
            document.getElementById('currentPageInfo').textContent = "Page 1 of 1";
            document.getElementById('prevPage').disabled = true;
            document.getElementById('nextPage').disabled = true;
        }

        modal.classList.remove("hidden");
        setTimeout(() => {
            modalContent.classList.remove("scale-95", "opacity-0");
            modalContent.classList.add("scale-100", "opacity-100");
        }, 10);
    }

    function updatePaginationInfo() {
        const info = document.getElementById('currentPageInfo');
        const prev = document.getElementById('prevPage');
        const next = document.getElementById('nextPage');
        const cutiPages = document.getElementById('cutiPages');
        const indicators = document.querySelectorAll('#pageIndicators .page-indicator, #pageIndicators > div');

        info.textContent = `Page ${currentPage} of ${totalPages}`;
        prev.disabled = currentPage === 1;
        next.disabled = currentPage === totalPages;
        cutiPages.style.transform = `translateX(-${(currentPage - 1) * 100}%)`;

        indicators.forEach((ind, idx) => {
            ind.classList.toggle('bg-blue-500', idx + 1 === currentPage);
            ind.classList.toggle('bg-gray-300', idx + 1 !== currentPage);
        });
    }

    document.getElementById('prevPage').onclick = () => { if (currentPage > 1) { currentPage--; updatePaginationInfo(); }};
    document.getElementById('nextPage').onclick = () => { if (currentPage < totalPages) { currentPage++; updatePaginationInfo(); }};

    function closeModal() {
        const modal = document.getElementById('cutiModal');
        const content = document.getElementById('cutiModalContent');
        content.classList.add("scale-95", "opacity-0");
        content.classList.remove("scale-100", "opacity-100");
        setTimeout(() => modal.classList.add("hidden"), 150);
    }

    // Close modal on backdrop click
    document.getElementById('cutiModal').onclick = (e) => { if (e.target.id === 'cutiModal') closeModal(); };

    prevBtn.onclick = () => { currentMonth--; if (currentMonth < 0) { currentMonth = 11; currentYear--; } renderCalendar(currentMonth, currentYear); };
    nextBtn.onclick = () => { currentMonth++; if (currentMonth > 11) { currentMonth = 0; currentYear++; } renderCalendar(currentMonth, currentYear); };

    renderCalendar(currentMonth, currentYear);
</script>
@endpush
@endsection
