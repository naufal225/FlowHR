<div class="min-h-screen bg-gray-50">
    <main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto">
        <div class="max-w-7xl mx-auto space-y-6">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route($routePrefix . '.dashboard') }}"
                            class="inline-flex items-center font-medium text-gray-600 transition hover:text-blue-600">
                            <i class="mr-2 fas fa-home"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="mx-2 text-xs text-gray-400 fas fa-chevron-right"></i>
                            <a href="{{ route($routePrefix . '.office-locations.index') }}"
                                class="font-medium text-gray-600 transition hover:text-blue-600">
                                Office Locations
                            </a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="mx-2 text-xs text-gray-400 fas fa-chevron-right"></i>
                            <span class="font-medium text-gray-500">{{ $officeLocation->name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <section class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm font-medium tracking-wide text-blue-600 uppercase">Office Detail</p>
                    <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $officeLocation->name }}</h1>
                    <p class="mt-2 text-sm text-gray-600">Review office metadata, active status, and the latest assigned employees from one page.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route($routePrefix . '.office-locations.index') }}"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to List
                    </a>
                    <a href="{{ route($routePrefix . '.office-locations.edit', $officeLocation) }}"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all rounded-lg shadow-sm bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
                        <i class="mr-2 fas fa-edit"></i>
                        Edit Office
                    </a>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Total Employees</p>
                        <div class="flex items-end justify-between mt-3">
                            <div>
                                <p class="text-3xl font-bold text-gray-900">{{ number_format($officeLocation->users_count) }}</p>
                                <p class="mt-1 text-sm text-gray-500">Assigned to this office location</p>
                            </div>
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 text-blue-600">
                                <i class="text-lg fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Location Status</p>
                        <div class="flex items-end justify-between mt-3">
                            <div>
                                @if($officeLocation->is_active)
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full bg-emerald-100 text-emerald-700">Active</span>
                                    <p class="mt-2 text-sm text-gray-500">This office can be used for active assignments and attendance setup.</p>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-full bg-rose-100 text-rose-700">Inactive</span>
                                    <p class="mt-2 text-sm text-gray-500">This office is kept for reference and is not currently active.</p>
                                @endif
                            </div>
                            <div class="flex items-center justify-center w-12 h-12 rounded-full {{ $officeLocation->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                <i class="text-lg fas {{ $officeLocation->is_active ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-12">
                <div class="space-y-6 xl:col-span-4">
                    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Office Profile</h2>
                            <p class="mt-1 text-sm text-gray-500">Core location metadata used by the attendance module.</p>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-1">
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Office Code</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $officeLocation->code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Office Name</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $officeLocation->name }}</dd>
                                </div>
                                <div class="sm:col-span-2 xl:col-span-1">
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Address</dt>
                                    <dd class="mt-1 text-sm leading-6 text-gray-900">{{ $officeLocation->address ?: '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Latitude</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ number_format((float) $officeLocation->latitude, 7) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Longitude</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ number_format((float) $officeLocation->longitude, 7) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Radius</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ number_format($officeLocation->radius_meter) }} m</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium tracking-wide text-gray-500 uppercase">Timezone</dt>
                                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $officeLocation->timezone ?: '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <h2 class="text-lg font-semibold text-gray-900">Audit Timeline</h2>
                            <p class="mt-1 text-sm text-gray-500">Reference timestamps for this office record.</p>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Created At</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $officeLocation->created_at?->format('d M Y, H:i') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Updated At</p>
                                <p class="mt-1 text-sm text-gray-900">{{ $officeLocation->updated_at?->format('d M Y, H:i') ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium tracking-wide text-gray-500 uppercase">Attendance Records</p>
                                <p class="mt-1 text-sm text-gray-900">{{ number_format($officeLocation->attendances_count) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-8">
                    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Assigned Employees</h2>
                                    <p class="mt-1 text-sm text-gray-500">Employees are ordered by newest user record first to approximate the latest office assignment list.</p>
                                </div>
                                <span class="inline-flex items-center self-start px-3 py-1 text-sm font-medium rounded-full bg-blue-50 text-blue-700">
                                    {{ number_format($assignedEmployees->total()) }} total employees
                                </span>
                            </div>
                        </div>

                        @if($assignedEmployees->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-white">
                                        <tr>
                                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Employee</th>
                                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Division</th>
                                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Added</th>
                                            <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach($assignedEmployees as $employee)
                                            <tr class="transition-colors hover:bg-gray-50">
                                                <td class="px-6 py-4">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">{{ $employee->name }}</p>
                                                        <p class="mt-1 text-sm text-gray-500">{{ $employee->email }}</p>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">{{ $employee->division?->name ?? '-' }}</td>
                                                <td class="px-6 py-4">
                                                    @if($employee->is_active)
                                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Active</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">{{ $employee->created_at?->format('d M Y, H:i') ?? '-' }}</td>
                                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                                    <a href="{{ route($routePrefix . '.users.show', $employee) }}" class="text-sm font-medium text-blue-600 transition hover:text-blue-800">
                                                        View User
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="flex flex-col gap-4 px-6 py-4 border-t border-gray-100 bg-gray-50 md:flex-row md:items-center md:justify-between">
                                <p class="text-sm text-gray-500">
                                    Showing {{ $assignedEmployees->firstItem() }}-{{ $assignedEmployees->lastItem() }} of {{ $assignedEmployees->total() }} assigned employees.
                                </p>
                                {{ $assignedEmployees->links() }}
                            </div>
                        @else
                            <div class="p-6">
                                <div class="flex flex-col items-center justify-center px-6 py-12 text-center border border-dashed rounded-2xl bg-gray-50 border-gray-200">
                                    <div class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-50 text-blue-600">
                                        <i class="text-xl fas fa-user-slash"></i>
                                    </div>
                                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No employees assigned</h3>
                                    <p class="mt-2 text-sm text-gray-500 max-w-md">This office location does not have any assigned employees yet. Once users are assigned to this office, they will appear here with the newest records first.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>
