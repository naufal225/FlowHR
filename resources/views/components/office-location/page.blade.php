@php
$officeLocation = $officeLocation ?? null;
$latitudeValue = old('latitude', $officeLocation?->latitude);
$longitudeValue = old('longitude', $officeLocation?->longitude);
$radiusSliderMax = 500;
$radiusManualMax = 1000;
$radiusValue = max(1, min((int) old('radius_meter', $officeLocation?->radius_meter ?? 100), $radiusManualMax));
$radiusSliderValue = min($radiusValue, $radiusSliderMax);
$timezoneValue = old('timezone', $officeLocation?->timezone);
$addressValue = old('address', $officeLocation?->address);
$defaultCenter = config('services.google_maps.default_center', []);
$mapConfig = [
'googleMapsBrowserKey' => config('services.google_maps.browser_key'),
'defaultCenter' => [
'lat' => (float) ($defaultCenter['lat'] ?? -6.2000000),
'lng' => (float) ($defaultCenter['lng'] ?? 106.8166667),
'zoom' => (int) ($defaultCenter['zoom'] ?? 13),
],
'timezoneResolveUrl' => $timezoneResolveUrl,
'csrfToken' => csrf_token(),
'initialState' => [
'latitude' => is_numeric($latitudeValue) ? (float) $latitudeValue : null,
'longitude' => is_numeric($longitudeValue) ? (float) $longitudeValue : null,
'radiusMeter' => max(1, min((int) $radiusValue, $radiusManualMax)),
'timezone' => filled($timezoneValue) ? (string) $timezoneValue : null,
'address' => filled($addressValue) ? (string) $addressValue : '',
],
];
@endphp

@push('styles')
<style>
    .office-location-map {
        height: 420px;
        min-height: 420px;
        background: #e5e7eb;
    }

    .office-location-map .gm-style {
        font-family: inherit;
    }

    .office-location-map .gm-style img,
    .office-location-map .gm-style canvas {
        max-width: none !important;
    }

    .office-location-map .gm-style img {
        display: inline-block !important;
    }

    @media (max-width: 640px) {
        .office-location-map {
            height: 360px;
            min-height: 360px;
        }
    }
</style>
@endpush

