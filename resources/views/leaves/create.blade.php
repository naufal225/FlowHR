@extends('layouts.app')

@section('title', 'Ajukan Cuti')
@section('header', 'Ajukan Cuti')
@section('subtitle', 'Sisa cuti: ' . $sisaCuti . ' hari')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('leaves.index') }}" class="hover:text-primary-600">Leave</a>
        <span class="mx-1">/</span><span class="text-gray-700">Ajukan Cuti</span>
    </nav>

    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Form Pengajuan Cuti</h2>
        </header>
        <form method="POST" action="{{ route('leaves.store') }}" class="p-5 space-y-4">
            @csrf
            <x-alert-errors />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_start" id="date_start" value="{{ old('date_start') }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300 @error('date_start') border-rose-400 @enderror">
                    @error('date_start')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Selesai <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_end" id="date_end" value="{{ old('date_end') }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300 @error('date_end') border-rose-400 @enderror">
                    @error('date_end')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="duration_info" class="hidden text-xs text-primary-700 bg-primary-50 rounded-lg px-3 py-2">
                <i class="fas fa-info-circle mr-1"></i>
                <span id="duration_text"></span>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Alasan <span class="text-rose-500">*</span></label>
                <textarea name="reason" rows="4" required maxlength="1000" placeholder="Tulis alasan pengajuan cuti..."
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300 @error('reason') border-rose-400 @enderror">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('leaves.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                    Kirim Pengajuan
                </button>
            </div>
        </form>
    </article>
</div>
@endsection

@push('scripts')
<script>
const holidays = @json($holidayDates);
const startInput = document.getElementById('date_start');
const endInput   = document.getElementById('date_end');

function isHolidayOrWeekend(date) {
    const d = new Date(date);
    if (d.getDay() === 0 || d.getDay() === 6) return true;
    return holidays.includes(date);
}

function countWorkingDays(start, end) {
    let count = 0;
    let cur   = new Date(start);
    const e   = new Date(end);
    while (cur <= e) {
        const ds = cur.toISOString().split('T')[0];
        if (!isHolidayOrWeekend(ds)) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

function updateDuration() {
    if (!startInput.value || !endInput.value) return;
    const days = countWorkingDays(startInput.value, endInput.value);
    const info = document.getElementById('duration_info');
    document.getElementById('duration_text').textContent = `Estimasi ${days} hari kerja (tidak termasuk libur & weekend).`;
    info.classList.remove('hidden');
}

startInput.addEventListener('change', updateDuration);
endInput.addEventListener('change', updateDuration);
</script>
@endpush
