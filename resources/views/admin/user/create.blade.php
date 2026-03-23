@extends('components.admin.layout.layout-admin')

@section('header', 'Add User')
@section('subtitle', 'Add User data')

@section('content')
<!-- Add User Content -->
<main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">

    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Add New User</h1>
                <p class="mt-2 text-sm text-gray-600">Create a new user record in the system</p>
            </div>

            <!-- Back Button -->
            <a href="{{ route('admin.users.index') }}"
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
                <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
                <p class="mt-1 text-sm text-gray-600">Please fill in the user details below</p>
            </div>

            <!-- Form Content -->
            <div class="p-6">
                <!-- Success & Error Messages -->
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

                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6" id="userForm">
                    @csrf

                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="Enter user full name" required>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">Enter the user's full name as it appears on official documents</p>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-700">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('email') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="Enter user email address" required>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                        </div>
                        @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">This email will be used for system login and notifications</p>
                    </div>

                    <!-- ROLES: Ganti jadi checkbox multiple -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">
                            Select Roles <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 gap-3 p-4 border border-gray-300 rounded-lg bg-gray-50">
                            @foreach($roles as $role)
                                @php
                                    $roleValue = $role->value;
                                    $label = $roleLabels[$roleValue] ?? ucfirst(str_replace('_', ' ', $roleValue));
                                @endphp
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value="{{ $roleValue }}"
                                        id="role_{{ $loop->index }}"
                                        {{ in_array($roleValue, old('roles', [])) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                    >
                                    <label for="role_{{ $loop->index }}" class="ml-3 text-sm font-medium text-gray-700">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('roles.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">User can have multiple roles. At least one role is required.</p>
                    </div>

                    <!-- Division -->
                    <div>
                        <label for="division_id" class="block mb-2 text-sm font-medium text-gray-700">
                            Select Division
                        </label>
                        <div class="relative">
                            <select id="division_id" name="division_id"
                                class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors @error('division_id') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                <option value="">Select a division (optional)</option>
                                @foreach($divisions as $division)
                                    <option value="{{ $division->id }}" {{ old('division_id') == $division->id ? 'selected' : '' }}>
                                        {{ $division->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 left-0 z-10 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>
                        @error('division_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-500">Required only if assigning <strong>Approver</strong> role.</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end pt-6 space-x-4 border-t border-gray-200">
                        <a href="{{ route('admin.users.index') }}"
                            class="px-6 py-3 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </a>
                        <button type="submit" id="submitBtn"
                            class="px-6 py-3 text-sm font-medium text-white transition-all rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="submitBtnText">Add User</span>
                            <svg id="submitBtnSpinner" class="hidden w-4 h-4 ml-2 -mr-1 text-white animate-spin"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
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
    document.getElementById('userForm').addEventListener('submit', function() {
        const submitBtn = document.getElementById('submitBtn');
        const submitBtnText = document.getElementById('submitBtnText');
        const submitBtnSpinner = document.getElementById('submitBtnSpinner');

        submitBtn.disabled = true;
        submitBtnText.textContent = 'Adding user...';
        submitBtnSpinner.classList.remove('hidden');
    });
</script>
@endpush
