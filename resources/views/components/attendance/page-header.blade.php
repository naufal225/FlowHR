<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="space-y-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
            @if(!empty($subtitle))
                <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
            @endif
        </div>
        @if(!empty($backHref))
            <a href="{{ $backHref }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-sky-700 transition hover:text-sky-800">
                <i class="fa-solid fa-arrow-left"></i>
                <span>{{ $backLabel ?? 'Back' }}</span>
            </a>
        @endif
    </div>
    @if(!empty($sideMeta))
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $sideMeta['label'] ?? 'Info' }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $sideMeta['value'] ?? '-' }}</p>
        </div>
    @endif
</div>
