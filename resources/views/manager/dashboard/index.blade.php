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

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Calendar Section -->
    @if($flags['cuti'])
    <div class="mb-8 bg-white border border-gray-200 rounded-xl shadow-soft">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-center text-gray-800">Employee Leave Calendar</h3>
        </div>
        <div class="p-4 sm:p-6">
            <div class="calendar-wrapper">
                <div id="calendar" class="overflow-hidden bg-white rounded-xl">
                    <div class="flex items-center justify-between mb-4 calendar-header">
                        <button id="prev" class="p-2 transition rounded-full hover:bg-gray-100">
                            <i class="text-gray-600 fas fa-chevron-left"></i>
                        </button>
                        <h2 id="monthYear" class="text-lg font-bold text-gray-800"></h2>
                        <button id="next" class="p-2 transition rounded-full hover:bg-gray-100">
                            <i class="text-gray-600 fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-7 pb-2 text-xs font-medium text-center text-gray-500 border-b border-gray-100 sm:text-sm">
                        <div>Min</div>
                        <div>Sen</div>
                        <div>Sel</div>
                        <div>Rab</div>
                        <div>Kam</div>
                        <div>Jum</div>
                        <div>Sab</div>
                    </div>

                    <div id="dates" class="grid grid-cols-7 gap-1 mt-2 text-center sm:gap-2"></div>
                </div>
            </div>
            <p class="mt-3 text-xs text-center text-red-500 sm:text-sm">
                *Klik tanggal bertanda merah untuk melihat siapa saja yang cuti
            </p>
        </div>
    </div>
    @endif

    <!-- Recent Requests Section -->
    <div class="mb-8 bg-white border border-gray-200 rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Recent Requests</h3>
            <p class="text-sm text-gray-500">Your latest submissions</p>
        </div>
        <div class="p-6">
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

<!-- Modal dengan Pagination (Sama seperti Super Admin) -->
<div id="cutiModal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black/50 backdrop-blur-sm">
    <div class="bg-white p-6 rounded-2xl w-[95%] max-w-2xl shadow-lg transform transition-all scale-95 opacity-0"
        id="cutiModalContent">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-800">List of Employee on Leave</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation dan Info -->
        <div class="flex items-center justify-between mb-4">
            <span id="currentPageInfo" class="text-sm text-gray-600">Page 1 of 1</span>
            <div class="flex gap-2">
                <button id="prevPage"
                    class="px-3 py-1 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="nextPage"
                    class="px-3 py-1 text-sm bg-gray-100 rounded-lg hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Container untuk daftar karyawan dengan grid horizontal -->
        <div id="cutiContainer" class="overflow-hidden">
            <div id="cutiPages" class="flex transition-transform duration-300 ease-in-out">
                <!-- Halaman akan diisi oleh JavaScript -->
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

    const monthYear = document.getElementById("monthYear");
    const datesContainer = document.getElementById("dates");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    const monthNames = [
        "Januari","Februari","Maret","April","Mei","Juni",
        "Juli","Agustus","September","Oktober","November","Desember"
    ];

    const cutiPerTanggal = @json($cutiPerTanggal);

    function renderCalendar(month, year) {
        datesContainer.innerHTML = "";
        monthYear.textContent = monthNames[month] + " " + year;

        let firstDay = new Date(year, month, 1).getDay();
        let daysInMonth = new Date(year, month + 1, 0).getDate();

        // Kosongkan slot awal minggu
        for (let i = 0; i < firstDay; i++) {
            datesContainer.innerHTML += `<div></div>`;
        }

        // Isi tanggal
        for (let day = 1; day <= daysInMonth; day++) {
            let dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            let isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());

            let classes = `
                relative aspect-square flex items-center justify-center
                rounded-lg cursor-pointer text-sm sm:text-base
                hover:bg-red-100 transition
                ${isToday ? 'bg-gray-200 font-bold' : ''}
            `;

            let content = `<span>${day}</span>`;

            if (cutiPerTanggal[dateStr]) {
                content += `
                    <span class="absolute top-1 right-1 w-2 h-2 sm:w-2.5 sm:h-2.5 bg-red-500 rounded-full"></span>
                `;
            }

            datesContainer.innerHTML += `
                <div class="${classes}" onclick="showEvent('${dateStr}')">
                    ${content}
                </div>
            `;
        }
    }

    // Variabel global untuk pagination
    let currentPage = 1;
    let totalPages = 1;
    const itemsPerPage = 6; // Jumlah item per halaman

    function showEvent(dateStr) {
        const modal = document.getElementById('cutiModal');
        const modalContent = document.getElementById('cutiModalContent');
        const cutiPages = document.getElementById('cutiPages');
        const currentPageInfo = document.getElementById('currentPageInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');

        cutiPages.innerHTML = "";
        currentPage = 1;

        if (cutiPerTanggal[dateStr]) {
            const employees = cutiPerTanggal[dateStr];
            totalPages = Math.ceil(employees.length / itemsPerPage);

            // Buat halaman-halaman
            for (let page = 0; page < totalPages; page++) {
                const pageContainer = document.createElement('div');
                pageContainer.className = 'w-full flex-shrink-0 grid grid-cols-2 gap-3';

                const startIndex = page * itemsPerPage;
                const endIndex = Math.min(startIndex + itemsPerPage, employees.length);

                for (let i = startIndex; i < endIndex; i++) {
                    const cuti = employees[i];
                    let firstLetter = cuti.employee ? cuti.employee.substring(0, 1).toUpperCase() : "?";

                    pageContainer.innerHTML += `
                        <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50">
                            ${cuti.url_profile ? `
                                <img class="flex items-center justify-center object-cover w-10 h-10 rounded-full"
                                    src="${cuti.url_profile}" alt="${cuti.employee}">
                            ` : `
                                <span class="flex items-center justify-center w-10 h-10 text-xs text-blue-600 bg-blue-100 rounded-full">
                                    ${firstLetter}
                                </span>
                            `}
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 truncate">${cuti.employee ?? '-'}</p>
                                <p class="text-xs text-gray-500 truncate">${cuti.email ?? '-'}</p>
                            </div>
                        </div>
                    `;
                }

                cutiPages.appendChild(pageContainer);
            }

            // Update info halaman dan tombol
            updatePaginationInfo();

        } else {
            cutiPages.innerHTML = `
                <div class="w-full py-8 text-center text-gray-600">
                    Tidak ada karyawan yang cuti pada tanggal ini
                </div>
            `;
            currentPageInfo.textContent = "Page 1 of 1";
            prevPageBtn.disabled = true;
            nextPageBtn.disabled = true;
        }

        modal.classList.remove("hidden");

        // Animasi muncul
        setTimeout(() => {
            modalContent.classList.remove("scale-95", "opacity-0");
            modalContent.classList.add("scale-100", "opacity-100");
        }, 10);
    }

    function updatePaginationInfo() {
        const currentPageInfo = document.getElementById('currentPageInfo');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const cutiPages = document.getElementById('cutiPages');

        currentPageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages;

        // Geser halaman
        cutiPages.style.transform = `translateX(-${(currentPage - 1) * 100}%)`;
    }

    // Event listeners untuk pagination
    document.getElementById('prevPage').addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updatePaginationInfo();
        }
    });

    document.getElementById('nextPage').addEventListener('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            updatePaginationInfo();
        }
    });

    function closeModal() {
        const modal = document.getElementById('cutiModal');
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
