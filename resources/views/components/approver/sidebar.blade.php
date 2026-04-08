<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 z-30 z-50 flex flex-col w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full bg-primary-800 shadow-medium lg:relative lg:translate-x-0"
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
    <nav class="flex-1 px-4 py-6 space-y-2">
        <a href="{{ route('approver.dashboard') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.dashboard') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-tachometer-alt"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <a href="{{ route('approver.attendance.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.attendance.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
            <i class="w-5 mr-3 text-center fas fa-user-clock"></i>
            <span class="font-medium">Attendance</span>
        </a>

        @if(\App\Models\FeatureSetting::isActive('cuti'))
        <a href="{{ route('approver.leaves.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.leaves.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">

            <i class="w-5 mr-3 text-center fas fa-plane-departure"></i>
            <span class="font-medium">Leave Requests</span>
        </a>
        @endif

        @if(\App\Models\FeatureSetting::isActive('reimbursement'))
        <a href="{{ route('approver.reimbursements.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.reimbursements.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">

            <i class="w-5 mr-3 text-center fas fa-file-invoice-dollar"></i>
            <span class="font-medium">Reimbursement Requests</span>
        </a>
        @endif

        @if(\App\Models\FeatureSetting::isActive('overtime'))
        <a href="{{ route('approver.overtimes.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.overtimes.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">

            <i class="w-5 mr-3 text-center fas fa-clock"></i>
            <span class="font-medium">Overtime Requests</span>
        </a>
        @endif

        @if(\App\Models\FeatureSetting::isActive('perjalanan_dinas'))
        <a href="{{ route('approver.official-travels.index') }}"
            class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('approver.official-travels.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">

            <i class="w-5 mr-3 text-center fas fa-briefcase"></i>
            <span class="font-medium">Official Travel Requests</span>
        </a>
        @endif

    </nav>

    <div class="p-4 border-t border-primary-700 bg-primary-900/60">
        <a class="flex items-center p-3 mb-4 transition-all duration-200 border rounded-xl border-primary-600/80 bg-primary-700/60 hover:bg-primary-700"
            href="{{ route('approver.profile.index') }}">
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
                <p class="text-xs text-primary-100">{{ Auth::user()->email }}</p>
            </div>
        </a>
        @if(Auth::user()->roles->count() >= 2)
        <a href="/choose-role"
            class="mb-2 flex w-full items-center rounded-lg border border-sky-300/40 bg-sky-600/25 px-4 py-2.5 text-sky-100 transition-all duration-200 hover:bg-sky-500/35 hover:text-white">
            <i class="w-5 mr-3 text-center fas fa-sync-alt"></i>
            <span class="font-medium">Change Role</span>
        </a>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                class="flex w-full items-center rounded-lg border border-rose-300/40 bg-rose-600/20 px-4 py-2.5 text-rose-100 transition-all duration-200 hover:bg-rose-500/30 hover:text-white">
                <i class="w-5 mr-3 text-center fas fa-sign-out-alt"></i>
                <span class="font-medium">Logout</span>
            </button>
        </form>
    </div>
</div>

