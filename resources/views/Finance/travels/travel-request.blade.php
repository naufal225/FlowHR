@extends('Finance.layouts.app')

@section('title', 'Request Official Travel')
@section('header', 'Request Official Travel')
@section('subtitle', 'Submit a new official travel request')

@section('content')
    <div class="max-w-3xl mx-auto">
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('finance.dashboard') }}" class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-neutral-400 mx-2"></i>
                        <a href="{{ route('finance.official-travels.index') }}" class="text-sm font-medium text-neutral-700 hover:text-primary-600">Official Travel Requests</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-neutral-400 mx-2"></i>
                        <span class="text-sm font-medium text-neutral-500">New Request</span>
                    </div>
                </li>
            </ol>
        </nav>

        @include('components.alert-errors')

        <div class="bg-white rounded-xl shadow-soft border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-bold text-neutral-900">Submit Official Travel Request</h2>
                <p class="text-neutral-600 text-sm">Fill in the details for your official travel request</p>
            </div>

            

            <form action="{{ route('finance.official-travels.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div>
                    <label for="customer" class="block text-sm font-semibold text-neutral-700 mb-2">
                        <i class="fas fa-users mr-2 text-primary-600"></i>
                        Customer
                    </label>
                    
                    <!-- Input tampilan -->
                    <input type="text" name="customer" id="customer" class="form-input"
                        value="{{ old('customer') }}" placeholder="e.g., John Doe" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="date_start" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>
                            Start Date
                        </label>
                        <input type="date" id="date_start" name="date_start" class="form-input"
                               value="{{ old('date_start') }}" required min="{{ date('Y-m-d') }}" onchange="calculateDays()">
                        <p class="text-xs text-neutral-500 mt-1">When does your travel start?</p>
                    </div>

                    <div>
                        <label for="date_end" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-calendar-alt mr-2 text-primary-600"></i>
                            End Date
                        </label>
                        <input type="date" id="date_end" name="date_end" class="form-input"
                               value="{{ old('date_end') }}" required min="{{ date('Y-m-d') }}" onchange="calculateDays()">
                        <p class="text-xs text-neutral-500 mt-1">When does your travel end?</p>
                    </div>
                </div>

                 Travel Duration Display 
                <div id="duration-calculation" class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-calculator text-green-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-semibold text-green-800 mb-1">Travel Duration</h4>
                            <p id="duration-total" class="text-sm font-bold text-green-800">Total Duration: 0 days</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-neutral-200">
                    <a href="{{ route('finance.official-travels.index') }}" class="px-6 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Request
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
            
            if (endDate < startDate) {
                totalP.textContent = `Total Duration: 0 days`;
                return;
            }

            if (startInput.value === "" || endInput.value === "") {
                totalP.textContent = `Total Duration: 0 days`;
                return;
            }

            const timeDiff = endDate.getTime() - startDate.getTime();
            const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            
            if (daysDiff > 0) {
                calculationDiv.style.display = 'block';
                totalP.textContent = `Total Duration: ${daysDiff} day${daysDiff > 1 ? 's' : ''}`;
            }
        }
    @endpush
@endsection
