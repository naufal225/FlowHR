<div class="rounded-2xl border px-5 py-4 {{ $classes ?? 'border-slate-200 bg-slate-50' }}">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-xl {{ $iconClasses ?? 'bg-slate-100 text-slate-700' }}">
            <i class="{{ $icon ?? 'fa-solid fa-circle-info' }}"></i>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                @if(!empty($badge))
                    @include('components.attendance.status-badge', ['badge' => $badge])
                @endif
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $description }}</p>
        </div>
    </div>
</div>
