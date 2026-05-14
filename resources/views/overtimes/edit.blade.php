@extends('layouts.app')
@section('title', 'Edit Overtime #' . $overtime->id)
@section('header', 'Edit Overtime')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('overtimes.index') }}" class="hover:text-primary-600">Overtime</a>
        <span class="mx-1">/</span>
        <a href="{{ route('overtimes.show', $overtime) }}" class="hover:text-primary-600">Detail #{{ $overtime->id }}</a>
        <span class="mx-1">/</span><span class="text-gray-700">Edit</span>
    </nav>
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Edit Overtime #{{ $overtime->id }}</h2>
        </header>
        <form method="POST" action="{{ route('overtimes.update', $overtime) }}" class="p-5 space-y-4">
            @csrf @method('PUT')
            <x-alert-errors />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal & Jam Mulai <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="date_start"
                        value="{{ old('date_start', \Carbon\Carbon::parse($overtime->date_start)->format('Y-m-d\TH:i')) }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('date_start')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal & Jam Selesai <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="date_end"
                        value="{{ old('date_end', \Carbon\Carbon::parse($overtime->date_end)->format('Y-m-d\TH:i')) }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('date_end')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Keterangan / Alasan</label>
                <textarea name="description" rows="3" placeholder="Jelaskan pekerjaan yang dilakukan saat lembur (opsional)"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">{{ old('description', $overtime->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('overtimes.show', $overtime) }}"
                    class="px-4 py-2 text-sm text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Batal</a>
                <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </article>
</div>
@endsection
