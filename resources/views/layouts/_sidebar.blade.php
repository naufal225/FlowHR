<!-- Unified Sidebar -->
<div class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full bg-primary-800 shadow-medium lg:relative lg:translate-x-0 lg:h-full"
    id="sidebar">

    <!-- Logo -->
    <div class="flex items-center justify-between h-32 px-6 py-4">
        <div class="w-full">
            <img src="{{ asset(config('branding.dark_surface_logo')) }}" alt="FlowHR Logo"
                class="w-24 h-auto max-w-full mx-auto md:w-48 rounded-xl">
        </div>
        <button class="text-white lg:hidden hover:text-primary-200" onclick="toggleSidebar()">
            <i class="text-lg fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 min-h-0 px-4 py-6 space-y-1 overflow-y-auto sidebar-scroll">

        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-tachometer-alt"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        {{-- Attendance --}}
        <div class="space-y-1"
            x-data="{ open: {{ request()->routeIs('attendance.*') ? 'true' : 'false' }} }">
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
                <a href="{{ route('attendance.index') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.index') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Overview</span>
                </a>
                <a href="{{ route('attendance.history') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.history', 'attendance.show') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>History</span>
                </a>
                @if(Auth::user()->hasRole(['admin', 'superAdmin']))
                <a href="{{ route('attendance.records') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.records') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>All Records</span>
                </a>
                <a href="{{ route('attendance.corrections.index') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.corrections.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Corrections</span>
                </a>
                <a href="{{ route('attendance.qr') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.qr*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>QR Code</span>
                </a>
                <a href="{{ route('attendance.settings') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.settings*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Settings</span>
                </a>
                @elseif(Auth::user()->hasRole('approver'))
                <a href="{{ route('attendance.team') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.team*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Team Attendance</span>
                </a>
                <a href="{{ route('attendance.corrections.index') }}"
                    class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('attendance.corrections.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                    <span>Corrections</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Leave --}}
        @if(\App\Models\FeatureSetting::isActive('cuti'))
            @if(Auth::user()->hasRole(['admin', 'superAdmin']))
            {{-- Admin/Super-Admin: dropdown with management options --}}
            <div class="space-y-1"
                x-data="{ open: {{ request()->routeIs('leaves.*', 'leave-balances.*', 'holidays.*') ? 'true' : 'false' }} }">
                <button type="button"
                    class="flex items-center w-full px-4 py-3 text-left transition-all duration-200 rounded-lg"
                    :class="open ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white'"
                    @click="open = !open">
                    <i class="w-5 mr-3 text-center fas fa-plane-departure"></i>
                    <span class="flex-1 font-medium">Leave Management</span>
                    <i class="text-xs transition-transform duration-200 fas fa-chevron-down"
                        :class="{ 'rotate-180': open }"></i>
                </button>
                <div class="pl-4 space-y-1 overflow-hidden" x-show="open" x-collapse>
                    <a href="{{ route('leaves.index') }}"
                        class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('leaves.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                        <span>Leave Requests</span>
                    </a>
                    <a href="{{ route('leave-balances.index') }}"
                        class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('leave-balances.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                        <span>Leave Balances</span>
                    </a>
                    <a href="{{ route('holidays.index') }}"
                        class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('holidays.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                        <span>Holidays</span>
                    </a>
                </div>
            </div>
            @else
            {{-- All other roles: single link --}}
            <a href="{{ route('leaves.index') }}"
                class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('leaves.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-calendar-alt"></i>
                <span class="font-medium">Leave</span>
            </a>
            @endif
        @endif

        {{-- Reimbursement --}}
        @if(\App\Models\FeatureSetting::isActive('reimbursement'))
            @if(Auth::user()->hasRole(['admin', 'superAdmin']))
            <div class="space-y-1"
                x-data="{ open: {{ request()->routeIs('reimbursements.*', 'reimbursement-types.*') ? 'true' : 'false' }} }">
                <button type="button"
                    class="flex items-center w-full px-4 py-3 text-left transition-all duration-200 rounded-lg"
                    :class="open ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white'"
                    @click="open = !open">
                    <i class="w-5 mr-3 text-center fas fa-file-invoice-dollar"></i>
                    <span class="flex-1 font-medium">Reimbursement</span>
                    <i class="text-xs transition-transform duration-200 fas fa-chevron-down"
                        :class="{ 'rotate-180': open }"></i>
                </button>
                <div class="pl-4 space-y-1 overflow-hidden" x-show="open" x-collapse>
                    <a href="{{ route('reimbursements.index') }}"
                        class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('reimbursements.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                        <span>All Requests</span>
                    </a>
                    <a href="{{ route('reimbursement-types.index') }}"
                        class="flex items-center px-4 py-2 text-sm transition-all duration-200 rounded-lg {{ request()->routeIs('reimbursement-types.*') ? 'bg-primary-600 text-white' : 'text-primary-200 hover:bg-primary-700 hover:text-white' }}">
                        <span>Types</span>
                    </a>
                </div>
            </div>
            @else
            <a href="{{ route('reimbursements.index') }}"
                class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('reimbursements.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-receipt"></i>
                <span class="font-medium">Reimbursement</span>
            </a>
            @endif
        @endif

        {{-- Overtime --}}
        @if(\App\Models\FeatureSetting::isActive('overtime'))
        <a href="{{ route('overtimes.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('overtimes.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-clock"></i>
            <span class="font-medium">Overtime</span>
        </a>
        @endif

        {{-- Official Travel --}}
        @if(\App\Models\FeatureSetting::isActive('perjalanan_dinas'))
        <a href="{{ route('official-travels.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('official-travels.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-briefcase"></i>
            <span class="font-medium">Official Travel</span>
        </a>
        @endif

        {{-- Admin/Super-Admin Only: Management Section --}}
        @if(Auth::user()->hasRole(['admin', 'superAdmin']))
        <div class="pt-3 mt-3 border-t border-primary-600/50">
            <p class="mb-1 px-4 text-[10px] font-semibold uppercase tracking-wider text-primary-400">Management</p>

            <a href="{{ route('divisions.index') }}"
                class="flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 {{ request()->routeIs('divisions.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-users"></i>
                <span class="font-medium">Division</span>
            </a>

            <a href="{{ route('office-locations.index') }}"
                class="flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 {{ request()->routeIs('office-locations.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-building"></i>
                <span class="font-medium">Office Location</span>
            </a>

            <a href="{{ route('users.index') }}"
                class="flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 {{ request()->routeIs('users.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-user-cog"></i>
                <span class="font-medium">Users</span>
            </a>

            <a href="{{ route('settings.index') }}"
                class="flex items-center px-4 py-2.5 rounded-lg transition-all duration-200 {{ request()->routeIs('settings.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                <i class="w-5 mr-3 text-center fas fa-cog"></i>
                <span class="font-medium">Settings</span>
            </a>
        </div>
        @endif

    </nav>

    <!-- Profile & Logout -->
    <div class="flex-shrink-0 p-4 border-t border-primary-700 bg-primary-900/60">
        <a class="mb-4 flex items-center rounded-xl border border-primary-600/80 bg-primary-700/60 p-3 transition-all duration-200 hover:bg-primary-700"
            href="{{ route('profile.index') }}">
            <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary-600">
                @if(Auth::user()->url_profile)
                <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->url_profile }}"
                    alt="{{ Auth::user()->name }}">
                @else
                <span class="text-sm font-semibold text-white">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </span>
                @endif
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-primary-100">{{ Auth::user()->getRoleArray() }}</p>
            </div>
        </a>
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
