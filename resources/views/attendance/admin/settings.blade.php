@extends($layout)

@section('title', 'Attendance Settings')
@section('header', 'Attendance Settings')
@section('subtitle', 'Configure attendance policy per office')

@section('content')
<div class="space-y-6">

    @include('components.attendance.page-header', [
        'eyebrow' => strtoupper(str_replace('-', ' ', $routePrefix)) . ' Attendance',
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
    ])

    @include('components.attendance.flash-messages')

    @if($officeLocations->isEmpty())
        @include('components.attendance.empty-state', ['title' => 'No office available', 'description' => 'Create an office location first before configuring attendance settings.', 'icon' => 'fa-solid fa-building'])
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.45fr_1fr]">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <form method="GET" action="{{ route($routePrefix . '.attendance.settings') }}" class="mb-6 flex flex-col gap-3 border-b border-slate-100 pb-6 sm:flex-row sm:items-end">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="office_location_id">Office</label>
                        <select id="office_location_id" name="office_location_id"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:min-w-[18rem]">
                            @foreach($officeLocations as $office)
                                <option value="{{ $office->id }}" @selected($selectedOffice?->id === $office->id)>{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                        <i class="fa-solid fa-filter"></i>
                        <span>Switch Office</span>
                    </button>
                </form>

                @if($selectedOffice)
                    <form method="POST" action="{{ route($routePrefix . '.attendance.settings.update') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="office_location_id" value="{{ $selectedOffice->id }}">

                        <div class="md:col-span-2">
                            @include('components.attendance.info-item', ['label' => 'Selected Office', 'value' => $selectedOffice->name, 'helper' => $selectedOffice->address])
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="work_start_time">Work Start</label>
                            <input id="work_start_time" name="work_start_time" type="time"
                                value="{{ old('work_start_time', $currentSetting?->work_start_time ? substr((string) $currentSetting->work_start_time, 0, 5) : '08:00') }}"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="work_end_time">Work End</label>
                            <input id="work_end_time" name="work_end_time" type="time"
                                value="{{ old('work_end_time', $currentSetting?->work_end_time ? substr((string) $currentSetting->work_end_time, 0, 5) : '17:00') }}"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="late_tolerance_minutes">Late Tolerance Minutes</label>
                            <input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0"
                                value="{{ old('late_tolerance_minutes', $currentSetting?->late_tolerance_minutes ?? 15) }}"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="qr_rotation_seconds">QR Rotation Seconds</label>
                            <input id="qr_rotation_seconds" name="qr_rotation_seconds" type="number" min="15"
                                value="{{ old('qr_rotation_seconds', $currentSetting?->qr_rotation_seconds ?? 30) }}"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="min_location_accuracy_meter">Min Location Accuracy Meter</label>
                            <input id="min_location_accuracy_meter" name="min_location_accuracy_meter" type="number" min="1"
                                value="{{ old('min_location_accuracy_meter', $currentSetting?->min_location_accuracy_meter ?? 50) }}"
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        </div>
                        <div class="flex items-center gap-3 md:col-span-2">
                            <input id="is_active" name="is_active" type="checkbox" value="1"
                                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                @checked(old('is_active', $currentSetting?->is_active ?? true))>
                            <label class="text-sm font-medium text-slate-700" for="is_active">Use this policy as active policy</label>
                        </div>

                        @if($errors->any())
                            <div class="md:col-span-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                Please review the form fields. Some values are still invalid.
                            </div>
                        @endif

                        <div class="md:col-span-2 flex flex-wrap gap-3">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Save Policy</span>
                            </button>
                            <a href="{{ route($routePrefix . '.attendance.settings', ['office_location_id' => $selectedOffice->id]) }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                <i class="fa-solid fa-rotate-right"></i>
                                <span>Reset</span>
                            </a>
                        </div>
                    </form>
                @endif
            </div>

            <div class="space-y-6">
                @if($settingSummary)
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">Current Policy Summary</h2>
                        <div class="mt-5 grid grid-cols-1 gap-3">
                            @include('components.attendance.info-item', ['label' => 'Office', 'value' => $settingSummary['office_name']])
                            @include('components.attendance.info-item', ['label' => 'Work Hours', 'value' => $settingSummary['work_start'] . ' - ' . $settingSummary['work_end']])
                            @include('components.attendance.info-item', ['label' => 'Late Tolerance', 'value' => $settingSummary['late_tolerance']])
                            @include('components.attendance.info-item', ['label' => 'QR Rotation', 'value' => $settingSummary['qr_rotation']])
                            @include('components.attendance.info-item', ['label' => 'Minimum Accuracy', 'value' => $settingSummary['min_accuracy']])
                            @include('components.attendance.info-item', ['label' => 'Office Radius', 'value' => $settingSummary['radius']])
                            @include('components.attendance.info-item', ['label' => 'Status', 'value' => $settingSummary['active'] ? 'Active' : 'Inactive'])
                        </div>
                    </div>
                @else
                    @include('components.attendance.empty-state', ['title' => 'No policy configured yet', 'description' => 'Use the form to create the first attendance policy for the selected office.', 'icon' => 'fa-solid fa-sliders'])
                @endif

                @include('components.attendance.state-panel', [
                    'title' => 'Helper Note',
                    'description' => 'Office radius is still managed from the office location module. Keep that value aligned with this attendance policy to avoid false suspicious flags.',
                    'icon' => 'fa-solid fa-circle-info',
                    'classes' => 'border-slate-200 bg-slate-50',
                    'iconClasses' => 'bg-slate-100 text-slate-700',
                ])
            </div>
        </div>
    @endif
</div>
@endsection



