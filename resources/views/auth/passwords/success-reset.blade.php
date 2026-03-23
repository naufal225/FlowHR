<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful - {{ config('app.name') }}</title>

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

        .btn-primary {
            @apply inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white transition-all duration-200 transform rounded-lg shadow-lg bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500;
        }

        .btn-secondary {
            @apply inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-neutral-700 transition-colors duration-200 bg-white border border-neutral-300 rounded-lg hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500;
        }

        .animate-bounce-slow {
            animation: bounce 2s infinite;
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-scale-in {
            animation: scaleIn 0.6s ease-out;
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-success-50 to-neutral-100">
    <div class="flex items-center justify-center min-h-screen px-4 py-12">
        <div class="w-full max-w-md animate-fade-in">
            <!-- Success Card -->
            <div class="bg-white border rounded-xl shadow-soft border-neutral-200 animate-scale-in">
                <div class="p-8 text-center">
                    <!-- Success Icon -->
                    <div class="inline-flex items-center justify-center w-20 h-20 mb-6 rounded-full bg-success-100 animate-bounce-slow">
                        <i class="text-3xl fas fa-check text-success-600"></i>
                    </div>

                    <!-- Success Message -->
                    <h1 class="mb-4 text-3xl font-bold text-neutral-900">Password Reset Successful!</h1>
                    <p class="mb-6 text-neutral-600">
                        Your password has been successfully updated. You can now use your new password to sign in to your account.
                    </p>

                    <!-- Security Notice -->
                    <div class="p-4 mb-6 border rounded-lg bg-primary-50 border-primary-200">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shield-alt text-primary-600"></i>
                            </div>
                            <div class="ml-3 text-left">
                                <h3 class="text-sm font-medium text-primary-800">Security Tip</h3>
                                <p class="mt-1 text-sm text-primary-700">
                                    For your security, we recommend signing out of all other devices and clearing your browser cache.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <a href="{{ route('login') }}" class="w-full btn-primary">
                            <i class="mr-2 fas fa-sign-in-alt"></i>
                            Sign In Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-6 text-center">
                <div class="p-4 border rounded-lg bg-white/50 backdrop-blur-sm border-neutral-200">
                    <h3 class="mb-2 text-sm font-medium text-neutral-900">What happens next?</h3>
                    <ul class="space-y-1 text-sm text-neutral-600">
                        <li class="flex items-center justify-center">
                            <i class="mr-2 fas fa-check-circle text-success-500"></i>
                            Your old password is no longer valid
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="mr-2 fas fa-check-circle text-success-500"></i>
                            All active sessions have been terminated
                        </li>
                        <li class="flex items-center justify-center">
                            <i class="mr-2 fas fa-check-circle text-success-500"></i>
                            You'll need to sign in with your new password
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

    <!-- Confetti Animation (Optional) -->
    <div id="confetti" class="fixed inset-0 z-10 pointer-events-none"></div>

    <script>
        // Auto redirect after 10 seconds (optional)
        let countdown = 10;
        const redirectTimer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(redirectTimer);
                window.location.href = '{{ route('login') }}';
            }
        }, 1000);

        // Simple confetti effect
        function createConfetti() {
            const confettiContainer = document.getElementById('confetti');
            const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

            for (let i = 0; i < 50; i++) {
                const confettiPiece = document.createElement('div');
                confettiPiece.style.position = 'absolute';
                confettiPiece.style.width = '10px';
                confettiPiece.style.height = '10px';
                confettiPiece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confettiPiece.style.left = Math.random() * 100 + '%';
                confettiPiece.style.top = '-10px';
                confettiPiece.style.borderRadius = '50%';
                confettiPiece.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;

                confettiContainer.appendChild(confettiPiece);

                setTimeout(() => {
                    confettiPiece.remove();
                }, 5000);
            }
        }

        // Add CSS for confetti animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Trigger confetti on page load
        setTimeout(createConfetti, 500);

        // Show success message in console
        console.log('🎉 Password reset successful!');
    </script>
</body>
</html>
