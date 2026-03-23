<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Role - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1d4ed8', // biru tua
                        secondary: '#dbeafe', // biru muda
                    }
                }
            }
        }
    </script>
    <style>
        .role-option input:checked+label .checkmark {
            border-color: #3b82f6;
            background-color: #3b82f6;
        }

        .role-option input:checked+label .checkmark i {
            display: block;
        }

        .role-option label .checkmark i {
            display: none;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen p-4 bg-gray-50">
    <div class="w-full max-w-md">
        <div class="p-8 bg-white border border-blue-100 shadow-lg rounded-xl">
            <!-- Logo / Icon -->
            <div class="mb-6 text-center">
                <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-blue-100 rounded-full">
                    <i class="text-2xl text-blue-600 fas fa-user-tag"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Pilih Role Anda</h1>
                <p class="mt-2 text-gray-600">Anda memiliki beberapa akses. Silakan pilih salah satu untuk melanjutkan.
                </p>
            </div>

            <!-- Form Pilih Role -->
            <form method="POST" action="{{ route('choose-role.store') }}" class="space-y-5">
                @csrf

                @foreach($roles as $role)
                <div class="relative role-option">
                    <input type="radio" name="role" id="role_{{ $loop->index }}" value="{{ $role->name }}"
                        class="sr-only peer" required />
                    <label for="role_{{ $loop->index }}"
                        class="flex items-center p-4 text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm cursor-pointer hover:bg-blue-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700">
                        <div
                            class="flex items-center justify-center w-6 h-6 mr-3 border border-gray-300 rounded-full checkmark">
                            <i class="text-xs text-white fas fa-check"></i>
                        </div>
                        <span class="font-medium capitalize">
                            {{ $roleLabels[$role->name] ?? str_replace('_', ' ', $role->name) }}
                        </span>
                    </label>
                </div>
                @endforeach

                <!-- Tombol Submit -->
                <div class="mt-6">
                    <button type="submit"
                        class="w-full px-4 py-3 font-semibold text-white transition duration-200 bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        Lanjutkan ke Dashboard
                    </button>
                </div>
            </form>

            <!-- Logout Link -->
            <div class="mt-6 text-center">
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="text-sm text-gray-500 transition hover:text-blue-600">
                    <i class="mr-1 fas fa-arrow-left"></i> Kembali ke login
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</body>

</html>