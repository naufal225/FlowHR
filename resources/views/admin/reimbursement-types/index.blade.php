@extends('components.admin.layout.layout-admin')

@section('header', 'Manage Reimbursement Types')
@section('subtitle', 'Kelola jenis-jenis pengajuan reimbursement')

@section('content')
<main class="relative z-10 flex-1 p-0 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Reimbursement Types</h1>
                <p class="mt-2 text-sm text-gray-600">Kelola jenis-jenis pengajuan reimbursement untuk sistem</p>
            </div>
            <div class="flex flex-col gap-3 mt-4 sm:mt-0 sm:flex-row">
                <a href="{{ route('admin.reimbursement-types.create') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Type
                </a>
            </div>
        </div>

        <!-- Success/Error Message -->
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

        @if(session('error'))
        <div class="flex items-center p-4 my-6 border border-red-200 bg-red-50 rounded-xl">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="mb-6">
        <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-xl">
            <div class="">
                <form class="flex flex-col gap-4 sm:flex-row" action="{{ route('admin.reimbursement-types.index') }}"
                    method="GET">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" placeholder="Search types..." name="search"
                                value="{{ request('search') }}"
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
                    <div class="flex gap-2">
                        <button type="submit"
                            class="px-6 py-2 font-medium text-white transition-all duration-200 rounded-lg bg-gradient-to-r from-sky-500 to-sky-600 hover:from-sky-600 hover:to-sky-700">
                            Search
                        </button>
                        @if(request()->has('search') && request('search') != '')
                        <a href="{{ route('admin.reimbursement-types.index') }}"
                            class="px-6 py-2 font-medium text-gray-700 transition-all duration-200 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Reset
                        </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Reimbursement Types List</h3>
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
                                Created At</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($types as $type)
                        <tr class="transition-colors duration-200 hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $types->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-neutral-900">
                                    {{ $type->name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-neutral-500">
                                    {{ $type->created_at->format('d M Y H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-md font-medium whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('admin.reimbursement-types.edit', $type->id) }}"
                                        class="text-blue-600 hover:text-blue-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="text-red-600 delete-type-btn hover:text-red-900"
                                        data-type-id="{{ $type->id }}" data-type-name="{{ $type->name }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $type->id }}"
                                        action="{{ route('admin.reimbursement-types.destroy', $type->id) }}"
                                        method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-neutral-400">
                                    <i class="mb-4 text-4xl fas fa-inbox"></i>
                                    <p class="text-lg font-medium">
                                        @if(request()->has('search') && request('search') != '')
                                        No reimbursement types found for "{{ request('search') }}"
                                        @else
                                        No reimbursement types found
                                        @endif
                                    </p>

                                    <!-- Hanya tampilkan tombol New Type jika tidak ada data di database sama sekali -->
                                    @if($totalTypes == 0)
                                    <a href="{{ route('admin.reimbursement-types.create') }}"
                                        class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        New Type
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($types->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $types->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection

@section('partial-modal')
<!-- Delete Confirmation Modal -->
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
                <h3 class="mb-2 text-lg font-semibold text-gray-900">Delete Reimbursement Type</h3>
                <p class="mb-6 text-sm text-gray-500">
                    Are you sure you want to delete <span id="typeName" class="font-medium text-gray-900"></span>?
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
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Delete button click handler
        document.querySelectorAll('.delete-type-btn').forEach(button => {
            button.addEventListener('click', function () {
                const typeId = this.getAttribute('data-type-id');
                const typeName = this.getAttribute('data-type-name');
                const deleteForm = document.getElementById(`delete-form-${typeId}`);

                // Set modal content
                document.getElementById('typeName').textContent = typeName;
                document.getElementById('confirmDeleteBtn').onclick = function() {
                    // Show spinner
                    document.getElementById('deleteButtonText').textContent = 'Deleting...';
                    document.getElementById('deleteSpinner').classList.remove('hidden');

                    // Submit form
                    deleteForm.submit();
                };

                // Show modal
                document.getElementById('deleteConfirmModal').classList.remove('hidden');
            });
        });

        // Cancel delete
        document.getElementById('cancelDeleteButton').addEventListener('click', function() {
            document.getElementById('deleteConfirmModal').classList.add('hidden');
        });

        // Close modal when clicking outside
        document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
</script>
@endpush
