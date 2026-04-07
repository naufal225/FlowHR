<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    @vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('FlowHR_logo.png') }}">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak]{display:none !important}
        /* Custom scrollbar untuk sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            position: absolute;
            right: 0;
            width: 6px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
    @stack('styles')
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar - Primary Blue (35%) -->
        @include('components.admin.sidebar')

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col flex-1 overflow-hidden lg:ml-0">
            <!-- Header - Secondary Sky Blue (20%) -->
            @include('components.admin.header')

            <!-- Dashboard Content -->
            <main class="relative z-10 flex-1 p-6 overflow-x-hidden overflow-y-auto bg-gray-50">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Sidebar Overlay for Mobile - Fixed positioning -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-black/20 lg:hidden"></div>

    @yield('partial-modal')

    @stack('scripts')
    @include('components.default-hidden-sync')

    <script>
        // Sidebar Toggle Functionality - Fixed
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            const isSidebarOpen = !sidebar.classList.contains('-translate-x-full');

            if (isSidebarOpen) {
                // Close sidebar
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                // Open sidebar
                sidebar.classList.remove('-translate-x-full');
                sidebarOverlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Event listeners
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });

        sidebarOverlay.addEventListener('click', function(e) {
            e.stopPropagation();
            closeSidebar();
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = sidebarToggle.contains(event.target);
            const isMobile = window.innerWidth < 1024;
            const isSidebarOpen = !sidebar.classList.contains('-translate-x-full');

            if (!isClickInsideSidebar && !isClickOnToggle && isMobile && isSidebarOpen) {
                closeSidebar();
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                // Desktop view - ensure overlay is hidden and body scroll is enabled
                sidebarOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        function toggleDropdown(id) {
        const dropdown = document.getElementById(id);
        const icon = document.getElementById(id + '-icon');
        const isOpen = dropdown.classList.contains('max-h-40');

        if (isOpen) {
            dropdown.classList.remove('max-h-40');
            dropdown.classList.add('max-h-0');
            icon.classList.remove('rotate-180');
        } else {
            dropdown.classList.remove('max-h-0');
            dropdown.classList.add('max-h-40');
            icon.classList.add('rotate-180');
        }
    }

    </script>
</body>

</html>
