<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - {{ config('app.name') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('yaztech-icon.jpg') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        error: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        .shadow-soft {
            box-shadow: 0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04);
        }

        .form-input {
            @apply w-full px-4 py-3 text-sm border border-neutral-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors duration-200;
        }

        .btn-primary {
            @apply inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none;
        }

        .btn-secondary {
            @apply inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-neutral-700 transition-colors duration-200 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500;
        }

        .input-error {
            @apply border-error-500 focus:ring-error-500 focus:border-error-500;
        }

        .error-message {
            @apply text-sm text-error-600 mt-1;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-neutral-50 to-neutral-100">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="mb-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-primary-100">
                    <i class="text-2xl fas fa-lock text-primary-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-neutral-900">Reset Password</h1>
                <p class="mt-2 text-neutral-600">Enter your new password to secure your account</p>
            </div>

            <!-- Reset Password Form -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
                <div class="p-8">
                    <form id="resetPasswordForm" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                        <!-- Email Display -->
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-neutral-700">
                                Email Address
                            </label>
                            <div class="flex items-center px-4 py-3 text-sm border rounded-lg bg-neutral-50 border-neutral-300">
                                <i class="mr-3 fas fa-envelope text-neutral-400"></i>
                                <span class="text-neutral-700">{{ $email ?? old('email') }}</span>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-6">
                            <label for="password" class="block mb-2 text-sm font-medium text-neutral-700">
                                New Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                    <i class="fas fa-lock text-neutral-400"></i>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-input pl-12 {{ $errors->has('password') ? 'input-error' : '' }}"
                                    placeholder="Enter your new password"
                                    required
                                    minlength="8"
                                >
                                <button
                                    type="button"
                                    id="togglePassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-neutral-400 hover:text-neutral-600"
                                >
                                    <i class="fas fa-eye" id="passwordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 text-xs text-neutral-500">
                                Password must be at least 8 characters long
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-6">
                            <label for="password_confirmation" class="block mb-2 text-sm font-medium text-neutral-700">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4">
                                    <i class="fas fa-lock text-neutral-400"></i>
                                </div>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="form-input pl-12 {{ $errors->has('password_confirmation') ? 'input-error' : '' }}"
                                    placeholder="Confirm your new password"
                                    required
                                    minlength="8"
                                >
                                <button
                                    type="button"
                                    id="toggleConfirmPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-neutral-400 hover:text-neutral-600"
                                >
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="error-message">{{ $message }}</p>
                            @enderror
                            <div id="passwordMatch" class="hidden mt-2 text-xs">
                                <span id="matchMessage"></span>
                            </div>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-neutral-700">Password Strength</span>
                                <span id="strengthText" class="text-xs text-neutral-500">Weak</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-neutral-200">
                                <div id="strengthBar" class="h-2 transition-all duration-300 rounded-full bg-error-500" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="submitBtn" class="w-full btn-primary">
                            <span id="submitText">Reset Password</span>
                            <svg id="submitSpinner" class="hidden w-4 h-4 ml-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700">
                    <i class="mr-2 fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed z-50 hidden top-4 right-4">
        <div id="toastContent" class="px-6 py-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <span id="toastMessage"></span>
                <button onclick="hideToast()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Toast functionality
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastContent = document.getElementById('toastContent');
            const toastMessage = document.getElementById('toastMessage');

            toastMessage.textContent = message;

            if (type === 'success') {
                toastContent.className = 'px-6 py-4 rounded-lg shadow-lg bg-success-500 text-white';
            } else {
                toastContent.className = 'px-6 py-4 rounded-lg shadow-lg bg-error-500 text-white';
            }

            toast.classList.remove('hidden');

            setTimeout(() => {
                hideToast();
            }, 5000);
        }

        function hideToast() {
            document.getElementById('toast').classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordIcon = document.getElementById('passwordIcon');
            const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            const passwordMatch = document.getElementById('passwordMatch');
            const matchMessage = document.getElementById('matchMessage');
            const form = document.getElementById('resetPasswordForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');

            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                passwordIcon.classList.toggle('fa-eye');
                passwordIcon.classList.toggle('fa-eye-slash');
            });

            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                confirmPasswordIcon.classList.toggle('fa-eye');
                confirmPasswordIcon.classList.toggle('fa-eye-slash');
            });

            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let strengthLabel = 'Weak';
                let strengthColor = 'bg-error-500';
                let strengthWidth = '25%';

                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;

                switch (strength) {
                    case 0:
                    case 1:
                        strengthLabel = 'Weak';
                        strengthColor = 'bg-error-500';
                        strengthWidth = '25%';
                        break;
                    case 2:
                        strengthLabel = 'Fair';
                        strengthColor = 'bg-warning-500';
                        strengthWidth = '50%';
                        break;
                    case 3:
                        strengthLabel = 'Good';
                        strengthColor = 'bg-primary-500';
                        strengthWidth = '75%';
                        break;
                    case 4:
                    case 5:
                        strengthLabel = 'Strong';
                        strengthColor = 'bg-success-500';
                        strengthWidth = '100%';
                        break;
                }

                strengthText.textContent = strengthLabel;
                strengthBar.className = `h-2 rounded-full transition-all duration-300 ${strengthColor}`;
                strengthBar.style.width = strengthWidth;

                checkPasswordMatch();
            });

            // Password match checker
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword.length > 0) {
                    passwordMatch.classList.remove('hidden');

                    if (password === confirmPassword) {
                        matchMessage.textContent = 'Passwords match';
                        matchMessage.className = 'text-success-600';
                    } else {
                        matchMessage.textContent = 'Passwords do not match';
                        matchMessage.className = 'text-error-600';
                    }
                } else {
                    passwordMatch.classList.add('hidden');
                }
            }

            // Form submission
            form.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (password !== confirmPassword) {
                    e.preventDefault();
                    showToast('Passwords do not match', 'error');
                    return;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    showToast('Password must be at least 8 characters long', 'error');
                    return;
                }

                // Show loading state
                submitText.textContent = 'Resetting Password...';
                submitSpinner.classList.remove('hidden');
                submitBtn.disabled = true;
            });

            // Show validation errors if any
            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast('{{ $error }}', 'error');
                @endforeach
            @endif
        });
    </script>
</body>
</html>
