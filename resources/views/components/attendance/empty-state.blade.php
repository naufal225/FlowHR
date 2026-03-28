<div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm">
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <i class="{{ $icon ?? 'fa-solid fa-box-open' }} text-xl"></i>
    </div>
    <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $title }}</h3>
    <p class="mt-2 text-sm text-slate-600">{{ $description }}</p>
    @if(!empty($actionHref) && !empty($actionLabel))
        <a href="{{ $actionHref }}"
            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700">
            <i class="fa-solid fa-arrow-right"></i>
            <span>{{ $actionLabel }}</span>
        </a>
    @endif
</div>
