@extends('components.super-admin.layout.layout-super-admin')
@section('header', 'Edit Setting')
@section('subtitle', 'Update setting value')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('super-admin.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-neutral-700 hover:text-primary-600">
                    <i class="mr-2 fas fa-home"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <a href="{{ route('super-admin.settings.index') }}"
                        class="text-sm font-medium text-neutral-700 hover:text-primary-600">Settings</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="mx-2 fas fa-chevron-right text-neutral-400"></i>
                    <span class="text-sm font-medium text-neutral-500">Edit {{ $costSetting->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="px-6 py-4 border-b border-neutral-200">
            <h2 class="text-lg font-bold text-neutral-900">Edit Setting</h2>
            <p class="text-sm text-neutral-600">Update the value for {{ $costSetting->name }}</p>
        </div>

        @if ($errors->any())
        <div class="px-4 py-3 mx-6 mt-6 border rounded-lg bg-error-50 border-error-200 text-error-700">
            <ul class="pl-5 space-y-1 list-disc">
                @foreach ($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('super-admin.settings.update', $costSetting->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block mb-2 text-sm font-semibold text-neutral-700">
                    Setting Name
                </label>
                <div class="p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                    <p class="font-medium text-neutral-900">{{ $costSetting->name }}</p>
                </div>
            </div>

            <div>
                <label class="block mb-2 text-sm font-semibold text-neutral-700">
                    Description
                </label>
                <div class="p-3 border rounded-lg bg-neutral-50 border-neutral-200">
                    <p class="text-neutral-900">{{ $costSetting->description }}</p>
                </div>
            </div>

            <div>
                <label for="value" class="block mb-2 text-sm font-semibold text-neutral-700">
                    Value
                </label>
                @if($costSetting->key !== 'ANNUAL_LEAVE')
                    <div class="flex items-center">
                        <span class="px-3 py-2 text-sm border border-r-0 rounded-l-lg bg-neutral-50 border-neutral-300">Rp</span>
                        <input type="text" id="value" inputmode="numeric" autocomplete="off"
                               value="{{ old('value', $costSetting->value) }}"
                               class="flex-1 block w-full px-3 py-2 border rounded-r-lg border-neutral-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm js-currency"
                               data-hidden="#value_hidden" placeholder="0" required>
                        <input type="hidden" id="value_hidden" name="value" value="{{ old('value', $costSetting->value) }}">
                    </div>
                @else
                    <input type="number" id="value" name="value" value="{{ (int) old('value', $costSetting->value) }}"
                           step="1" min="0"
                           class="flex-1 block w-full px-3 py-2 border rounded-lg border-neutral-300 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                           required>
                @endif
                <p class="mt-1 text-xs text-neutral-500">Enter the value</p>
            </div>

            <div class="flex justify-end pt-6 space-x-4 border-t border-neutral-200">
                <a href="{{ route('super-admin.settings.index') }}"
                    class="px-6 py-2 text-sm font-medium transition-colors duration-200 rounded-lg text-neutral-700 bg-neutral-100 hover:bg-neutral-200">
                    <i class="mr-2 fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="btn-primary">
                    <i class="mr-2 fas fa-save"></i>
                    Update Setting
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        function formatRupiahDisplay(val){
            if (val == null) return '';
            val = String(val).replace(/[^0-9,\.]/g, '');
            // normalize any dots as decimal into comma for display
            if (val.indexOf(',') === -1 && val.indexOf('.') !== -1) {
                // if both exist, prefer first comma; otherwise convert dot to comma
                val = val.replace(/\./g, ',');
            }
            const parts = val.split(',');
            let intPart = parts[0].replace(/\D/g, '');
            if (!intPart) return '';
            intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            if (parts.length > 1) {
                const dec = parts.slice(1).join('').replace(/\D/g, '').slice(0, 2);
                return dec ? intPart + ',' + dec : intPart;
            }
            return intPart;
        }

        function normalizeForSubmit(displayVal){
            if (!displayVal) return '';
            // remove thousand separators and convert comma to dot for decimal
            return String(displayVal).replace(/\./g, '').replace(',', '.');
        }

        function initCurrencyInputs(){
            document.querySelectorAll('.js-currency').forEach(function(input){
                const hiddenSelector = input.getAttribute('data-hidden');
                const hidden = hiddenSelector ? document.querySelector(hiddenSelector) : null;
                if (!hidden) return;

                // Initialize display from hidden value
                input.value = formatRupiahDisplay(hidden.value);

                const syncHidden = function(){
                    hidden.value = normalizeForSubmit(input.value);
                };

                input.addEventListener('input', function(e){
                    const cursorFromEnd = input.value.length - input.selectionStart;
                    input.value = formatRupiahDisplay(input.value);
                    syncHidden();
                    // try to restore caret near end for better UX
                    const pos = Math.max(0, input.value.length - cursorFromEnd);
                    input.setSelectionRange(pos, pos);
                });

                // Ensure hidden is synced before submit
                input.form && input.form.addEventListener('submit', syncHidden);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCurrencyInputs);
        } else {
            initCurrencyInputs();
        }
    })();
</script>
@endpush
