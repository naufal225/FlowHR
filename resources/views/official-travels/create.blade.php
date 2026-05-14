@extends('layouts.app')
@section('title', 'Ajukan Perjalanan Dinas')
@section('header', 'Ajukan Perjalanan Dinas')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('official-travels.index') }}" class="hover:text-primary-600">Perjalanan Dinas</a>
        <span class="mx-1">/</span><span class="text-gray-700">Ajukan</span>
    </nav>
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Form Pengajuan Perjalanan Dinas</h2>
        </header>
        <form method="POST" action="{{ route('official-travels.store') }}" class="p-5 space-y-4">
            @csrf
            <x-alert-errors />
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Customer / Tujuan <span class="text-rose-500">*</span></label>
                <input type="text" name="customer" value="{{ old('customer') }}" required
                    placeholder="Nama perusahaan atau tujuan perjalanan"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                @error('customer')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_start" value="{{ old('date_start') }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('date_start')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Selesai <span class="text-rose-500">*</span></label>
                    <input type="date" name="date_end" value="{{ old('date_end') }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('date_end')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Keterangan</label>
                <textarea name="description" rows="3" placeholder="Tujuan dan agenda perjalanan dinas (opsional)"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('official-travels.index') }}"
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
