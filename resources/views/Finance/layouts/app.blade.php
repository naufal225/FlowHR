<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', 'Employee Portal')</title>
    @vite('resources/css/app.css')
    <link rel="icon" type="image/x-icon" href="{{ asset('FlowHR_logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>[x-cloak]{display:none !important}</style>
    @stack('styles')
</head>

<body class="h-screen overflow-hidden font-sans antialiased bg-neutral-50">
    <div class="flex h-full">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 text-white transition-transform duration-300 ease-in-out transform -translate-x-full bg-primary-800 shadow-medium lg:relative lg:translate-x-0"
            id="sidebar">
            <div class="flex items-center justify-between h-32 px-6 py-4">
                <div class="w-full">
                    <img src="{{ asset(config('branding.dark_surface_logo')) }}" alt="FlowHR Logo" class="w-24 h-auto max-w-full mx-auto md:w-48 rounded-xl">
                </div>
                <button class="z-30 text-white lg:hidden hover:text-primary-200" id="btnNav">
                    <i class="text-lg fas fa-times"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="{{ route('finance.dashboard') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('finance.dashboard') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                    <i class="w-5 mr-3 text-center fas fa-home"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                @if(\App\Models\FeatureSetting::isActive('cuti'))
                <a href="{{ route('finance.leaves.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('finance.leaves.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                    <i class="w-5 mr-3 text-center fas fa-calendar-alt"></i>
                    <span class="font-medium">Leave</span>
                </a>
                @endif

                @if(\App\Models\FeatureSetting::isActive('reimbursement'))
                <a href="{{ route('finance.reimbursements.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('finance.reimbursements.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                    <i class="w-5 mr-3 text-center fas fa-receipt"></i>
                    <span class="font-medium">Reimbursement</span>
                </a>
                @endif

                @if(\App\Models\FeatureSetting::isActive('overtime'))
                <a href="{{ route('finance.overtimes.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('finance.overtimes.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                    <i class="w-5 mr-3 text-center fas fa-clock"></i>
                    <span class="font-medium">Overtime</span>
                </a>
                @endif

                @if(\App\Models\FeatureSetting::isActive('perjalanan_dinas'))
                <a href="{{ route('finance.official-travels.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-all duration-200 {{ request()->routeIs('finance.official-travels.*') ? 'bg-primary-700 text-white shadow-soft' : 'text-primary-100 hover:bg-primary-700 hover:text-white' }}">
                    <i class="w-5 mr-3 text-center fas fa-plane"></i>
                    <span class="font-medium">Official Travel</span>
                </a>
                @endif
            </nav>

            <div class="p-4 border-t border-primary-700 bg-primary-900/60">
                <a class="mb-4 flex items-center rounded-xl border border-primary-600/80 bg-primary-700/60 p-3 transition-all duration-200 hover:bg-primary-700"
                    href="{{ route('finance.profile.index') }}">
                    @if(Auth::user()->url_profile)
                    <img class="mr-3 h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->url_profile }}"
                        alt="{{ Auth::user()->name }}">
                    @else
                    <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary-600">
                        <span class="text-sm font-semibold text-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                    </div>
                    @endif
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

        <!-- Overlay for mobile -->
        <div class="fixed inset-0 hidden bg-opacity-50 bg-black/20 lg:hidden" id="sidebar-overlay"></div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 min-w-0">
            <header class="bg-secondary-500 shadow-soft">
                <div class="flex h-20 items-center px-6">
                    <button class="mr-4 text-white lg:hidden hover:text-secondary-100" onclick="toggleSidebar()">
                        <i class="text-lg fas fa-bars"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-6 overflow-y-auto">
                @if(session('success'))
                <div
                    class="px-4 py-3 mb-6 border rounded-lg bg-success-50 border-success-200 text-success-800 shadow-soft">
                    <div class="flex items-center">
                        <i class="mr-2 fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="px-4 py-3 mb-6 border rounded-lg bg-error-50 border-error-200 text-error-800 shadow-soft">
                    <div class="flex items-center">
                        <i class="mr-2 fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <x-report-export.floating-panel
        :index-url="route('finance.report-exports.index')"
        :store-url="route('finance.report-exports.store')"
        :show-url-template="route('finance.report-exports.show', ['reportExport' => '__ID__'])"
        :download-url-template="route('finance.report-exports.download', ['reportExport' => '__ID__'])"
        :user-id="auth()->id() ?? 0"
        role-scope="finance"
    />

    <script>
        const btnNav = document.getElementById("btnNav");
    btnNav.addEventListener('click', toggleSidebar);

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.querySelector('[onclick="toggleSidebar()"]');
        if (window.innerWidth < 1024 &&
            !sidebar.contains(event.target) &&
            !sidebarToggle.contains(event.target) &&
            !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    });

    </script>
    @stack('scripts')
</body>

</html>

