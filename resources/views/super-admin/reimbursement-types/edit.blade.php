@extends('components.super-admin.layout.layout-super-admin')
@section('header', 'Edit Reimbursement Type')
@section('subtitle', 'Perbarui informasi jenis reimbursement')
@section('content')

<main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Reimbursement Type</h1>
                <p class="mt-2 text-sm text-gray-600">Perbarui informasi jenis reimbursement</p>
            </div>
            <!-- Back Button -->
            <a href="{{ route('super-admin.reimbursement-types.index') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="max-w-2xl mx-auto">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Reimbursement Type Information</h3>
                <p class="mt-1 text-sm text-gray-600">Silakan perbarui nama jenis reimbursement di bawah ini</p>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <!-- Success Message -->
                @if(session('success'))
                <div class="flex items-center p-4 mb-6 border border-green-200 bg-green-50 rounded-xl">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                <!-- Error Messages -->
                @if($errors->any())
                <div class="flex items-start p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-red-800">Please correct the following errors:</h4>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <!-- Form -->
                <form action="{{ route('super-admin.reimbursement-types.update', $reimbursementType->id) }}" method="POST" class="space-y-6" id="typeForm">
                    @csrf
                    @method('PUT')

                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                            Reimbursement Type Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="name" name="name" value="{{ old('name', $reimbursementType->name) }}"
                                class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="Enter reimbursement type name" required>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">Masukkan nama jenis reimbursement (contoh: Transportasi, Makan, Akomodasi)</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end pt-6 space-x-4 border-t border-gray-200">
                        <!-- Cancel Button -->
                        <a href="{{ route('super-admin.reimbursement-types.index') }}"
                            class="px-6 py-3 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </a>
                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn"
                            class="px-6 py-3 text-sm font-medium text-white transition-all rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="submitBtnText">Update Type</span>
                            <svg id="submitBtnSpinner" class="hidden w-4 h-4 ml-2 -mr-1 text-white animate-spin"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

@endsection

@push('scripts')
<script>
    document.getElementById('typeForm').addEventListener('submit', function() {
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        const submitBtnSpinner = document.getElementById('submitBtnSpinner');

        submitBtn.disabled = true;
        submitBtnText.textContent = 'Updating...';
        submitBtnSpinner.classList.remove('hidden');
    });
</script>
@endpush
