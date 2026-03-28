<div x-cloak x-show="loading"
    class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/40 px-4"
    x-transition.opacity>
    <div class="flex items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl">
        <span class="inline-flex h-10 w-10 animate-spin items-center justify-center rounded-full border-4 border-slate-200 border-t-sky-600"></span>
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ $title ?? 'Loading attendance data' }}</p>
            <p class="text-xs text-slate-500">{{ $description ?? 'Please wait while FlowHR prepares the next attendance view.' }}</p>
        </div>
    </div>
</div>
