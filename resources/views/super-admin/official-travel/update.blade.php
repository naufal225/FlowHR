@extends('components.super-admin.layout.layout-super-admin')


@section('header', 'Edit Official Travel Request')
@section('subtitle', 'Modify your official travel request details')

@section('content')
<div class="max-w-3xl mx-auto">
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('super-admin.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('super-admin.official-travels.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Official Travel Requests</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('super-admin.official-travels.show', $officialTravel->id) }}"
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

        

        <form action="{{ route('super-admin.official-travels.update', $officialTravel->id) }}" method="POST"
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

                <div>
                    <label for="date_end" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                        End Date
                    </label>
                    <input type="date" id="date_end" name="date_end" class="form-input"
                        value="{{ $officialTravel->date_end->format('Y-m-d') }}" required min="{{ date('Y-m-d') }}"
                        onchange="calculateDays()">
                    <p class="mt-1 text-xs text-neutral-500">Current duration: {{ $officialTravel->total }} day{{
                        $officialTravel->total > 1 ? 's' : '' }}</p>
                </div>
            </div>

            Travel Duration Display
            <div id="duration-calculation" class="p-4 border border-green-200 rounded-lg bg-green-50">
                <div class="flex items-start">
                    <i class="fas fa-calculator text-green-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-green-800">Travel Duration</h4>
                        <p id="duration-total" class="text-sm font-bold text-green-800">Total Duration: {{
                            $officialTravel->total ?? '0' }} day{{ $officialTravel->total > 1 ? 's' : '' }}</p>
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
@endsection
