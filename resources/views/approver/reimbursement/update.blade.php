@extends('components.approver.layout.layout-approver')

@section('header', 'Edit Reimbursement')
@section('subtitle', 'Modify your reimbursement claim details')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('approver.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('approver.reimbursements.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Reimbursement Requests</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('approver.reimbursements.show', $reimbursement->id) }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Claim #RY{{
                        $reimbursement->id }}</a>
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
            <h2 class="text-lg font-bold text-neutral-900">Edit Reimbursement Claim #RY{{ $reimbursement->id }}</h2>
            <p class="text-sm text-neutral-600">Update your reimbursement claim information</p>
        </div>

        

        <form action="{{ route('approver.reimbursements.updateSelf', $reimbursement->id) }}" method="POST"
            enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="customer" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-users text-primary-600"></i>
                        Perusahaan Customer
                    </label>

                    <!-- Input tampilan -->
                    <input type="text" name="customer" id="customer" class="form-input"
                        value="{{ old('customer', $reimbursement->customer) }}" placeholder="e.g., John Doe" required>
                </div>

                <div>
                    <label for="reimbursement_type_id" class="block mb-2 text-sm font-semibold text-neutral-700">
                        <i class="mr-2 fas fa-list text-primary-600"></i>
                        Type Reimbursement
                    </label>
                    <select name="reimbursement_type_id" id="reimbursement_type_id" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        @foreach($types as $type)
                        <option value="{{ $type->id }}" {{ old('reimbursement_type_id', $reimbursement->
                            reimbursement_type_id) == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="total" class="block mb-2 text-sm font-semibold text-neutral-700"> {{-- Changed from
                        amount --}}
                        <i class="mr-2 fas fa-dollar-sign text-primary-600"></i>
                        Total Amount (Rp) {{-- Changed from Amount --}}
                    </label>
                    <input type="text" id="total_display" class="form-input" value="{{ old('total', $reimbursement->total) }}"
                        placeholder="e.g., 150000" required>

                    <!-- Input hidden untuk nilai asli -->
                    <input type="hidden" id="total" name="total" value="{{ old('total', $reimbursement->total) }}">
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
                        value="{{ old('date', $reimbursement->date->format('Y-m-d')) }}" required
                        max="{{ date('Y-m-d') }}">
                </div>
            </div>

            <div>
                <label for="invoice_path" class="block mb-2 text-sm font-semibold text-neutral-700"> {{-- Changed from
                    attachment --}}
                    <i class="mr-2 fas fa-paperclip text-primary-600"></i>
                    Invoice (Optional) {{-- Changed from Attachment --}}
                </label>
                <input type="file" id="invoice_path" name="invoice_path" class="form-input-file"> {{-- Changed from
                attachment --}}
                <p class="mt-1 text-xs text-neutral-500">Accepted formats: JPG, PNG, PDF (Max 2MB). Leave blank to keep
                    current.</p>
                @if($reimbursement->invoice_path)
                <div class="flex items-center mt-2 text-sm text-neutral-600">
                    <i class="mr-2 fas fa-file-alt"></i>
                    Current: <a href="{{ Storage::url($reimbursement->invoice_path) }}" target="_blank"
                        class="ml-1 text-primary-600 hover:underline">View Invoice</a> {{-- Changed from attachment --}}
                    <label class="flex items-center ml-4">
                        <input type="checkbox" name="remove_invoice_path" value="1"
                            class="rounded shadow-sm border-neutral-300 text-error-600 focus:ring-error-500"> {{--
                        Changed from remove_attachment --}}
                        <span class="ml-2 text-error-600">Remove current invoice</span> {{-- Changed from attachment
                        --}}
                    </label>
                </div>
                @endif
            </div>

            <!-- Warning Notice -->
            <div class="p-4 border rounded-lg bg-warning-50 border-warning-200">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-warning-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="mb-1 text-sm font-semibold text-warning-800">Important Notice</h4>
                        <p class="text-xs text-warning-700">
                            Editing this request will reset its status to pending and require re-approval from your
                            Approver 1.
                        </p>
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
                    <i class="mr-2 fas fa-save"></i>
                    Update Claim
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
