@extends('components.manager.layout.layout-manager')

@section('header', 'Request Reimbursement')
@section('subtitle', 'Submit a new reimbursement claim')

@section('content')
    <div class="max-w-3xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('manager.dashboard') }}" class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                        <i class="mr-2 fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                        <a href="{{ route('manager.reimbursements.index') }}" class="text-sm font-medium text-neutral-700 hover:text-primary-600">Reimbursement Requests</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                        <span class="text-sm font-medium text-neutral-500">New Claim</span>
                    </div>
                </li>
            </ol>
        </nav>

        @include('components.alert-errors')

        <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="px-6 py-4 border-b border-neutral-200">
                <h2 class="text-lg font-bold text-neutral-900">Submit Reimbursement Claim</h2>
                <p class="text-sm text-neutral-600">Fill in the details for your reimbursement request</p>
            </div>

            

            <form action="{{ route('manager.reimbursements.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                <div>
                    <label for="customer" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-users text-primary-600"></i>
                        Perusahaan Customer
                    </label>

                    <!-- Input tampilan -->
                    <input type="text" name="customer" id="customer" class="form-input"
                        value="{{ old('customer') }}" placeholder="e.g., John Doe" required>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="total_display" class="block mb-2 text-sm font-semibold text-neutral-700">
                            <i class="mr-2 fas fa-dollar-sign text-primary-600"></i>
                            Total Amount (Rp)
                        </label>

                        <!-- Input tampilan -->
                        <input type="text" id="total_display" class="form-input"
                            value="{{ old('total') }}" placeholder="e.g., 150000" required>

                        <!-- Input hidden untuk nilai asli -->
                        <input type="hidden" id="total" name="total" value="{{ old('total') }}">
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            const displayInput = document.getElementById("total_display");
                            const hiddenInput = document.getElementById("total");

                            function formatRupiah(angka) {
                                return angka.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }

                            displayInput.addEventListener("input", function (e) {
                                // ambil angka asli (tanpa titik)
                                let raw = this.value.replace(/\D/g, "");
                                // simpan ke hidden input
                                hiddenInput.value = raw;
                                // tampilkan kembali dalam format rupiah
                                this.value = formatRupiah(raw);
                            });

                            // kalau ada value lama dari old()
                            if (hiddenInput.value) {
                                displayInput.value = formatRupiah(hiddenInput.value);
                            }
                        });
                    </script>

                    <div>
                        <label for="date" class="block mb-2 text-sm font-semibold text-neutral-700">
                            <i class="mr-2 fas fa-calendar-day text-primary-600"></i>
                            Date of Expense
                        </label>
                        <input type="date" id="date" name="date" class="form-input"
                               value="{{ old('date') }}" required max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div>
                    <label for="invoice_path" class="block mb-2 text-sm font-semibold text-neutral-700"> {{-- Changed from attachment --}}
                        <i class="mr-2 fas fa-paperclip text-primary-600"></i>
                        Invoice
                    </label>
                    <input type="file" id="invoice_path" name="invoice_path" class="form-input-file"> {{-- Changed from attachment --}}
                    <p class="mt-1 text-xs text-neutral-500">Accepted formats: JPG, PNG, PDF (Max 2MB). Please attach receipts or invoices.</p>
                </div>

                <!-- Reimbursement Policy Reminder -->
                <div class="p-4 border rounded-lg bg-primary-50 border-primary-200">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-primary-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="mb-2 text-sm font-semibold text-primary-800">Reimbursement Policy Reminder</h4>
                            <ul class="space-y-1 text-xs text-primary-700">
                                <li>• All claims must be submitted within 30 days of the expense date.</li>
                                <li>• Original receipts or digital copies are required for all claims.</li>
                                <li>• Claims over Rp 1.000.000 require additional manager approval.</li>
                                <li>• Personal expenses are not eligible for reimbursement.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                    <a href="{{ route(name: 'manager.reimbursements.index') }}" class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                        <i class="mr-2 fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="mr-2 fas fa-paper-plane"></i>
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
