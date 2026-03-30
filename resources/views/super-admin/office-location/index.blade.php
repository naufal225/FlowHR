@extends('components.super-admin.layout.layout-super-admin')

@section('header', 'Manage Office Location')
@section('subtitle', 'Kelola data kantor')

@section('content')
<main class="relative z-10 flex-1 p-0 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manage Office Location</h1>
                <p class="mt-2 text-sm text-gray-600">Maintain office master data for user and attendance management</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('super-admin.office-locations.create') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 hover:scale-105">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Office
                </a>
            </div>
        </div>


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
            <form class="flex flex-col gap-4 sm:flex-row" action="{{ route('super-admin.office-locations.index') }}"
                method="GET">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" placeholder="Search office by code, name, or address..." name="search"
                            value="{{ $search ?? request('search') }}"
                            class="w-full py-2 pl-10 pr-4 transition-colors border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Office List</h3>
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
                                Code</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Office Name</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Address</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Radius</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Users</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Status</th>
                            <th
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left uppercase text-neutral-500">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-neutral-200">
                        @forelse($officeLocations as $officeLocation)
                        <tr class="transition-colors duration-200 hover:bg-neutral-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $officeLocations->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded bg-slate-100 text-slate-700">
                                    {{ $officeLocation->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-900 whitespace-nowrap">{{ $officeLocation->name }}
                            </td>
                            <td class="max-w-sm px-6 py-4 text-sm truncate text-neutral-500"
                                title="{{ $officeLocation->address }}">
                                {{ $officeLocation->address ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-neutral-500 whitespace-nowrap">{{
                                $officeLocation->radius_meter }} m</td>
                            <td class="px-6 py-4 text-sm text-neutral-500 whitespace-nowrap">{{
                                $officeLocation->users_count }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($officeLocation->is_active)
                                <span
                                    class="px-2.5 py-1 text-xs font-medium text-emerald-700 rounded-full bg-emerald-100">Active</span>
                                @else
                                <span
                                    class="px-2.5 py-1 text-xs font-medium text-gray-700 rounded-full bg-gray-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-medium text-md whitespace-nowrap">
                                <a href="{{ route('super-admin.office-locations.edit', $officeLocation) }}"
                                    class="text-secondary-600 hover:text-secondary-900" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-neutral-400">
                                    <i class="mb-4 text-4xl fas fa-building"></i>
                                    <p class="text-lg font-medium">No office location found</p>
                                    <a href="{{ route('super-admin.office-locations.create') }}"
                                        class="inline-flex items-center px-4 py-2 mt-4 text-white transition-colors duration-200 rounded-lg bg-primary-600 hover:bg-primary-700">
                                        <i class="mr-2 fas fa-plus"></i>
                                        New office
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($officeLocations->hasPages())
            <div class="px-6 py-4 border-t border-neutral-200">
                {{ $officeLocations->links() }}
            </div>
            @endif
        </div>
    </div>
</main>
@endsection
