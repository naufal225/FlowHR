<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-30 flex flex-col w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full sidebar-container bg-primary-800 shadow-medium lg:relative lg:translate-x-0"
    id="sidebar">
    <div class="flex items-center justify-between h-32 px-6 py-4">
        <div class="w-full">
            <img src="{{ asset(config('branding.dark_surface_logo')) }}" alt="FlowHR Logo" class="w-24 h-auto max-w-full mx-auto md:w-48 rounded-xl">
        </div>
        <button class="text-white lg:hidden hover:text-primary-200" onclick="toggleSidebar()">
            <i class="text-lg fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto sidebar-scroll">
        <a href="{{ route('admin.dashboard') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.dashboard') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-tachometer-alt"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('admin.divisions.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.divisions.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-users"></i>
            <span class="font-medium">Division</span>
        </a>

        <a href="{{ route('admin.office-locations.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.office-locations.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-building"></i>
            <span class="font-medium">Office Location</span>
        </a>

        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('admin.attendance.*') ? 'true' : 'false' }} }">
            <button type="button"
                class="flex items-center w-full px-4 py-3 text-left transition-all duration-200 rounded-lg"
                :class="open ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white'"
                @click="open = !open">
                <i class="w-5 mr-3 text-center fas fa-user-check"></i>
                <span class="flex-1 font-medium">Attendance</span>
                <i class="text-xs transition-transform duration-200 fas fa-chevron-down"
                    :class="{ 'rotate-180': open }"></i>
            </button>
            <div class="pl-4 space-y-1 overflow-hidden" x-show="open" x-collapse>
                <a href="{{ route('admin.attendance.index') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('admin.attendance.index') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Overview</span>
                </a>
                <a href="{{ route('admin.attendance.records') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('admin.attendance.records', 'admin.attendance.show') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Records</span>
                </a>
                <a href="{{ route('admin.attendance.corrections.index') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('admin.attendance.corrections.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Corrections</span>
                </a>
                <a href="{{ route('admin.attendance.qr') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('admin.attendance.qr*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>QR Code</span>
                </a>
                <a href="{{ route('admin.attendance.settings') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('admin.attendance.settings*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <a href="{{ route('admin.users.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.users.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-users"></i>
            <span class="font-medium">User</span>
        </a>

        <!-- Leave Dropdown -->
        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('admin.leaves.*', 'admin.leave-balances.*', 'admin.holidays.*') ? 'true' : 'false' }} }">
            <button type="button"
                class="flex items-center w-full px-4 py-3 text-left transition-all duration-200 rounded-lg"
                :class="open ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white'"
                @click="open = !open">
                <i class="w-5 mr-3 text-center fas fa-plane-departure"></i>
                <span class="flex-1 font-medium">Leave Management</span>
                <i class="text-xs transition-transform duration-200 fas fa-chevron-down"
                    :class="{'rotate-180': open}"></i>
            </button>
            <div class="pl-4 space-y-1 overflow-hidden" x-show="open" x-collapse>
                <a href="{{ route('admin.leaves.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.leaves.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Leave Requests</span>
                </a>
                <a href="{{ route('admin.leave-balances.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.leave-balances.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Leave Balances</span>
                </a>
                <!-- Tambahkan menu Holidays -->
                <a href="{{ route('admin.holidays.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.holidays.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Holidays</span>
                </a>
            </div>
        </div>
        <!-- Reimbursement Dropdown -->
        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('admin.reimbursements.*') || request()->routeIs('admin.reimbursement-types.*') ? 'true' : 'false' }} }">

            <button type="button"
                class="flex items-center w-full px-4 py-3 text-left transition-all duration-200 rounded-lg"
                :class="open ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white'"
                @click="open = !open">
                <i class="w-5 mr-3 text-center fas fa-file-invoice-dollar"></i>
                <span class="flex-1 font-medium">Reimbursement</span>
                <i class="text-xs transition-transform duration-200 fas fa-chevron-down"
                    :class="{'rotate-180': open}"></i>
            </button>
            <div class="pl-4 space-y-1 overflow-hidden" x-show="open" x-collapse>
                <a href="{{ route('admin.reimbursements.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.reimbursements.index') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>All Requests</span>
                </a>
                <a href="{{ route('admin.reimbursement-types.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('admin.reimbursement-types.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Reimbursement Types</span>
                </a>
            </div>
        </div>

        <a href="{{ route('admin.overtimes.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.overtimes.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-clock"></i>
            <span class="font-medium">Overtime Requests</span>
        </a>

        <a href="{{ route('admin.official-travels.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.official-travels.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-briefcase"></i>
            <span class="font-medium">Official Travel Requests</span>
        </a>

        <!-- Cost Settings Menu -->
        <a href="{{ route('admin.settings.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('admin.settings.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-cog"></i>
            <span class="font-medium">Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-primary-700 bg-primary-900/60">
        <a class="mb-4 flex items-center rounded-xl border border-primary-600/80 bg-primary-700/60 p-3 transition-all duration-200 hover:bg-primary-700"
            href="{{ route('admin.profile.index') }}">
            <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary-600">
                @if(Auth::user()->url_profile)
                <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->url_profile }}"
                    alt="{{ Auth::user()->name }}">
                @else
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-300">
                    <span class="text-sm font-medium text-gray-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                </div>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-primary-100">{{ Auth::user()->email }}</p>
            </div>
        </a>
        @if(Auth::user()->roles->count() >= 2)
        <a href="/choose-role"
            class="mb-2 flex w-full items-center rounded-lg border border-sky-300/40 bg-sky-600/25 px-4 py-2.5 text-sky-100 transition-all duration-200 hover:bg-sky-500/35 hover:text-white">
            <i class="fas fa-sync-alt mr-3 w-5 text-center"></i>
            <span class="font-medium">Change Role</span>
        </a>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="flex w-full items-center rounded-lg border border-rose-300/40 bg-rose-600/20 px-4 py-2.5 text-rose-100 transition-all duration-200 hover:bg-rose-500/30 hover:text-white">
                <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div>
</div>

