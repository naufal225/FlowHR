@extends('components.super-admin.layout.layout-super-admin')
@section('header', 'Create Holiday')
@section('subtitle', 'Add a new holiday date')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
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
                    <a href="{{ route('super-admin.holidays.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Holidays</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">New Holiday</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Add New Holiday</h2>
            <p class="text-sm text-neutral-600">Fill in the details for the new holiday</p>
        </div>

        @if ($errors->any())
        <div class="px-4 py-3 mx-6 mt-6 border rounded-lg bg-error-50 border-error-200 text-error-700">
            <ul class="pl-5 space-y-1 list-disc">
                @foreach ($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('super-admin.holidays.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            <div>
                <label for="name" class="block mb-2 text-sm font-semibold text-neutral-700">
                    <i class="mr-2 fas fa-tag text-primary-600"></i>
                    Holiday Name
                </label>
                <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}"
                    placeholder="e.g., New Year's Day" required>
            </div>

            <div>
                <label for="holiday_date" class="block mb-2 text-sm font-semibold text-neutral-700">
                    <i class="mr-2 fas fa-calendar-alt text-primary-600"></i>
                    Holiday Date
                </label>
                <input type="date" id="holiday_date" name="holiday_date" class="form-input"
                    value="{{ old('holiday_date') }}" required min="{{ date('Y-m-d') }}">
            </div>

            <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ route('super-admin.holidays.index') }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-save"></i>
                    Save Holiday
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
