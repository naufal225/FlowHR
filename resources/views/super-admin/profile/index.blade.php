@extends('components.super-admin.layout.layout-super-admin')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    @if(session('success'))
    <div class="flex items-center p-4 my-6 border border-green-200 bg-green-50 rounded-xl">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="flex items-start p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3">
            <h4 class="text-sm font-medium text-red-800">Please correct the following errors:</h4>
            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif



    <div class="mb-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="px-6 py-8">
            <div class="flex items-center space-x-6">
                <!-- Profile Photo -->
                <div class="relative">
                    @if(auth()->user()->url_profile)
                    <img src="{{ auth()->user()->url_profile }}" alt="{{ auth()->user()->name }}"
                        class="object-cover w-24 h-24 border-4 border-blue-100 rounded-full">
                    @else
                    <div
                        class="flex items-center justify-center w-24 h-24 bg-blue-500 border-4 border-blue-100 rounded-full">
                        <span class="text-2xl font-semibold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                    </div>
                    @endif
                </div>

                <!-- User Info -->
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ auth()->user()->name }}</h1>
                    <div class="mt-1 space-y-2">
                        <label class="text-sm font-semibold text-neutral-700">Email</label>
                        <div class="flex items-center p-3 border rounded-lg bg-neutral-50 border-neutral-200"
                            x-data="{ tooltip: false }">
                            <i class="flex-shrink-0 mr-3 fas fa-envelope text-primary-600"></i>
                            <span class="font-medium truncate text-neutral-900" @mouseenter="tooltip = true"
                                @mouseleave="tooltip = false" x-tooltip="'{{ auth()->user()->email }}'">
                                {{ auth()->user()->email }}
                            </span>
                            <div x-show="tooltip" x-cloak
                                class="absolute px-3 py-2 -mt-12 text-sm text-white bg-gray-900 rounded-lg shadow-lg">
                                {{ auth()->user()->email }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ auth()->user()->roles->map(fn($role) =>
                            \App\Enums\Roles::from($role->name)->label())->join(', ') }}</span>
                    </div>
                </div>

                <!-- Edit Button -->
                <div>
                    <button onclick="openEditModal()"
                        class="px-4 py-2 font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-edit"></i>Edit Profile
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Details -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <!-- Account Information -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <p class="mt-1 text-sm text-gray-900">{{ auth()->user()->roles->map(fn($role) =>
                        \App\Enums\Roles::from($role->name)->label())->join(', ') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Member Since</label>
                    <p class="mt-1 text-sm text-gray-900">{{ optional(auth()->user()->created_at)->format('F j, Y') ??
                        '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Security Settings</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <p class="mt-1 text-sm text-gray-900">••••••••••••</p>
                </div>
                <div>
                    <button onclick="openPasswordModal()"
                        class="px-4 py-2 font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="mr-2 fas fa-key"></i>Change Password
                    </button>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                    <p class="mt-1 text-sm text-gray-900">{{ optional(auth()->user()->updated_at)->format('F j, Y g:i
                        A') ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="fixed inset-0 z-50 hidden bg-black/20">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md bg-white rounded-lg shadow-xl">
            <form action="{{ route('super-admin.profile.update', Auth::id()) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Profile</h3>
                </div>

                <div class="px-6 py-4 space-y-4">
                    <!-- Profile Photo Upload -->
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Profile Photo</label>
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                @if(auth()->user()->url_profile)
                                <img id="preview-image" src="{{ auth()->user()->url_profile }}" alt="Preview"
                                    class="object-cover w-16 h-16 rounded-full">
                                @else
                                <div id="preview-placeholder"
                                    class="flex items-center justify-center w-16 h-16 bg-blue-500 rounded-full">
                                    <span class="text-lg font-semibold text-white">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                                @endif
                            </div>
                            <input type="file" name="profile_photo" id="profile_photo" accept="image/*"
                                class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name" value="{{ auth()->user()->name }}"
                            class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ auth()->user()->email }}"
                            class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-gray-100 rounded-lg hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="fixed inset-0 z-50 hidden bg-black/20 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md bg-white shadow-2xl rounded-xl">
            <form action="{{ route('super-admin.profile.password') }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="p-2 mr-3 bg-blue-100 rounded-lg">
                            <i class="text-blue-600 fas fa-key"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Ganti Password</h3>
                            <p class="text-sm text-gray-600">Pastikan password baru Anda kuat dan aman</p>
                        </div>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="px-6 py-4 space-y-5">
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block mb-1 text-sm font-medium text-gray-700">
                            <i class="mr-1 text-gray-500 fas fa-lock"></i>
                            Password Saat Ini
                        </label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password"
                                class="block w-full px-4 py-3 transition-colors border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan password Anda saat ini" required>
                            <button type="button" class="absolute text-gray-500 right-3 top-3 hover:text-gray-700"
                                onclick="togglePassword('current_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block mb-1 text-sm font-medium text-gray-700">
                            <i class="mr-1 text-gray-500 fas fa-lock"></i>
                            Password Baru
                        </label>
                        <div class="relative">
                            <input type="password" name="new_password" id="new_password"
                                class="block w-full px-4 py-3 transition-colors border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Buat password baru yang kuat" required>
                            <button type="button" class="absolute text-gray-500 right-3 top-3 hover:text-gray-700"
                                onclick="togglePassword('new_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="new_password_confirmation" class="block mb-1 text-sm font-medium text-gray-700">
                            <i class="mr-1 text-gray-500 fas fa-redo-alt"></i>
                            Konfirmasi Password Baru
                        </label>
                        <div class="relative">
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                                class="block w-full px-4 py-3 transition-colors border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Masukkan ulang password baru untuk konfirmasi" required>
                            <button type="button" class="absolute text-gray-500 right-3 top-3 hover:text-gray-700"
                                onclick="togglePassword('new_password_confirmation', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="flex items-center mt-2 text-xs text-gray-500">
                            <i class="mr-1 text-blue-500 fas fa-info-circle"></i>
                            Masukkan ulang password baru untuk memastikan tidak ada kesalahan ketik
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end px-6 py-4 space-x-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                    <button type="button" onclick="closePasswordModal()"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm flex items-center">
                        <i class="mr-2 fas fa-save"></i>
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Modal functions
    function openEditModal() {
        document.getElementById('editProfileModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editProfileModal').classList.add('hidden');
    }

    function openPasswordModal() {
        document.getElementById('passwordModal').classList.remove('hidden');
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').classList.add('hidden');
        // Clear password fields
        document.getElementById('current_password').value = '';
        document.getElementById('new_password').value = '';
        document.getElementById('new_password_confirmation').value = '';
    }

    // Image preview
    document.getElementById('profile_photo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('preview-image');
                const placeholder = document.getElementById('preview-placeholder');

                if (preview) {
                    preview.src = e.target.result;
                } else if (placeholder) {
                    placeholder.outerHTML = `<img id="preview-image" src="${e.target.result}" alt="Preview" class="object-cover w-16 h-16 rounded-full">`;
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Close modals when clicking outside
    document.getElementById('editProfileModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });

    document.getElementById('passwordModal').addEventListener('click', function(e) {
        if (e.target === this) closePasswordModal();
    });

      function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function openPasswordModal() {
        document.getElementById('passwordModal').classList.remove('hidden');
    }

    function closePasswordModal() {
        document.getElementById('passwordModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
