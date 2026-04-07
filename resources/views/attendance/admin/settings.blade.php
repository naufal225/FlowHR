@extends($layout)

@section('title', 'Attendance Settings')
@section('header', 'Attendance Settings')
@section('subtitle', 'Configure attendance policy per office')

@section('content')
<div class="space-y-6">
    @include('components.attendance.page-header', [
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
        'backHref' => route($routePrefix . '.attendance.index'),
        'backLabel' => 'Back to Overview',
        'sideMeta' => [
            'label' => 'Selected Office',
            'value' => $selectedOffice?->name ?? 'No office selected',
        ],
    ])

    @include('components.attendance.flash-messages')

    @if($officeLocations->isEmpty())
        @include('components.attendance.empty-state', [
            'title' => 'No office available',
            'description' => 'Create an office location first before configuring attendance settings.',
            'icon' => 'fa-solid fa-building',
        ])
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.45fr_1fr]">
            <div class="p-6 bg-white border shadow-sm border-slate-200 rounded-3xl">
                <form method="GET" action="{{ $settingsForm['filter_action'] }}"
                    class="flex flex-col gap-4 pb-6 mb-6 border-b border-slate-100 sm:flex-row sm:items-end">
                    <div class="flex-grow w-full sm:w-auto">
                        <label class="block mb-2 text-xs font-semibold tracking-wider uppercase text-slate-500"
                            for="office_location_id">
                            Select Office
                        </label>
                        <div class="relative">
                            <select id="office_location_id" name="office_location_id"
                                class="w-full appearance-none rounded-xl border border-slate-300 bg-white py-2.5 pl-4 pr-10 text-sm font-medium text-slate-700 shadow-sm transition-all focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 sm:min-w-[18rem] hover:border-slate-400 cursor-pointer">
                                @foreach($officeLocations as $office)
                                    <option value="{{ $office->id }}" @selected($settingsForm['selected_office_id'] === $office->id)>
                                        {{ $office->name }}{{ filled($office->code) ? ' - ' . $office->code : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-3 pointer-events-none text-slate-500">
                                <i class="text-xs fa-solid fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-sky-600/20 transition-all hover:bg-sky-700 hover:shadow-sky-600/30 hover:-translate-y-0.5 active:translate-y-0 active:shadow-none">
                        <i class="text-sm fa-solid fa-filter"></i>
                        <span>Switch Office</span>
                    </button>
                </form>

                <form method="POST" action="{{ $settingsForm['submit_action'] }}" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="office_location_id" value="{{ $settingsForm['selected_office_id'] }}">

                    <div class="md:col-span-2">
                        <div class="flex items-start gap-4 p-4 border rounded-2xl bg-slate-50 border-slate-100">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full shrink-0 bg-sky-100 text-sky-600">
                                <i class="text-lg fa-solid fa-building"></i>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800">Selected Office</h3>
                                    <p class="text-base font-medium text-slate-900">
                                        {{ $selectedOffice?->name ?? 'No office selected' }}
                                        @if(filled($selectedOffice?->code))
                                            <span class="ml-2 text-xs font-semibold text-slate-500">{{ $selectedOffice->code }}</span>
                                        @endif
                                    </p>
                                </div>
                                <p class="text-xs text-slate-500">
                                    <i class="mr-1 fa-solid fa-location-dot"></i>
                                    {{ $selectedOffice?->address ?: 'Office address is not available yet.' }}
                                </p>
                                <div class="flex flex-wrap gap-2 pt-1 text-xs font-medium">
                                    <span class="px-2.5 py-1 rounded-full bg-sky-50 text-sky-700">Radius {{ $selectedOffice?->radius_meter ? number_format($selectedOffice->radius_meter) . ' m' : '-' }}</span>
                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">Timezone {{ $selectedOffice?->timezone ?: '-' }}</span>
                                    <span class="px-2.5 py-1 rounded-full {{ $selectedOffice?->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $selectedOffice?->is_active ? 'Office Active' : 'Office Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="px-4 py-3 text-sm border md:col-span-2 rounded-2xl border-rose-200 bg-rose-50 text-rose-700">
                            <div class="flex items-start gap-2">
                                <i class="mt-0.5 fa-solid fa-circle-exclamation"></i>
                                <div>
                                    <p class="font-semibold">Please review the attendance setting form.</p>
                                    <ul class="mt-1 space-y-1 list-disc list-inside">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="group">
                        <x-time-picker
                            id="work_start_time"
                            name="work_start_time"
                            label="Work Start Time"
                            :value="$settingsForm['values']['work_start_time']"
                        />
                    </div>

                    <div class="group md:max-w-xs">
                        <x-time-picker
                            id="work_end_time"
                            name="work_end_time"
                            label="Work End Time"
                            :value="$settingsForm['values']['work_end_time']"
                        />
                    </div>

                    <div class="group">
                        <label class="block mb-2 text-sm font-semibold transition-colors text-slate-700 group-focus-within:text-sky-600"
                            for="late_tolerance_minutes">
                            Late Tolerance (Minutes)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i class="text-sm fa-regular fa-clock"></i>
                            </div>
                            <input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0"
                                value="{{ $settingsForm['values']['late_tolerance_minutes'] }}"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm transition-all placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 hover:border-slate-400">
                        </div>
                    </div>

                    <div class="group">
                        <label class="block mb-2 text-sm font-semibold transition-colors text-slate-700 group-focus-within:text-sky-600"
                            for="qr_rotation_seconds">
                            QR Rotation (Seconds)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i class="text-sm fa-solid fa-qrcode"></i>
                            </div>
                            <input id="qr_rotation_seconds" name="qr_rotation_seconds" type="number" min="15"
                                value="{{ $settingsForm['values']['qr_rotation_seconds'] }}"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm transition-all placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 hover:border-slate-400">
                        </div>
                    </div>

                    <div class="group md:col-span-2">
                        <label class="block mb-2 text-sm font-semibold transition-colors text-slate-700 group-focus-within:text-sky-600"
                            for="min_location_accuracy_meter">
                            Min Location Accuracy (Meters)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <i class="text-sm fa-solid fa-location-crosshairs"></i>
                            </div>
                            <input id="min_location_accuracy_meter" name="min_location_accuracy_meter" type="number" min="1"
                                value="{{ $settingsForm['values']['min_location_accuracy_meter'] }}"
                                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm transition-all placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 hover:border-slate-400">
                        </div>
                        <p class="mt-1.5 text-xs text-slate-500">Lower values require higher GPS precision. This value is stored directly in the attendance policy table.</p>
                    </div>

                    <div class="flex items-center gap-3 p-3 border rounded-xl border-slate-200 bg-slate-50/50 md:col-span-2">
                        <div class="relative flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" value="1" @checked($settingsForm['values']['is_active'])
                                class="w-5 h-5 transition-all bg-white border rounded-md shadow-sm appearance-none cursor-pointer peer border-slate-300 checked:border-sky-600 checked:bg-sky-600 hover:border-sky-400 focus:ring-2 focus:ring-sky-500/20">
                            <i class="fa-solid fa-check absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-[10px] text-white opacity-0 transition-opacity peer-checked:opacity-100 pointer-events-none"></i>
                        </div>
                        <label class="text-sm font-medium cursor-pointer select-none text-slate-700" for="is_active">
                            Use this policy as the <span class="font-bold text-sky-600">active policy</span> for attendance validation.
                        </label>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-4 border-t md:col-span-2 border-slate-100">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-sky-600/20 transition-all hover:bg-sky-700 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0">
                            <i class="text-base fa-solid fa-floppy-disk"></i>
                            <span>Save Policy</span>
                        </button>

                        <a href="{{ $settingsForm['reset_href'] }}"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition-all hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 active:bg-slate-100">
                            <i class="text-base fa-solid fa-rotate-right"></i>
                            <span>Reset Changes</span>
                        </a>
                    </div>
                </form>
            </div>

            <div class="space-y-8">
                @if($settingSummary)
                    <div class="p-8 bg-white border shadow-sm rounded-3xl border-slate-200/60">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-600">
                                <i class="fa-solid fa-clipboard-list"></i>
                            </div>
                            <h2 class="text-xl font-bold tracking-tight text-slate-900">Current Policy Summary</h2>
                        </div>

                        <div class="grid grid-cols-1 gap-4 mt-2">
                            @include('components.attendance.info-item', [
                                'label' => 'Office',
                                'value' => $settingSummary['office_name'],
                                'helper' => $selectedOffice?->address,
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'Work Hours',
                                'value' => $settingSummary['work_start'] . ' - ' . $settingSummary['work_end'],
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'Late Tolerance',
                                'value' => $settingSummary['late_tolerance'],
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'QR Rotation',
                                'value' => $settingSummary['qr_rotation'],
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'Min. Accuracy',
                                'value' => $settingSummary['min_accuracy'],
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'Office Radius',
                                'value' => $settingSummary['radius'],
                            ])

                            @include('components.attendance.info-item', [
                                'label' => 'Status',
                                'value' => $settingSummary['active'] ? 'Active' : 'Inactive',
                                'helper' => $currentSetting?->updated_at?->translatedFormat('d M Y H:i')
                                    ? 'Last updated ' . $currentSetting->updated_at->translatedFormat('d M Y H:i')
                                    : null,
                            ])
                        </div>
                    </div>
                @else
                    <div class="py-12">
                        @include('components.attendance.empty-state', [
                            'title' => 'No policy configured yet',
                            'description' => 'Use the form to create the first attendance policy for the selected office.',
                            'icon' => 'fa-solid fa-sliders',
                        ])
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
