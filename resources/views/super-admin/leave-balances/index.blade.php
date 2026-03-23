@extends('components.super-admin.layout.layout-super-admin')
@section('header', 'Leave Balances')
@section('subtitle', 'View all employees leave balances')

@section('content')
<main class="relative z-10 flex-1 p-0 space-y-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <!-- Header with Title and Export Button -->
    <div class="flex flex-col justify-between p-6 md:flex-row md:items-start">
        <div class="flex-1 mb-4 md:mb-0">
            <h1 class="text-2xl font-bold text-neutral-900">Leave Balances</h1>
            <p class="text-neutral-600">View all employees leave balances and usage</p>
        </div>
        <div class="flex-shrink-0">
            <button type="button" onclick="exportLeaveBalances()"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md md:w-auto hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <i class="mr-2 fas fa-file-pdf"></i>
                Export PDF
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
        <form method="GET" action="{{ route('super-admin.leave-balances.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-4">

            <!-- Division -->
            <div>
                <label class="block mb-2 text-sm font-medium text-neutral-700">Division</label>
                <select name="division_id"
                    class="w-full px-3 py-2 border rounded-md border-neutral-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">All Divisions</option>
                    @foreach($divisions as $division)
                    <option value="{{ $division->id }}" {{ request('division_id')==$division->id ? 'selected' : '' }}>
                        {{ $division->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Year -->
            <div>
                <label class="block mb-2 text-sm font-medium text-neutral-700">Year</label>
                <input type="text" name="year" id="yearPicker" value="{{ request('year', now()->year) }}"
                    class="w-full px-3 py-2 border rounded-md border-neutral-300 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    placeholder="Select year">
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit"
                    class="w-full px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="mr-2 fas fa-filter"></i> Filter
                </button>
            </div>

            <!-- Reset -->
            <div class="flex items-end">
                <a href="{{ route('super-admin.leave-balances.index') }}"
                    class="w-full px-4 py-2 text-sm font-medium text-center bg-white border rounded-md text-neutral-700 border-neutral-300 hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    <i class="mr-2 fas fa-refresh"></i> Reset
                </a>
            </div>
        </form>

    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 text-blue-600 bg-blue-100 rounded-full">
                    <i class="text-xl fas fa-users"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-neutral-500">Total Employees</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ count($leaveBalances) }}</p>
                </div>
            </div>
        </div>
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 text-green-600 bg-green-100 rounded-full">
                    <i class="text-xl fas fa-calendar-check"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-neutral-500">Average Used Days</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ $avgUsed }}</p>
                </div>
            </div>
        </div>
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-amber-100 text-amber-600">
                    <i class="text-xl fas fa-calendar-minus"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-neutral-500">Average Remaining Days</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ $avgRemain }}</p>
                </div>
            </div>
        </div>
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 text-indigo-600 bg-indigo-100 rounded-full">
                    <i class="text-xl fas fa-calendar"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-neutral-500">Total Annual Leave</p>
                    <p class="text-2xl font-bold text-neutral-900">{{ env('CUTI_TAHUNAN', 20) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balances Table -->
    <div class="overflow-hidden bg-white border border-neutral-200 rounded-xl shadow-soft">
        <div class="px-6 py-4 border-b border-neutral-200 bg-neutral-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-neutral-900">Employee Leave Balances</h3>
                <span class="px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                    {{ count($leaveBalances) }} employees
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200">
                <thead class="bg-neutral-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Employee</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Division</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Annual Leave</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Used Days</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                            Remaining Days</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-neutral-200">
                    @forelse($leaveBalances as $balance)
                    <tr class="transition-colors duration-150 hover:bg-neutral-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 w-10 h-10">
                                    @if($balance['employee']->url_profile)
                                    <img class="object-cover w-10 h-10 rounded-full"
                                        src="{{ $balance['employee']->url_profile }}"
                                        alt="{{ $balance['employee']->name }}">
                                    @else
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-neutral-200">
                                        <span class="text-sm font-medium text-neutral-700">
                                            {{ strtoupper(substr($balance['employee']->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-neutral-900">{{ $balance['employee']->name }}
                                    </div>
                                    <div class="text-sm text-neutral-500">{{ $balance['employee']->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-neutral-900">
                                {{ $balance['employee']->division?->name ?? 'No Division' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-neutral-900">{{ $balance['total_cuti'] }} days</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-neutral-900">{{ $balance['used_cuti'] }} days</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($balance['sisa_cuti'] <= 0) <span
                                class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                {{ $balance['sisa_cuti'] }} days
                                </span>
                                @elseif($balance['sisa_cuti'] <= 5) <span
                                    class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                    {{ $balance['sisa_cuti'] }} days
                                    </span>
                                    @else
                                    <span
                                        class="inline-flex px-2.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                        {{ $balance['sisa_cuti'] }} days
                                    </span>
                                    @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-neutral-400">
                                <i class="mb-4 text-4xl fas fa-users"></i>
                                <p class="text-lg font-medium">No employees found</p>
                                <p class="text-sm">Try adjusting your filters</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.0/css/pikaday.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pikaday/1.8.0/pikaday.min.js"></script>
<script>
    // Initialize year picker
    const yearPicker = new Pikaday({
        field: document.getElementById('yearPicker'),
        format: 'YYYY',
        yearRange: [new Date().getFullYear() - 10, new Date().getFullYear() + 10],
        showMonthAfterYear: true,
        maxDate: new Date(new Date().getFullYear() + 10, 11, 31),
        minDate: new Date(new Date().getFullYear() - 10, 0, 1),
        onSelect: function() {
            // Format the selected date as just the year
            const date = this.getDate();
            if (date) {
                document.getElementById('yearPicker').value = date.getFullYear();
            }
        }
    });

    // Set the picker to only show year
    yearPicker._o.onDraw = function() {
        const yearSelect = this._el.querySelector('.pika-select-year');
        if (yearSelect) {
            yearSelect.focus();
            // Hide month navigation
            const months = this._el.querySelectorAll('.pika-table');
            if (months.length) {
                months[0].style.display = 'none';
            }
        }
    };

    function exportLeaveBalances() {
        // Get current form data
        const form = document.querySelector('form');
        const formData = new FormData(form);

        // Build query string
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }

        // Redirect to export route with same parameters
        const exportUrl = "{{ route('super-admin.leave-balances.export') }}?" + params.toString();
        window.location.href = exportUrl;
    }
</script>
@endpush
