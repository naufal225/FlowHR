<div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p @if(!empty($valueId)) id="{{ $valueId }}" @endif class="mt-1 text-sm font-medium text-slate-900">{{ $value }}</p>
    @if(!empty($helper))
        <p @if(!empty($helperId)) id="{{ $helperId }}" @endif class="mt-1 text-xs text-slate-500">{{ $helper }}</p>
    @endif
</div>

