@php
    $state = $dashboardAttendanceState ?? [];
    $badge = $state['badge'] ?? [
        'label' => 'Configuration Issue',
        'icon' => 'fa-solid fa-gear',
        'pill_classes' => 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200',
        'surface_classes' => 'border-red-200 bg-red-50',
    ];
    $flags = is_array($state['flags'] ?? null) ? $state['flags'] : [];
    $description = $state['description'] ?? 'Attendance status is unavailable.';
    $dateLabel = $state['date_label'] ?? '-';
    $checkIn = $state['check_in'] ?? '-';
    $checkOut = $state['check_out'] ?? '-';
@endphp

<section class="mb-6 rounded-2xl border {{ $badge['surface_classes'] }} p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">State Absensi Sekarang</p>
                <span class="text-xs text-slate-500">{{ $dateLabel }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @include('components.attendance.status-badge', ['badge' => $badge])
                @foreach($flags as $flag)
                    @include('components.attendance.status-badge', ['badge' => $flag])
                @endforeach
            </div>

            <p class="text-sm text-slate-600">{{ $description }}</p>
        </div>

        <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Check In</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ $checkIn }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Check Out</p>
                <p class="mt-1 text-lg font-bold text-slate-900">{{ $checkOut }}</p>
            </div>
        </div>
    </div>
</section>

