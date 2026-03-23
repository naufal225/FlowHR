@extends('components.super-admin.layout.layout-super-admin')
@section('header', 'Reimbursement Type Detail')
@section('subtitle', 'View detailed information of a reimbursement type')
@section('content')

<main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Reimbursement Type: {{ $reimbursementType->name }}</h1>
                <p class="mt-2 text-sm text-gray-600">Detailed view of the reimbursement type</p>
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

    <!-- Detail Card -->
    <div class="max-w-2xl mx-auto">
        <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
            <!-- Card Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="mr-2 fas fa-file-invoice-dollar"></i>
                    {{ $reimbursementType->name }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">Created on {{ $reimbursementType->created_at->format('d M Y \a\t
                    H:i') }}</p>
            </div>

            <!-- Card Content -->
            <div class="p-6">
                <!-- Success Message (if redirected from edit/delete) -->
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

                <!-- Detail Table -->
                <div class="overflow-hidden border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap">
                                    ID
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $reimbursementType->id }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap">
                                    Name
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $reimbursementType->name }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap">
                                    Created At
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $reimbursementType->created_at->format('d M Y H:i:s') }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-500 whitespace-nowrap">
                                    Updated At
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $reimbursementType->updated_at->format('d M Y H:i:s') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end pt-6 mt-6 space-x-4 border-t border-gray-200">
                    <a href="{{ route('super-admin.reimbursement-types.index') }}"
                        class="px-6 py-3 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Back
                    </a>
                    <a href="{{ route('super-admin.reimbursement-types.edit', $reimbursementType->id) }}"
                        class="px-6 py-3 text-sm font-medium text-white transition-all rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
