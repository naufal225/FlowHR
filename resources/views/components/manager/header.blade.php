<header class="bg-secondary-500 shadow-soft">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">

            <button id="sidebar-toggle"
                class="mr-4 text-white hover:text-sky-200 focus:outline-none focus:text-sky-200 lg:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div>
                <h2 class="text-xl font-bold text-white">@yield('header', 'Dashboard')</h2>
                <p class="text-sm text-secondary-100">@yield('subtitle', 'Welcome back!')</p>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <div class="flex items-center px-3 py-2 rounded-full bg-secondary-600">
                <div class="flex items-center justify-center w-8 h-8 bg-white rounded-full lg:mr-2">
                    <span class="text-sm font-semibold text-secondary-600">{{ strtoupper(substr(trim(explode(' ',
                        Auth::user()->name)[0]), 0, 1)) }}</span>
                </div>
                <span class="hidden text-sm font-medium text-white lg:block">{{ Auth::user()->name }}</span>
            </div>
        </div>
    </div>
</header>