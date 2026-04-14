@extends('components.admin.layout.layout-admin')

@section('header', 'Request Overtime')
@section('subtitle', 'Submit a new overtime request')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('admin.overtimes.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Overtime Requests</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">New Request</span>
                </div>
            </li>
        </ol>
    </nav>


    @include('components.alert-errors')

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Submit Overtime Request</h2>
            <p class="text-sm text-neutral-600">Fill in the details for your overtime request</p>
        </div>


        <form action="{{ route('admin.overtimes.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <!-- Work Hours Info -->
            <div class="p-4 border border-blue-200 rounded-lg bg-blue-50">
                <div class="flex items-start">
                    <i class="fas fa-clock text-blue-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-blue-800">Normal Work Hours</h4>
                        <p class="text-xs text-blue-700">
                            Regular working hours: 09:00 - 17:00 (8 hours)<br>
                            Overtime is calculated for work outside these hours.
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <label for="customer" class="block mb-2 text-sm font-semibold text-neutral-700">
                    <i class="mr-2 fas fa-users text-primary-600"></i>
                    Customer
                </label>

                <!-- Input tampilan -->
                <input type="text" name="customer" id="customer" class="form-input" value="{{ old('customer') }}"
                    placeholder="e.g., John Doe" required>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="date_start" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        Start Date & Time
                    </label>
                    <input type="datetime-local" id="date_start" name="date_start"
                        value="{{ old('date_start', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                    <p class="mt-1 text-xs text-neutral-500">When did you start working overtime?</p>
                </div>

                <div>
                    <label for="date_end" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        End Date & Time
                    </label>
                    <input type="datetime-local" id="date_end" name="date_end"
                        value="{{ old('date_end', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                    <p class="mt-1 text-xs text-neutral-500">When did you finish working overtime?</p>
                </div>
            </div>
            <!-- Overtime Duration Display -->
            <div id="duration-calculation" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-calculator text-green-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="text-sm font-semibold text-green-800 mb-1">Overtime Duration</h4>
                        <p id="duration-total" class="text-sm font-bold text-green-800">Total Duration: 0 hours</p>
                    </div>
                </div>
            </div>
<div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{url()->previous() }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-paper-plane"></i>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const startInput = document.getElementById('date_start');
    const endInput = document.getElementById('date_end');
    const durationTotal = document.getElementById('duration-total');

    if (!startInput || !endInput || !durationTotal) {
        return;
    }

    function formatDuration(totalMinutes) {
        if (totalMinutes <= 0) {
            return '0 hours';
        }

        const days = Math.floor(totalMinutes / 1440);
        const hours = Math.floor((totalMinutes % 1440) / 60);
        const minutes = totalMinutes % 60;
        const parts = [];

        if (days > 0) {
            parts.push(`${days} day${days !== 1 ? 's' : ''}`);
        }

        if (hours > 0) {
            parts.push(`${hours} hour${hours !== 1 ? 's' : ''}`);
        }

        if (minutes > 0) {
            parts.push(`${minutes} minute${minutes !== 1 ? 's' : ''}`);
        }

        return parts.length > 0 ? parts.join(' ') : '0 hours';
    }

    function parseDateTime(value) {
        if (!value) {
            return null;
        }

        const dt = new Date(value);
        return Number.isNaN(dt.getTime()) ? null : dt;
    }

    function calculateDuration() {
        const startDate = parseDateTime(startInput.value);
        const endDate = parseDateTime(endInput.value);

        if (!startDate || !endDate || endDate <= startDate) {
            durationTotal.textContent = 'Total Duration: 0 hours';
            return;
        }

        const totalMinutes = Math.floor((endDate.getTime() - startDate.getTime()) / 60000);
        durationTotal.textContent = `Total Duration: ${formatDuration(totalMinutes)}`;
    }

    startInput.addEventListener('change', calculateDuration);
    startInput.addEventListener('input', calculateDuration);
    endInput.addEventListener('change', calculateDuration);
    endInput.addEventListener('input', calculateDuration);

    calculateDuration();
});
</script>
@endpush
@endsection
