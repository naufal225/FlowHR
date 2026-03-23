@extends('components.admin.layout.layout-admin')
@section('header', 'Settings')
@section('subtitle', 'Manage application settings')

@section('content')
<main class="relative z-10 flex-1 p-0 space-y-6 overflow-x-hidden overflow-y-auto bg-gray-50">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Settings</h1>
            <p class="text-neutral-600">Manage application configurations</p>
        </div>
    </div>

    @if(session('success'))
    <div class="flex items-center p-4 my-6 border border-green-200 bg-green-50 rounded-xl">
        <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
        </div>
    </div>
    @endif

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Feature Management</h2>
            <p class="text-sm text-gray-600">Enable or disable application modules</p>
        </div>
        <form action="{{ route('admin.settings.features.update') }}" method="POST" class="p-6">
            @csrf
            @php
            $f = $features ?? collect();
            $isOn = fn($k) => (bool) ($f[$k] ?? false);
            @endphp
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Leave Feature -->
                <label
                    class="relative flex items-start p-4 transition-all duration-200 border border-gray-200 rounded-lg cursor-pointer group hover:border-blue-300 hover:shadow-md hover:bg-blue-50/30">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg">
                                <i class="text-sm text-blue-600 fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Leave (Cuti)</div>
                                <div class="text-xs text-gray-500">Show leave requests & calendar</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center ml-4">
                        <input type="checkbox" name="cuti" value="1"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $isOn('cuti')
                            ? 'checked' : '' }}>
                    </div>
                </label>

                <!-- Reimbursement Feature -->
                <label
                    class="relative flex items-start p-4 transition-all duration-200 border border-gray-200 rounded-lg cursor-pointer group hover:border-purple-300 hover:shadow-md hover:bg-purple-50/30">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-lg">
                                <i class="text-sm text-purple-600 fas fa-receipt"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Reimbursement</div>
                                <div class="text-xs text-gray-500">Show reimbursement flows</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center ml-4">
                        <input type="checkbox" name="reimbursement" value="1"
                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500" {{
                            $isOn('reimbursement') ? 'checked' : '' }}>
                    </div>
                </label>

                <!-- Overtime Feature -->
                <label
                    class="relative flex items-start p-4 transition-all duration-200 border border-gray-200 rounded-lg cursor-pointer group hover:border-green-300 hover:shadow-md hover:bg-green-50/30">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-lg">
                                <i class="text-sm text-green-600 fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Overtime</div>
                                <div class="text-xs text-gray-500">Show overtime requests</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center ml-4">
                        <input type="checkbox" name="overtime" value="1"
                            class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500" {{
                            $isOn('overtime') ? 'checked' : '' }}>
                    </div>
                </label>

                <!-- Official Travel Feature -->
                <label
                    class="relative flex items-start p-4 transition-all duration-200 border border-gray-200 rounded-lg cursor-pointer group hover:border-yellow-300 hover:shadow-md hover:bg-yellow-50/30">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-lg">
                                <i class="text-sm text-yellow-600 fas fa-plane"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Official Travel</div>
                                <div class="text-xs text-gray-500">Show official travel requests</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center ml-4">
                        <input type="checkbox" name="perjalanan_dinas" value="1"
                            class="w-5 h-5 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500" {{
                            $isOn('perjalanan_dinas') ? 'checked' : '' }}>
                    </div>
                </label>
            </div>

            <div class="flex justify-end pt-6 mt-6 border-t border-gray-200">
                <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white transition-all duration-200 bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 focus:outline-none">
                    <i class="mr-2 fas fa-save"></i>
                    Save Feature Settings
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Application Settings</h2>
            <p class="text-sm text-neutral-600">Update the values used throughout the application</p>
        </div>

        <form action="{{ route('admin.settings.update-multiple') }}" method="POST" class="p-6">
            @csrf
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach($settings->chunk(ceil($settings->count() / 2)) as $chunk)
                <div class="space-y-6">
                    @foreach($chunk as $setting)
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-semibold text-neutral-700">
                                {{ $setting->name }}
                            </label>
                            <p class="mt-1 text-sm text-neutral-500">{{ $setting->description }}</p>
                        </div>
                        <div class="md:col-span-2">
                            @if($setting->key !== 'ANNUAL_LEAVE')
                            <div class="flex items-center">
                                <span
                                    class="px-3 py-2 text-sm border border-r-0 rounded-l-lg bg-neutral-50 border-neutral-300">Rp</span>
                                <input type="text" inputmode="numeric" autocomplete="off"
                                    value="{{ old($setting->key, $setting->value) }}"
                                    class="flex-1 block w-full px-3 py-2 border rounded-r-lg border-neutral-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm js-currency"
                                    data-hidden="#setting-{{ $setting->key }}-hidden" placeholder="0">
                                <input type="hidden" id="setting-{{ $setting->key }}-hidden" name="{{ $setting->key }}"
                                    value="{{ old($setting->key, $setting->value) }}">
                            </div>
                            @else
                            <input type="number" name="{{ $setting->key }}"
                                value="{{ (int) old($setting->key, $setting->value) }}" step="1" min="0"
                                class="flex-1 block w-full px-3 py-2 border rounded-lg border-neutral-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                                placeholder="Enter value">
                            @endif
                            @error($setting->key)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <hr class="border-neutral-200">
                    @endforeach
                </div>
                @endforeach
            </div>

            <div class="flex justify-end pt-6 mt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ route('admin.settings.index') }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-undo"></i>
                    Reset Changes
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-save"></i>
                    Save All Changes
                </button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const currencyInputs = document.querySelectorAll('.js-currency');

    function formatRupiah(angka) {
        if (!angka || angka === '') return '';
        const num = parseInt(angka, 10);
        return isNaN(num) ? '' : num.toLocaleString('id-ID');
    }

    // Simpan nilai awal & format saat halaman dimuat
    currencyInputs.forEach(input => {
        const hiddenInput = document.querySelector(input.dataset.hidden);
        if (hiddenInput && hiddenInput.value) {
            // Simpan nilai asli ke dataset untuk keperluan reset
            input.dataset.originalValue = hiddenInput.value;
            input.value = formatRupiah(hiddenInput.value);
        }
    });

    // Handle input user
    currencyInputs.forEach(input => {
        const hiddenInput = document.querySelector(input.dataset.hidden);

        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            hiddenInput.value = value;
            e.target.value = formatRupiah(value);
        });

        input.addEventListener('blur', function (e) {
            if (e.target.value === '') {
                hiddenInput.value = '';
            }
        });
    });

    // Handle RESET: kembalikan ke nilai awal yang disimpan
    if (form) {
        form.addEventListener('reset', function (e) {
            // Browser reset terjadi secara async, jadi pakai setTimeout
            setTimeout(() => {
                currencyInputs.forEach(input => {
                    const hiddenInput = document.querySelector(input.dataset.hidden);
                    if (!hiddenInput || !input.dataset.originalValue) return;

                    const original = input.dataset.originalValue;
                    hiddenInput.value = original;
                    input.value = formatRupiah(original);
                });
            }, 10);
        });
    }
});
</script>
@endpush
