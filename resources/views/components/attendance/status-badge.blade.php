@php
    $badge = $badge ?? [
        'label' => 'Unknown',
        'icon' => 'fa-solid fa-circle-question',
        'pill_classes' => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
    ];
@endphp

<span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $badge['pill_classes'] }}">
    <i class="{{ $badge['icon'] }} text-[11px]"></i>
    <span>{{ $badge['label'] }}</span>
</span>
