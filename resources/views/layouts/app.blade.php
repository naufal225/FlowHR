<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'FlowHR'))</title>
    @vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.20/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="icon" type="image/x-icon" href="{{ asset('FlowHR_logo.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 3px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.3); }
    </style>
    @stack('styles')
</head>

<body class="h-screen overflow-hidden font-sans antialiased bg-neutral-50">
    <div class="flex h-full">
        @include('layouts._sidebar')

        <!-- Overlay for mobile -->
        <div class="fixed inset-0 z-40 hidden bg-black/20 lg:hidden" id="sidebar-overlay"></div>

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col flex-1 overflow-hidden lg:ml-0">
            <!-- Header -->
            <header class="bg-secondary-500 shadow-soft">
                <div class="flex h-20 items-center px-6">
                    <button id="sidebar-toggle"
                        class="mr-4 text-white hover:text-sky-200 focus:outline-none lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="flex-1">
                        @hasSection('header')
                        <h1 class="text-lg font-semibold text-white">@yield('header')</h1>
                        @if(View::hasSection('subtitle'))
                        <p class="text-sm text-sky-200">@yield('subtitle')</p>
                        @endif
                        @endif
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
                @if(session('success'))
                <div class="px-4 py-3 mb-6 border rounded-lg bg-success-50 border-success-200 text-success-800 shadow-soft">
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

    @yield('partial-modal')

    @if(!config('reporting.legacy_export_enabled'))
    <style>
        #exportReimbursementRequests,
        #exportOvertimesData,
        #exportOfficialTravelsData { display: none !important; }
    </style>
    @endif

    @php
        $reportScope = Auth::user()->hasRole('superAdmin') ? 'super-admin'
            : (Auth::user()->hasRole('admin') ? 'admin'
            : (Auth::user()->hasRole('finance') ? 'finance' : null));
    @endphp
    @if($reportScope && \Illuminate\Support\Facades\Route::has($reportScope . '.report-exports.index'))
    <x-report-export.floating-panel
        :index-url="route($reportScope . '.report-exports.index')"
        :store-url="route($reportScope . '.report-exports.store')"
        :show-url-template="route($reportScope . '.report-exports.show', ['reportExport' => '__ID__'])"
        :download-url-template="route($reportScope . '.report-exports.download', ['reportExport' => '__ID__'])"
        :user-id="auth()->id() ?? 0"
        :role-scope="$reportScope"
    />
    @endif

    @include('components.default-hidden-sync')

    <script>
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            if (isOpen) {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }

        document.addEventListener('click', function (event) {
            if (!sidebar || !sidebarToggle) return;
            const isMobile = window.innerWidth < 1024;
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target) && isMobile && isOpen) {
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024 && sidebarOverlay) {
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });
    </script>

    @stack('scripts')
</body>

</html>
