<div class="space-y-4">
    @forelse($logs as $log)
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-slate-900">{{ $log['type'] }}</p>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $log['status']['pill_classes'] }}">
                            {{ $log['status']['label'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ $log['message'] }}</p>
                </div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $log['occurred_at'] }}</p>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3 {{ !empty($showSensitive) ? 'lg:grid-cols-5' : 'lg:grid-cols-3' }}">
                @include('components.attendance.info-item', ['label' => 'Latitude', 'value' => $log['latitude']])
                @include('components.attendance.info-item', ['label' => 'Longitude', 'value' => $log['longitude']])
                @include('components.attendance.info-item', ['label' => 'Accuracy', 'value' => $log['accuracy']])
                @if(!empty($showSensitive))
                    @include('components.attendance.info-item', ['label' => 'IP Address', 'value' => $log['ip_address']])
                    @include('components.attendance.info-item', ['label' => 'Device', 'value' => $log['device_info']])
                @endif
            </div>
        </div>
    @empty
        @include('components.attendance.empty-state', [
            'title' => 'No activity logs',
            'description' => 'No attendance log entries were recorded for this attendance record.',
            'icon' => 'fa-solid fa-timeline',
        ])
    @endforelse
</div>
