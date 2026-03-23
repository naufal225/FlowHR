@extends('Finance.layouts.app')

@section('title', 'Edit Official Travel Request')
@section('header', 'Edit Official Travel Request')
@section('subtitle', 'Modify your official travel request details')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('finance.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('finance.official-travels.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Official Travel Requests</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('finance.official-travels.show', $officialTravel->id) }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Request #TY{{
                        $officialTravel->id }}</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Edit</span>
                </div>
            </li>
        </ol>
    </nav>

    @include('components.alert-errors')

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Edit Official Travel Request #TY{{ $officialTravel->id }}
            </h2>
            <p class="text-sm text-neutral-600">Update your official travel request information</p>
        </div>

        

        <form action="{{ route('finance.official-travels.update', $officialTravel->id) }}" method="POST"
            class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="customer" class="block mb-2 text-sm font-semibold text-neutral-700">
                    <i class="mr-2 fas fa-users text-primary-600"></i>
                    Customer
                </label>

                <!-- Input tampilan -->
                <input type="text" name="customer" id="customer" class="form-input"
                    value="{{ old('customer', $officialTravel->customer) }}" placeholder="e.g., John Doe" required>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="date_start" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        Start Date
                    </label>
                    <input type="date" id="date_start" name="date_start" class="form-input"
                        value="{{ $officialTravel->date_start->format('Y-m-d') }}" required min="{{ date('Y-m-d') }}"
                        onchange="calculateDays()">
                </div>

                @php
                    $start = Carbon\Carbon::parse($officialTravel->date_start);
                    $end = Carbon\Carbon::parse($officialTravel->date_end);
                    $totalDays = $start->startOfDay()->diffInDays($end->startOfDay()) + 1;
                @endphp

                <div>                  
                    <label for="date_end" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        End Date
                    </label>
                    <input type="date" id="date_end" name="date_end" class="form-input"
                        value="{{ $officialTravel->date_end->format('Y-m-d') }}" required min="{{ date('Y-m-d') }}"
                        onchange="calculateDays()">
                    <p class="mt-1 text-xs text-neutral-500">Current duration: {{ $totalDays }} day{{
                        $totalDays > 1 ? 's' : '' }}</p>
                </div>
            </div>

            Travel Duration Display
            <div id="duration-calculation" class="p-4 border border-green-200 rounded-lg bg-green-50">
                <div class="flex items-start">
                    <i class="fas fa-calculator text-green-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-green-800">Travel Duration</h4>
                        <p id="duration-total" class="text-sm font-bold text-green-800">Total Duration: {{
                            $totalDays ?? '0' }} day{{ $totalDays > 1 ? 's' : '' }}</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ url()->previous() }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-save"></i>
                    Update Request
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
function calculateDays() {
const startInput = document.getElementById('date_start');
const endInput = document.getElementById('date_end');
const calculationDiv = document.getElementById('duration-calculation');
const totalP = document.getElementById('duration-total');

const startDate = new Date(startInput.value);
const endDate = new Date(endInput.value);

if (endDate < startDate) { totalP.textContent=`Total Duration: 0 days`; return; } if (startInput.value==="" ||
    endInput.value==="" ) { totalP.textContent=`Total Duration: 0 days`; return; } const timeDiff=endDate.getTime() -
    startDate.getTime(); const daysDiff=Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; if (daysDiff> 0) {
    calculationDiv.style.display = 'block';
    totalP.textContent = `Total Duration: ${daysDiff} day${daysDiff > 1 ? 's' : ''}`;
    }
    }
    @endpush
    @endsection
