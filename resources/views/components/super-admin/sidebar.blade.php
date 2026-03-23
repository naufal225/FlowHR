<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-30 flex flex-col w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full sidebar-container bg-primary-800 shadow-medium lg:relative lg:translate-x-0"
    id="sidebar">
    <div class="flex items-center justify-between px-6 py-4 bg-primary-900">
        <div class="w-full">
            <img src="{{ asset('yaztech-logo-web.png') }}" alt="Yaztech Logo" class="w-auto h-12 mx-auto">
        </div>
        <button class="text-white lg:hidden hover:text-primary-200" onclick="toggleSidebar()">
            <i class="text-lg fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto sidebar-scroll">
        <a href="{{ route('super-admin.dashboard') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.dashboard') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-tachometer-alt"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('super-admin.divisions.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.divisions.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-users"></i>
            <span class="font-medium">Division</span>
        </a>

        <a href="{{ route('super-admin.users.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.users.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-users"></i>
            <span class="font-medium">User</span>
        </a>

        <!-- Leave Dropdown -->
        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('super-admin.leaves.*', 'super-admin.leave-balances.*', 'super-admin.holidays.*') ? 'true' : 'false' }} }">
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
                <a href="{{ route('super-admin.leaves.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('super-admin.leaves.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Leave Requests</span>
                </a>
                <a href="{{ route('super-admin.leave-balances.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('super-admin.leave-balances.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Leave Balances</span>
                </a>
                <!-- Tambahkan menu Holidays -->
                <a href="{{ route('super-admin.holidays.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('super-admin.holidays.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Holidays</span>
                </a>
            </div>
        </div>
        <!-- Reimbursement Dropdown -->
        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('super-admin.reimbursements.*') || request()->routeIs('super-admin.reimbursement-types.*') ? 'true' : 'false' }} }">

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
                <a href="{{ route('super-admin.reimbursements.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('super-admin.reimbursements.index') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>All Requests</span>
                </a>
                <a href="{{ route('super-admin.reimbursement-types.index') }}"
                    class="flex items-center px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ request()->routeIs('super-admin.reimbursement-types.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Reimbursement Types</span>
                </a>
            </div>
        </div>

        <a href="{{ route('super-admin.overtimes.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.overtimes.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-clock"></i>
            <span class="font-medium">Overtime Requests</span>
        </a>

        <a href="{{ route('super-admin.official-travels.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.official-travels.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-briefcase"></i>
            <span class="font-medium">Official Travel Requests</span>
        </a>

        <!-- Cost Settings Menu -->
        <a href="{{ route('super-admin.settings.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('super-admin.settings.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-cog"></i>
            <span class="font-medium">Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-primary-700">
        <a class="flex items-center mb-4" href="{{ route('super-admin.profile.index') }}">
            <div class="flex items-center justify-center w-10 h-10 mr-3 rounded-full bg-primary-600">
                @if(Auth::user()->url_profile)
                <img class="object-cover w-10 h-10 rounded-full" src="{{ Auth::user()->url_profile }}"
                    alt="{{ Auth::user()->name }}">
                @else
                <div class="flex items-center justify-center w-10 h-10 bg-gray-300 rounded-full">
                    <span class="text-sm font-medium text-gray-700">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </span>
                </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-primary-200">{{ Auth::user()->email }}</p>
            </div>
        </a>
        @if(Auth::user()->roles->count() >= 2)
        <a href="/choose-role"
            class="flex items-center w-full px-4 py-2 mb-2 transition-all duration-200 rounded-lg text-primary-100 hover:bg-primary-700 hover:text-white">
            <i class="w-5 mr-3 text-center fas fa-sync-alt"></i>
            <span class="font-medium">Change Role</span>
        </a>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="flex items-center w-full px-4 py-2 transition-all duration-200 rounded-lg text-primary-100 hover:bg-primary-700 hover:text-white">
                <i class="w-5 mr-3 text-center fas fa-sign-out-alt"></i>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div>
</div>
