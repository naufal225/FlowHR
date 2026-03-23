@extends('components.admin.layout.layout-admin')

@section('header', 'Manage Division')
@section('subtitle', 'Manage division data')

@section('content')
<main class="relative z-10 flex-1 p-0 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Division</h1>
                <p class="mt-2 text-sm text-gray-600">Manage your division data and information</p>
            </div>
            <div class="flex flex-col gap-3 mt-4 sm:mt-0 sm:flex-row">
                {{-- <button id="importExcelBtn"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    Import Excel
                </button> --}}
                <a id="addDivisionBtn" href="{{ route('admin.divisions.create') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Division
                </a>
            </div>
        </div>


    </div>

    <div class="">
        <!-- Success Message -->
        @if(session('success'))
        <div class="flex items-center p-4 my-6 border border-green-200 bg-green-50 rounded-xl">
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
    </div>

    <div class="mb-6">
        <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="">
                <form class="flex flex-col gap-4 sm:flex-row" action="{{ route("admin.divisions.index") }}"
                    method="GET">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" placeholder="Search divisions..." name="search"
                                class="w-full py-2 pl-10 pr-4 transition-colors border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <button
                        class="px-6 py-2 font-medium text-white transition-all duration-200 rounded-lg bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700">
                        Search
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Division List</h3>
            </div>
        </div>
        <div class="overflow-hidden bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200">
                    <thead class="bg-neutral-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                No</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Name</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Leader</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($divisions as $division)
                        <tr class="transition-colors duration-200 hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    {{
                                    $divisions->firstItem() + $loop->index }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-900">
                                    {{ $division->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500">
                                    {{ $division->leader->name ?? "N/A" }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-md font-medium whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.divisions.edit', $division->id) }}"
                                        class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                        class="delete-division-btn text-error-600 hover:text-error-900"
                                        data-division-id="{{ $division->id }}"
                                        data-division-name="{{ $division->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $division->id }}"
                                        action="{{ route('admin.divisions.destroy', $division->id) }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-neutral-400">
                                    <i class="mb-4 text-4xl fas fa-inbox"></i>
                                    <p class="text-lg font-medium">No divisions found</p>
                                    <a href="{{ route('admin.divisions.create') }}"
                                        class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        New division
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($divisions->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $divisions->links() }}
            </div>
            @endif
        </div>
    </div>
</main>

@endsection

@section('partial-modal')

<div id="deleteConfirmModal" class="fixed inset-0 z-50 hidden overflow-y-auto" data-inline-hidden style="display: none;">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

        <div
            class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>

            <div class="text-center">
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Division</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="divisionName" class="font-medium text-gray-900"></span>?
                    This action cannot be undone.
                </p>
            </div>

            <div class="flex justify-center space-x-3">
                <button type="button" id="cancelDeleteButton"
                    class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg cancel-delete-btn hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button type="button" id="confirmDeleteBtn"
                    class="px-4 py-2 text-sm font-medium text-white transition-colors bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <span id="deleteButtonText">Delete</span>
                    <svg id="deleteSpinner" class="hidden w-4 h-4 ml-2 -mr-1 text-white animate-spin" data-inline-hidden style="display: none;" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@vite("resources/js/admin/division/script.js")
@endpush