<main class="relative z-10 flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ $pageTitle }}</h1>
                <p class="max-w-3xl mt-2 text-sm leading-6 text-gray-600">{{ $pageDescription }}</p>
            </div>
            <a href="{{ $cancelRoute }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>

        @if($errors->any())
        <div class="p-4 border shadow-sm rounded-2xl border-rose-200 bg-rose-50">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-rose-600" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-rose-800">Please correct the highlighted fields before saving.
                    </h3>
                    <ul class="pl-5 mt-2 space-y-1 text-sm list-disc text-rose-700">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ $formAction }}" method="POST" class="space-y-6" data-office-location-form
            data-config='@json($mapConfig)'>
            @csrf
            @if(strtoupper($formMethod) !== 'POST')
            @method($formMethod)
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.7fr)]">
                <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Office Profile</h2>
                        </div>
                        @if($officeLocation)
                        <span
                            class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600">{{
                            $officeLocation->code }}</span>
                        @endif
                    </div>

                    <div class="grid gap-5 mt-6 md:grid-cols-2">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Office Code <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" id="code" name="code" value="{{ old('code', $officeLocation?->code) }}"
                                class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm uppercase shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('code') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                                placeholder="JKT-HQ" required>
                            @error('code') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Office Name <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', $officeLocation?->name) }}"
                                class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('name') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                                placeholder="Jakarta Head Office" required>
                            @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="4"
                            class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('address') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                            placeholder="Full office address">{{ $addressValue }}</textarea>
                        <p class="mt-2 text-xs text-gray-500">This field is updated from the selected candidate place,
                            but you can still refine the written address if needed.</p>
                        @error('address') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </section>

                <aside class="space-y-6">
                    <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                        <h2 class="text-lg font-semibold text-gray-900">Operational Status</h2>
                        <p class="mt-1 text-sm text-gray-600">Control whether this office can be assigned to new users.
                        </p>
                        <div class="p-4 mt-5 border border-gray-200 rounded-2xl bg-gray-50">
                            <input type="hidden" name="is_active" value="0">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" name="is_active" value="1"
                                    class="w-4 h-4 mt-1 border-gray-300 rounded text-sky-600 focus:ring-sky-500" {{
                                    old('is_active', $officeLocation?->is_active ?? true) ? 'checked' : '' }}>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Active office location</span>
                                    <span class="block mt-1 text-xs leading-5 text-gray-500">Inactive offices stay in
                                        history but are hidden from new operational assignment flows.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                </aside>
            </div>
            <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Location Snapshot</h2>
                        <p class="mt-1 text-sm text-gray-600">Review the exact coordinates, timezone, and radius that
                            will be saved.</p>
                    </div>
                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-gray-500">Live sync from map, pin,
                        and timezone detection</p>
                </div>
                <dl class="grid gap-3 mt-5 lg:grid-cols-4">
                    <div class="px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Latitude</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900" data-office-location-latitude-display>{{
                            is_numeric($latitudeValue) ? number_format((float) $latitudeValue, 7, '.', '') : 'Not
                            selected yet' }}</dd>
                    </div>
                    <div class="px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Longitude</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900" data-office-location-longitude-display>{{
                            is_numeric($longitudeValue) ? number_format((float) $longitudeValue, 7, '.', '') : 'Not
                            selected yet' }}</dd>
                    </div>
                    <div class="px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Timezone</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900" data-office-location-timezone-display>{{
                            filled($timezoneValue) ? $timezoneValue : 'Waiting for lookup or manual entry' }}</dd>
                    </div>
                    <div class="px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                        <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Radius</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900" data-office-location-radius-display>{{
                            number_format((int) $radiusValue) }} meters</dd>
                    </div>
                </dl>
            </section>


            <section class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Find and Pin the Office</h2>
                        {{-- <p class="mt-1 text-sm text-gray-600">Search for a business or address, compare several
                            candidate locations, choose the correct one, then refine the exact pin on the map if needed.
                        </p> --}}
                    </div>
                    {{-- <div class="px-4 py-3 text-sm text-gray-600 border border-gray-200 rounded-xl bg-gray-50">The
                        map will only update after you explicitly select one candidate location.</div> --}}
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(300px,0.75fr)]">
                    <div class="space-y-4">
                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="office_place_search"
                                        class="block text-sm font-medium text-gray-700">Search Place or Address</label>
                                </div>
                                <div class="px-4 py-3 mt-2 bg-white border border-gray-300 shadow-sm rounded-2xl">
                                    <input type="text" id="office_place_search"
                                        class="w-full px-0 py-0 text-sm text-gray-900 bg-transparent border-0 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                                        placeholder="Search the office address, office name, or business name"
                                        autocomplete="off" spellcheck="false">
                                </div>
                                <p class="px-3 py-2 mt-2 text-xs font-medium border rounded-xl"
                                    data-office-location-search-status>Loading Google Maps search...</p>
                            </div>

                            <div class="hidden px-4 py-3 border shadow-sm rounded-2xl border-emerald-200 bg-emerald-50"
                                data-office-location-selected-panel>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Selected
                                    candidate</p>
                                <p class="mt-2 text-sm font-semibold text-emerald-900"
                                    data-office-location-selected-title></p>
                                <p class="mt-1 text-sm text-emerald-800" data-office-location-selected-address></p>
                            </div>

                            <div class="hidden overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl"
                                data-office-location-results-panel>
                                <div class="flex items-start justify-between gap-4 px-4 py-3 border-b border-gray-100">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">
                                            Candidate locations</p>
                                        <p class="mt-1 text-sm text-gray-600" data-office-location-results-meta>Select
                                            one result before the map updates.</p>
                                    </div>
                                    <span
                                        class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-sky-50 text-sky-700"
                                        data-office-location-results-count></span>
                                </div>
                                <div class="overflow-y-auto max-h-80">
                                    <ul class="divide-y divide-gray-100" data-office-location-results-list></ul>
                                    <div class="hidden px-4 py-5 text-sm text-gray-500"
                                        data-office-location-results-empty></div>
                                </div>
                            </div>
                        </div>

                        <div id="office-location-map"
                            class="overflow-hidden border border-gray-200 office-location-map rounded-2xl"></div>
                        <p class="px-4 py-3 text-sm font-medium border rounded-xl" data-office-location-map-status>
                            Preparing Google Maps...</p>
                    </div>

                    <div class="p-5 border border-gray-200 rounded-2xl bg-gray-50">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="radius_meter" class="block text-sm font-medium text-gray-700">Radius Meter
                                    <span class="text-rose-500">*</span></label>
                            </div>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-900 bg-white rounded-full shadow-sm"
                                data-office-location-radius-chip>{{ number_format((int) $radiusValue) }} m</span>
                        </div>
                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                @foreach([50, 100, 200, 300, 500] as $radiusPreset)
                                <button type="button"
                                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-gray-600 transition bg-white border border-gray-200 rounded-xl hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700"
                                    data-office-location-radius-preset="{{ $radiusPreset }}">
                                    {{ $radiusPreset }} m
                                </button>
                                @endforeach
                            </div>
                            <div class="px-4 py-4 bg-white border shadow-sm rounded-2xl border-sky-100">
                                <div
                                    class="flex items-center justify-between text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-500">
                                    <span>Quick Slider</span>
                                    <span>1-{{ number_format($radiusSliderMax) }} m</span>
                                </div>
                                <input type="range" min="1" max="{{ $radiusSliderMax }}" step="1"
                                    value="{{ $radiusSliderValue }}"
                                    class="w-full h-2 mt-4 rounded-full appearance-none cursor-pointer bg-sky-100 accent-sky-600"
                                    data-office-location-radius-range>
                                <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                                    <span>Tighter gate</span>
                                    <span>Wider gate</span>
                                </div>
                            </div>
                            <div>
                                <label for="radius_meter"
                                    class="block text-xs font-semibold uppercase tracking-[0.16em] text-gray-500">Exact
                                    Radius</label>
                                <div class="relative mt-2">
                                    <input type="number" id="radius_meter" name="radius_meter" min="1"
                                        max="{{ $radiusManualMax }}" value="{{ (int) $radiusValue }}"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3 pr-12 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('radius_meter') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                                        required>
                                    <span
                                        class="pointer-events-none absolute inset-y-0 right-4 inline-flex items-center text-xs font-semibold uppercase tracking-[0.16em] text-gray-400">m</span>
                                </div>
                                <p class="mt-2 text-xs text-gray-500" data-office-location-radius-helper>Slider covers
                                    the common office range up to {{ number_format($radiusSliderMax) }} meters. Manual
                                    input remains available up to {{ number_format($radiusManualMax) }} meters for
                                    special sites.</p>
                            </div>
                        </div>
                        @error('radius_meter') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="p-4 border border-gray-200 rounded-2xl bg-gray-50">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone <span
                                        class="text-rose-500">*</span></label>
                                <p class="mt-1 text-xs leading-5 text-gray-500">Resolved automatically on the server
                                    from the selected coordinates when the backend provider is configured.</p>
                            </div>
                            <button type="button"
                                class="inline-flex items-center rounded-xl border border-sky-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] text-sky-700 transition hover:border-sky-300 hover:bg-sky-50"
                                data-office-location-timezone-refresh>
                                Detect Timezone
                            </button>
                        </div>
                        <input type="text" id="timezone" name="timezone" value="{{ $timezoneValue }}"
                            class="mt-4 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('timezone') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                            placeholder="Asia/Jakarta" required>
                        <p class="px-3 py-2 mt-3 text-xs font-medium border rounded-xl"
                            data-office-location-timezone-status>Automatic timezone detection will appear here.</p>
                        @error('timezone') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="p-4 border border-gray-200 rounded-2xl bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-900">Advanced Coordinates</h3>
                        <p class="mt-1 text-xs leading-5 text-gray-500">These fields update from the map automatically,
                            but remain editable for recovery or fallback use.</p>
                        <div class="grid gap-4 mt-4 md:grid-cols-2">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" step="any" id="latitude" name="latitude"
                                    value="{{ $latitudeValue }}"
                                    class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('latitude') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                                    placeholder="-6.2000000" required>
                                @error('latitude') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude <span
                                        class="text-rose-500">*</span></label>
                                <input type="number" step="any" id="longitude" name="longitude"
                                    value="{{ $longitudeValue }}"
                                    class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-100 @error('longitude') border-rose-300 focus:border-rose-500 focus:ring-rose-100 @enderror"
                                    placeholder="106.8166667" required>
                                @error('longitude') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="flex flex-col-reverse justify-end max-w-6xl gap-3 pt-6 mx-auto border-gray-200 sm:flex-row">
            <a href="{{ $cancelRoute }}"
                class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Cancel</a>
            <button type="submit"
                class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-white transition shadow-sm rounded-xl bg-gradient-to-r from-sky-600 to-blue-700 hover:from-sky-700 hover:to-blue-800">{{
                $submitLabel }}</button>
        </div>

    </form>
    </div>
</main>

@include('components.office-location.script')
