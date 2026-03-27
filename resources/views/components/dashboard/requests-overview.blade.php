<div class="space-y-6">
    {{-- Top Row: Stats + Calendar --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 lg:col-span-8 content-start items-start self-start">
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
        </div>

        {{-- Calendar Widget (Top Right) --}}
        <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl lg:col-span-4">
            <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Employee Leave Calendar</h3>
                    <span class="text-xs text-gray-500">{{ now()->format('F Y') }}</span>
                </div>
            </header>
            <div class="p-5">
                <div id="calendar">
                    <div class="flex items-center justify-between mb-4">
                        <button id="prev" type="button" aria-label="Previous month"
                            class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                            <i class="text-xs fas fa-chevron-left"></i>
                        </button>
                        <h2 id="monthYear" class="text-sm font-semibold text-gray-800"></h2>
                        <button id="next" type="button" aria-label="Next month"
                            class="flex items-center justify-center w-8 h-8 text-gray-500 transition rounded-lg hover:bg-gray-100">
                            <i class="text-xs fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-7 mb-2">
                        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                            <div class="py-2 text-xs font-medium text-center text-gray-400">{{ $day }}</div>
                        @endforeach
                    </div>

                    <div id="dates" class="grid grid-cols-7 gap-1"></div>
                </div>
                <div class="flex items-center justify-center gap-4 pt-3 mt-4 border-t border-gray-100">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
                        <span class="text-xs text-gray-500">Leave</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block w-2 h-2 bg-blue-600 rounded-full"></span>
                        <span class="text-xs text-gray-500">Today</span>
                    </div>
                </div>
            </div>
        </article>
    </section>

    {{-- Main Content: Charts + Recent Requests --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start">
        {{-- Left Column: Charts --}}
        <div class="space-y-6 lg:col-span-8">`r`n            {{-- Monthly Requests Chart --}}
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
        <aside class="lg:col-span-4">
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

{{-- Modal: Employee on Leave (FOUC Prevention) --}}
<div id="cutiModal"
     class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/40 backdrop-blur-sm"
     style="display: none;"
     inert
     aria-hidden="true"
     role="dialog"
     aria-modal="true">

    <div id="cutiModalContent"
        class="bg-white rounded-2xl mx-4 w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col shadow-2xl transform transition-all duration-200 scale-95 opacity-0"
        role="document">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0 bg-gray-50">
            <div>
                <h2 class="text-sm font-semibold text-gray-800">Employees on Leave</h2>
                <span id="selectedDateLabel" class="block text-xs text-gray-500 mt-0.5"></span>
            </div>
            <div class="flex items-center gap-1.5">
                <button id="prevPage" type="button" aria-label="Previous page"
                    class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="text-xs fas fa-chevron-left"></i>
                </button>
                <button id="nextPage" type="button" aria-label="Next page"
                    class="flex items-center justify-center text-gray-500 transition rounded-lg w-7 h-7 hover:bg-gray-200 disabled:opacity-30 disabled:cursor-not-allowed">
                    <i class="text-xs fas fa-chevron-right"></i>
                </button>
                <button id="closeModalBtn" type="button" aria-label="Close modal"
                    class="flex items-center justify-center ml-1 text-gray-400 transition rounded-lg w-7 h-7 hover:bg-gray-200 hover:text-gray-600">
                    <i class="text-xs fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Page Info --}}
        <div class="px-6 py-2 bg-white border-b border-gray-100">
            <span id="currentPageInfo" class="text-xs text-gray-400">Page 1 of 1</span>
        </div>

        {{-- Modal Body --}}
        <div class="flex-1 p-5 overflow-y-auto bg-gray-50/50">
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

    // Calendar Logic
    const monthYear = document.getElementById('monthYear');
    const datesContainer = document.getElementById('dates');
    const prevBtn = document.getElementById('prev');
    const nextBtn = document.getElementById('next');

    // Modal Elements
    const modal = document.getElementById('cutiModal');
    const modalContent = document.getElementById('cutiModalContent');
    const cutiPages = document.getElementById('cutiPages');
    const selectedDateLabel = document.getElementById('selectedDateLabel');

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];

    const cutiPerTanggal = @json($cutiPerTanggal);

    function renderCalendar(month, year) {
        datesContainer.innerHTML = '';
        monthYear.textContent = monthNames[month] + ' ' + year;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Empty slots for days before month starts
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            datesContainer.appendChild(emptyCell);
        }

        // Date cells
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
            const hasCuti = !!cutiPerTanggal[dateStr];

            const button = document.createElement('button');
            button.type = 'button';
            button.onclick = () => showEvent(dateStr);
            button.setAttribute('aria-label', `Open leave data for ${dateStr}`);
            button.className = `relative flex items-center justify-center mx-auto my-0.5 w-8 h-8 rounded-lg text-xs cursor-pointer transition-all
                ${isToday
                    ? 'bg-blue-600 text-white font-bold shadow-sm'
                    : 'text-gray-700 hover:bg-gray-100'
                } ${hasCuti ? 'hover:bg-red-50' : ''}`;

            button.innerHTML = `
                ${day}
                ${hasCuti ? '<span class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-red-500 rounded-full"></span>' : ''}
            `;

            datesContainer.appendChild(button);
        }
    }

    // Modal Pagination
    let currentPage = 1;
    let totalPages = 1;
    const itemsPerPage = 6;

    function formatDisplayDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function showEvent(dateStr) {
        cutiPages.innerHTML = '';
        currentPage = 1;
        selectedDateLabel.textContent = formatDisplayDate(dateStr);

        if (cutiPerTanggal[dateStr]) {
            const employees = cutiPerTanggal[dateStr];
            totalPages = Math.ceil(employees.length / itemsPerPage);

            for (let page = 0; page < totalPages; page++) {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'w-full flex-shrink-0 grid grid-cols-2 gap-3';

                const start = page * itemsPerPage;
                const end = Math.min(start + itemsPerPage, employees.length);

                for (let i = start; i < end; i++) {
                    const c = employees[i];
                    const letter = c.employee ? c.employee[0].toUpperCase() : '?';

                    const card = document.createElement('div');
                    card.className = 'flex items-center gap-3 p-3 bg-white rounded-xl border border-gray-100 hover:shadow-sm transition-shadow';
                    card.innerHTML = `
                        ${c.url_profile
                            ? `<img src="${c.url_profile}" alt="${c.employee}" class="object-cover border border-gray-200 rounded-full w-9 h-9 shrink-0">`
                            : `<div class="flex items-center justify-center text-xs font-semibold text-blue-600 border border-blue-100 rounded-full bg-blue-50 w-9 h-9 shrink-0">${letter}</div>`
                        }
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">${c.employee ?? '-'}</p>
                            <p class="text-xs text-gray-400 truncate">${c.email ?? '-'}</p>
                        </div>
                    `;
                    pageDiv.appendChild(card);
                }

                cutiPages.appendChild(pageDiv);
            }
        } else {
            cutiPages.innerHTML = `
                <div class="w-full py-10 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full">
                        <i class="text-xl text-gray-300 fas fa-calendar-times"></i>
                    </div>
                    <p class="text-sm text-gray-500">No employees on leave on this date.</p>
                </div>
            `;
            totalPages = 1;
        }

        updatePaginationInfo();
        openModal();
    }

    function openModal() {
        modal.classList.remove('hidden');
        modal.removeAttribute('inert');
        modal.setAttribute('aria-hidden', 'false');

        // Force reflow for animation
        void modalContent.offsetWidth;

        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');

        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            document.getElementById('closeModalBtn')?.focus();
        }, 100);
    }

    function updatePaginationInfo() {
        document.getElementById('currentPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('nextPage').disabled = currentPage === totalPages;
        cutiPages.style.transform = `translateX(-${(currentPage - 1) * 100}%)`;
    }

    function closeModal() {
        modalContent.classList.add('scale-95', 'opacity-0');
        modalContent.classList.remove('scale-100', 'opacity-100');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('inert', '');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }, 200);
    }

    // Event Listeners
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);

    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; updatePaginationInfo(); }
    });

    document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < totalPages) { currentPage++; updatePaginationInfo(); }
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

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

    // Safety net for FOUC prevention
    if (modal) {
        modal.style.display = 'none';
    }

    // Initialize calendar
    renderCalendar(currentMonth, currentYear);
</script>
@endpush



