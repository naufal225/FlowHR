@extends('components.super-admin.layout.layout-super-admin')

@section('header', 'User Detail')
@section('subtitle', 'Detail profil pengguna')

@section('content')
<main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                <p class="mt-2 text-sm text-gray-600">Detailed user profile and office assignment</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('super-admin.users.index') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
                <a href="{{ route('super-admin.users.edit', $user) }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
                    <i class="mr-2 fas fa-edit"></i>
                    Edit User
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="lg:col-span-2">
            <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">User Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Division</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->division->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Office Location</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->officeLocation->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Roles</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user->role_display }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                @if($user->is_active)
                                <span class="px-2.5 py-1 text-xs font-medium text-emerald-700 rounded-full bg-emerald-100">
                                    Active
                                </span>
                                @else
                                <span class="px-2.5 py-1 text-xs font-medium text-gray-700 rounded-full bg-gray-200">
                                    Inactive
                                </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <aside>
            <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-900">Audit Snapshot</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Created At</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->created_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Updated At</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->updated_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Office Assignment</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $user->officeLocation->name ?? 'Not assigned' }}</p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</main>
@endsection
