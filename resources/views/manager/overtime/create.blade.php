@extends('components.manager.layout.layout-manager')

@section('header', 'Request Overtime')
@section('subtitle', 'Submit a new overtime request')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('manager.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('manager.overtimes.index') }}"
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

        

        <form action="{{ route('manager.overtimes.store') }}" method="POST" class="p-6 space-y-6">
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
                    <input type="datetime-local" name="date_start"
                        value="{{ old('date_start', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                    <p class="mt-1 text-xs text-neutral-500">When did you start working overtime?</p>
                </div>

                <div>
                    <label for="date_end" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        End Date & Time
                    </label>
                    <input type="datetime-local" name="date_end"
                        value="{{ old('date_end', now()->format('Y-m-d\TH:i')) }}" class="form-input" required>
                    <p class="mt-1 text-xs text-neutral-500">When did you finish working overtime?</p>
                </div>
            </div>

            <!-- Overtime Calculation Display -->
            <div id="overtime-calculation" class="p-4 border border-green-200 rounded-lg bg-green-50"
                style="display: none;">
                <div class="flex items-start">
                    <i class="fas fa-calculator text-green-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-green-800">Overtime Calculation</h4>
                        <p id="overtime-details" class="text-xs text-green-700"></p>
                        <p id="overtime-total" class="mt-1 text-sm font-bold text-green-800"></p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ route('manager.overtimes.index') }}"
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
@endsection
