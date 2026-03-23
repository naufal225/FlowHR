<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Approval')</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header - Secondary Sky Blue (20%) -->
    <header class="border-b shadow-sm bg-sky-100 border-sky-200">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <i class="mr-3 text-2xl fas fa-check-circle text-sky-600"></i>
                    <h1 class="text-xl font-semibold text-gray-800">Approval System</h1>
                </div>
                <div class="text-sm text-gray-600">
                    <i class="mr-1 fas fa-shield-alt"></i>
                    Secure Link
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="px-4 py-3 mb-6 text-green-700 border border-green-200 rounded-lg bg-green-50">
                    <i class="mr-2 fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="px-4 py-3 mb-6 text-red-700 border border-red-200 rounded-lg bg-red-50">
                    <i class="mr-2 fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="px-4 py-3 mb-6 text-red-700 border border-red-200 rounded-lg bg-red-50">
                    <i class="mr-2 fas fa-exclamation-triangle"></i>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto bg-white border-t border-gray-200">
        <div class="px-4 py-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="text-sm text-center text-gray-500">
                <i class="mr-1 fas fa-lock"></i>
                This is a secure approval link. Do not share with others.
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
