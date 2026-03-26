@extends('components.super-admin.layout.layout-super-admin')

@section('header', 'Update Office Location')
@section('subtitle', 'Perbarui data kantor')

@section('content')
<main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Update Office Location</h1>
                <p class="mt-2 text-sm text-gray-600">Update office profile and operational settings</p>
            </div>
            <a href="{{ route('super-admin.office-locations.index') }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <div class="max-w-3xl mx-auto">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Office Information</h3>
                <p class="mt-1 text-sm text-gray-600">Code: {{ $officeLocation->code }}</p>
            </div>

            <div class="p-6">
                @if($errors->any())
                <div class="flex items-start p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 mt-0.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                <form action="{{ route('super-admin.office-locations.update', $officeLocation) }}" method="POST" class="space-y-6" id="officeForm">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="code" class="block mb-2 text-sm font-medium text-gray-700">
                                Office Code <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="code" name="code" value="{{ old('code', $officeLocation->code) }}"
                                class="w-full px-4 py-3 uppercase border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="JKT-HQ" required>
                            @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-700">
                                Office Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $officeLocation->name) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="Jakarta Head Office" required>
                            @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block mb-2 text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                            placeholder="Full office address">{{ old('address', $officeLocation->address) }}</textarea>
                        @error('address')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <label for="latitude" class="block mb-2 text-sm font-medium text-gray-700">
                                Latitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $officeLocation->latitude) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('latitude') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="-6.2000000" required>
                            @error('latitude')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="longitude" class="block mb-2 text-sm font-medium text-gray-700">
                                Longitude <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $officeLocation->longitude) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('longitude') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="106.8166667" required>
                            @error('longitude')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="radius_meter" class="block mb-2 text-sm font-medium text-gray-700">
                                Radius (meter) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="radius_meter" name="radius_meter" value="{{ old('radius_meter', $officeLocation->radius_meter) }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('radius_meter') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                placeholder="100" min="1" required>
                            @error('radius_meter')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <input type="hidden" name="is_active" value="0">
                        <label class="inline-flex items-center gap-3">
                            <input type="checkbox" name="is_active" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded"
                                {{ old('is_active', $officeLocation->is_active) ? 'checked' : '' }}>
                            <span class="text-sm font-medium text-gray-700">Active office location</span>
                        </label>
                        <p class="mt-2 text-xs text-gray-500">Inactive office cannot be selected for new user assignment.</p>
                    </div>

                    <div class="flex items-center justify-end pt-6 space-x-4 border-t border-gray-200">
                        <a href="{{ route('super-admin.office-locations.index') }}"
                            class="px-6 py-3 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-3 text-sm font-medium text-white transition-all rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
                            Update Office
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
