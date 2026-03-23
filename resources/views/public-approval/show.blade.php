@extends('components.public.layout')

@section('title', 'Review Request #' . $subject->id)

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Request Details Card -->
    <div class="mb-6 overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-sky-50">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="mb-1 text-2xl font-bold text-gray-900">
                        <i class="mr-2 text-blue-600 fas fa-file-alt"></i>
                        Review Request #{{ $subject->id }}
                    </h1>
                    <p class="text-sm text-gray-600">
                        <i class="mr-1 fas fa-clock"></i>
                        Submitted: {{ $subject->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-yellow-800 bg-yellow-100 rounded-full">
                        <i class="mr-1 fas fa-hourglass-half"></i>
                        Pending Review
                    </span>
                </div>
            </div>
        </div>

        <!-- Request Information -->
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Pengaju</label>
                        <p class="font-medium text-gray-900">
                            <i class="mr-2 text-gray-400 fas fa-user"></i>
                            {{ $subject->employee->name ?? 'N/A' }}
                        </p>
                    </div>

                    @if(isset($subject->date_start) && isset($subject->date_end))
                    <div>
                        <label class="text-sm font-medium text-gray-500">Periode</label>
                        <p class="text-gray-900">
                            <i class="mr-2 text-gray-400 fas fa-calendar"></i>
                            {{ \Carbon\Carbon::parse($subject->date_start)->format('d M Y') }} –
                            {{ \Carbon\Carbon::parse($subject->date_end)->format('d M Y') }}
                        </p>
                    </div>
                    @endif
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Tipe Request</label>
                        <p class="font-medium text-gray-900">
                            <i class="mr-2 text-gray-400 fas fa-tag"></i>
                            {{ class_basename($subject) }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-500">Level Approval</label>
                        <p class="text-gray-900">
                            <i class="mr-2 text-gray-400 fas fa-layer-group"></i>
                            Level {{ $link->level }}
                        </p>
                    </div>
                </div>
            </div>

            @if(isset($subject->description) && $subject->description)
            <div class="mb-6">
                <label class="block mb-2 text-sm font-medium text-gray-500">Deskripsi</label>
                <div class="p-4 rounded-lg bg-gray-50">
                    <p class="text-gray-700 whitespace-pre-wrap">{{ $subject->description }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Approval Form -->
    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="mr-2 text-green-600 fas fa-clipboard-check"></i>
                Approval Action
            </h2>
            <p class="mt-1 text-sm text-gray-600">Please review the request and provide your decision</p>
        </div>

        <form method="POST" action="{{ route('public.approval.act', $rawToken) }}" class="p-6">
            @csrf

            <!-- Note Field -->
            <div class="mb-6">
                <label for="note" class="block mb-2 text-sm font-medium text-gray-700">
                    <i class="mr-1 fas fa-comment-alt"></i>
                    Catatan (Opsional)
                </label>
                <textarea
                    name="note"
                    id="note"
                    rows="4"
                    class="w-full px-3 py-2 transition-colors border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Berikan catatan atau alasan untuk keputusan Anda..."
                >{{ old('note') }}</textarea>
                @error('note')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @if($canApprove)
                <button
                    name="action"
                    value="approved"
                    type="submit"
                    class="flex items-center justify-center px-6 py-3 font-semibold text-white transition-all duration-200 transform bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-200 hover:scale-105"
                    onclick="return confirm('Apakah Anda yakin ingin menyetujui request ini?')"
                >
                    <i class="mr-2 fas fa-check"></i>
                    Approve Request
                </button>
                @endif

                @if($canReject)
                <button
                    name="action"
                    value="rejected"
                    type="submit"
                    class="flex items-center justify-center px-6 py-3 font-semibold text-white transition-all duration-200 transform bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-200 hover:scale-105"
                    onclick="return confirm('Apakah Anda yakin ingin menolak request ini?')"
                >
                    <i class="mr-2 fas fa-times"></i>
                    Reject Request
                </button>
                @endif
            </div>

            @if(!$canApprove && !$canReject)
            <div class="py-8 text-center">
                <i class="mb-3 text-3xl text-yellow-500 fas fa-exclamation-triangle"></i>
                <p class="text-gray-600">No actions available for this approval link.</p>
            </div>
            @endif
        </form>
    </div>

    <!-- Security Notice -->
    <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
            <div class="text-sm text-blue-800">
                <p class="mb-1 font-medium">Security Notice:</p>
                <ul class="space-y-1 text-blue-700 list-disc list-inside">
                    <li>Link ini unik dan memiliki masa berlaku terbatas</li>
                    <li>Link akan nonaktif setelah digunakan</li>
                    <li>Jangan bagikan link ini kepada orang lain</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-resize textarea
    document.getElementById('note').addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const action = e.submitter.value;
        const note = document.getElementById('note').value.trim();

        if (action === 'rejected' && !note) {
            if (!confirm('Anda akan menolak request tanpa memberikan catatan. Apakah Anda yakin?')) {
                e.preventDefault();
                return false;
            }
        }
    });
</script>
@endpush
