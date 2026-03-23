@extends('components.admin.layout.layout-admin')
@section('header', 'Holiday Detail')
@section('subtitle', '')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('admin.holidays.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Holidays</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">{{ $holiday->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Holiday Details</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-neutral-700">Holiday Name</label>
                    <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                        <i class="mr-3 fas fa-tag text-primary-600"></i>
                        <span class="font-medium text-neutral-900">{{ $holiday->name }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-neutral-700">Holiday Date</label>
                    <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                        <i class="mr-3 fas fa-calendar-alt text-primary-600"></i>
                        <span class="font-medium text-neutral-900">
                            {{ $holiday->holiday_date->format('l, M d, Y') }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-neutral-700">Created At</label>
                    <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                        <i class="mr-3 fas fa-clock text-primary-600"></i>
                        <span class="font-medium text-neutral-900">
                            {{ $holiday->created_at->format('M d, Y \a\t H:i') }}
                        </span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-neutral-700">Last Updated</label>
                    <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                        <i class="mr-3 fas fa-sync-alt text-primary-600"></i>
                        <span class="font-medium text-neutral-900">
                            {{ $holiday->updated_at->format('M d, Y \a\t H:i') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end mt-6 space-x-4">
        <a href="{{ route('admin.holidays.index') }}"
            class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
            <i class="mr-2 fas fa-arrow-left"></i>
            Back to List
        </a>
        <a href="{{ route('admin.holidays.edit', $holiday->id) }}"
            class="px-6 py-2 text-sm font-medium text-white transition-colors duration-200 rounded-lg bg-secondary-600 hover:bg-secondary-700">
            <i class="mr-2 fas fa-edit"></i>
            Edit Holiday
        </a>
    </div>
</div>
@endsection
