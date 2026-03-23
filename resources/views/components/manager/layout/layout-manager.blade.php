<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('yaztech-icon.jpg') }}">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>[x-cloak]{display:none !important}</style>

    @stack('styles')
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar - Primary Blue (35%) -->
        @include('components.manager.sidebar')

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col flex-1 overflow-hidden lg:ml-0">
            <!-- Header - Secondary Sky Blue (20%) -->
            @include('components.manager.header')

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
        document.addEventListener('DOMContentLoaded', () => {
            const leaveNav = document.getElementById('leave-nav');
            const officialTravelNav = document.getElementById('official-travel-nav');
            const overtimeNav = document.getElementById('overtime-nav');
            const reimbursementNav = document.getElementById('reimbursement-nav');
            const badgeLeave = document.getElementById('leave-badge');
            const badgeTravel = document.getElementById('official-travel-badge');
            const badgeOvertime = document.getElementById('overtime-badge');
            const badgeReimbursement = document.getElementById('reimbursement-badge');

            if (!leaveNav || !officialTravelNav || !reimbursementNav || !overtimeNav || !window.Echo) return;

            let roles = [];
            try { roles = JSON.parse(leaveNav.dataset.roles || '[]'); } catch (e) { roles = []; }
            const divisionId = leaveNav.dataset.divisionId;

            function incrementBadge(badgeElement) {
                if (!badgeElement) return;
                let current = parseInt(badgeElement.textContent) || 0;
                badgeElement.textContent = current + 1;
                badgeElement.style.display = 'inline-flex';
            }
           if (roles.includes('approver') || roles.includes('manager')) {
                window.Echo.private(`manager.approval`)
                    .listen('.leave.level-advanced', (e) => {
                        console.log('[Echo] leave.level-advanced received', e);
                        incrementBadge(badgeLeave);
                    })
                    .listen('.official-travel.level-advanced', (e) => {
                        console.log('[Echo] official-travel.level-advanced received', e);
                        incrementBadge(badgeTravel);
                    })
                    .listen('.overtime.level-advanced', (e) => {
                        console.log('[Echo] overtime.level-advanced received', e);
                        incrementBadge(badgeOvertime);
                    })
                    .listen('.reimbursement.level-advanced', (e) => {
                        console.log('[Echo] reimbursement.level-advanced received', e);
                        incrementBadge(badgeReimbursement);
                    });
            }

        });
    </script>

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
    </script>
</body>

</html>
