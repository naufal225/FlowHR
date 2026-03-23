@extends('Finance.layouts.app')

@section('title', 'Request Reimbursement')
@section('header', 'Request Reimbursement')
@section('subtitle', 'Submit a new reimbursement claim')

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Breadcrumb -->
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
                        <a href="{{ route('finance.reimbursements.index') }}" class="text-sm font-medium text-neutral-700 hover:text-primary-600">Reimbursement Requests</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-neutral-400 mx-2"></i>
                        <span class="text-sm font-medium text-neutral-500">New Claim</span>
                    </div>
                </li>
            </ol>
        </nav>

        @include('components.alert-errors')

        <div class="bg-white rounded-xl shadow-soft border border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-bold text-neutral-900">Submit Reimbursement Claim</h2>
                <p class="text-neutral-600 text-sm">Fill in the details for your reimbursement request</p>
            </div>
            
            
            
            <form action="{{ route('finance.reimbursements.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="customer" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-users mr-2 text-primary-600"></i>
                            Perusahaan Customer
                        </label>
                        
                        <!-- Input tampilan -->
                        <input type="text" name="customer" id="customer" class="form-input"
                            value="{{ old('customer') }}" placeholder="e.g., John Doe" required>
                    </div>

                    <div>
                        <label for="reimbursement_type_id" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-list mr-2 text-primary-600"></i>
                            Type Reimbursement
                        </label>
                        <select name="reimbursement_type_id" id="reimbursement_type_id" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" {{ old('reimbursement_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="total_display" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-dollar-sign mr-2 text-primary-600"></i>
                            Total Amount (Rp)
                        </label>

                        <!-- Input tampilan -->
                        <input type="text" id="total_display" class="form-input"
                            value="{{ old('total') }}" placeholder="e.g., 150000" required>

                        <!-- Input hidden untuk nilai asli -->
                        <input type="hidden" id="total" name="total" value="{{ old('total') }}">
                    </div>
                    
                    <div>
                        <label for="date" class="block text-sm font-semibold text-neutral-700 mb-2">
                            <i class="fas fa-calendar-day mr-2 text-primary-600"></i>
                            Date of Expense
                        </label>
                        <input type="date" id="date" name="date" class="form-input" 
                               value="{{ old('date') }}" required max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div>
                    <label for="invoice_path" class="block text-sm font-semibold text-neutral-700 mb-2"> {{-- Changed from attachment --}}
                        <i class="fas fa-paperclip mr-2 text-primary-600"></i>
                        Invoice
                    </label>
                    <input type="file" id="invoice_path" name="invoice_path" class="form-input-file"> {{-- Changed from attachment --}}
                    <p class="text-xs text-neutral-500 mt-1">Accepted formats: JPG, PNG, PDF (Max 2MB). Please attach receipts or invoices.</p>
                </div>

                <!-- Reimbursement Policy Reminder -->
                <div class="bg-primary-50 border border-primary-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-primary-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-semibold text-primary-800 mb-2">Reimbursement Policy Reminder</h4>
                            <ul class="text-xs text-primary-700 space-y-1">
                                <li>• All claims must be submitted within 30 days of the expense date.</li>
                                <li>• Original receipts or digital copies are required for all claims.</li>
                                <li>• Personal expenses are not eligible for reimbursement.</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 pt-6 border-t border-neutral-200">
                    <a href="{{ route('finance.reimbursements.index') }}" class="px-6 py-2 text-sm font-medium text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    const displayInput = document.getElementById("total_display");
    const hiddenInput = document.getElementById("total");

    function formatRupiah(angka) {
        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    displayInput.addEventListener("input", function (e) {
        let raw = this.value.replace(/\D/g, "");
        hiddenInput.value = raw;
        this.value = formatRupiah(raw);
    });

    if (hiddenInput.value) {
        displayInput.value = formatRupiah(hiddenInput.value);
    }
@endpush
