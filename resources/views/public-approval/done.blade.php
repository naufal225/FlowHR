@extends('components.public.layout')

@section('title', 'Approval Complete')

@section('content')
<div class="max-w-lg mx-auto text-center">
    <!-- Success Card -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <!-- Success Icon -->
        <div class="px-6 py-8 bg-gradient-to-r from-green-50 to-emerald-50">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full">
                <i class="text-2xl text-green-600 fas fa-check"></i>
            </div>
            <h1 class="mb-2 text-2xl font-bold text-gray-900">Approval Complete!</h1>
            <p class="text-gray-600">Your approval action has been successfully processed.</p>
        </div>

        <!-- Details -->
        <div class="px-6 py-6">
            <div class="space-y-4">
                <div class="flex items-center justify-center text-sm text-gray-600">
                    <i class="mr-2 fas fa-clock"></i>
                    Processed at: {{ now()->format('d M Y, H:i') }}
                </div>

                <div class="p-4 rounded-lg bg-gray-50">
                    <div class="flex items-center justify-center text-sm">
                        <i class="mr-2 text-green-600 fas fa-shield-check"></i>
                        <span class="text-gray-700">This approval link has been used and is now inactive</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <p class="mb-4 text-xs text-gray-500">
                The relevant parties have been notified of your decision.
                You may now close this window.
            </p>

            <button
                onclick="window.close()"
                class="w-full px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
                <i class="mr-2 fas fa-times"></i>
                Close Window
            </button>
        </div>
    </div>

    <!-- Additional Information -->
    <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
            <div class="text-sm text-left text-blue-800">
                <p class="mb-1 font-medium">What happens next?</p>
                <ul class="space-y-1 text-blue-700 list-disc list-inside">
                    <li>The requester will be notified of your decision</li>
                    <li>If approved, the request will proceed to the next step</li>
                    <li>All actions are logged for audit purposes</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-close window after 30 seconds (optional)
    setTimeout(function() {
        if (confirm('Would you like to close this window automatically?')) {
            window.close();
        }
    }, 30000);

    // Handle browser back button
    window.addEventListener('popstate', function(event) {
        alert('This approval has already been processed. You cannot go back.');
        history.pushState(null, null, window.location.href);
    });

    // Prevent page refresh
    window.addEventListener('beforeunload', function(event) {
        event.returnValue = 'This approval has been completed. Are you sure you want to leave?';
    });
</script>
@endpush
