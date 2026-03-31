@props([
    'name',
    'value' => '',
    'label' => '',
    'id' => null,
])

@php
    $inputId = $id ?: $name;
    $parts = explode(':', (string) $value);
    $selectedHour = isset($parts[0]) && preg_match('/^\d{1,2}$/', $parts[0])
        ? str_pad($parts[0], 2, '0', STR_PAD_LEFT)
        : '';
    $selectedMinute = isset($parts[1]) && preg_match('/^\d{1,2}$/', $parts[1])
        ? str_pad($parts[1], 2, '0', STR_PAD_LEFT)
        : '';
@endphp

<div>
    @if(filled($label))
        <label class="block mb-2 text-sm font-semibold text-slate-700" for="{{ $inputId }}_hour">
            {{ $label }}
        </label>
    @endif

    <div class="flex items-center gap-2">
        <select
            id="{{ $inputId }}_hour"
            name="{{ $name }}_hour"
            class="rounded-xl border border-slate-300 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm transition-all focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 hover:border-slate-400"
        >
            @foreach(range(0, 23) as $hour)
                @php($hourValue = str_pad((string) $hour, 2, '0', STR_PAD_LEFT))
                <option value="{{ $hourValue }}" @selected($selectedHour === $hourValue)>{{ $hourValue }}</option>
            @endforeach
        </select>

        <span class="text-slate-500 font-medium">:</span>

        <select
            id="{{ $inputId }}_minute"
            name="{{ $name }}_minute"
            class="rounded-xl border border-slate-300 bg-white py-2.5 px-4 text-sm text-slate-700 shadow-sm transition-all focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 hover:border-slate-400"
        >
            @foreach(range(0, 55, 5) as $minute)
                @php($minuteValue = str_pad((string) $minute, 2, '0', STR_PAD_LEFT))
                <option value="{{ $minuteValue }}" @selected($selectedMinute === $minuteValue)>{{ $minuteValue }}</option>
            @endforeach
        </select>
    </div>
</div>
