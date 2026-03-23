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
                <span class="hidden text-sm font-medium text-white lg:block">{{ Auth::user()->name }}</span>
            </div>
        </div>
    </div>
</header>