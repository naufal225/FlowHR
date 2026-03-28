<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>
            @if(!empty($caption))
                <p class="mt-2 text-xs text-slate-500">{{ $caption }}</p>
            @endif
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $iconContainerClasses ?? 'bg-slate-100 text-slate-700' }}">
            <i class="{{ $icon }}"></i>
        </div>
    </div>
</div>
