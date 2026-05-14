@extends('layouts.app')

@section('title', 'Edit Cuti #' . $leave->id)
@section('header', 'Edit Cuti')
@section('subtitle', 'Perbarui pengajuan cuti')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('leaves.index') }}" class="hover:text-primary-600">Leave</a>
        <span class="mx-1">/</span>
        <a href="{{ route('leaves.show', $leave) }}" class="hover:text-primary-600">Detail #{{ $leave->id }}</a>
        <span class="mx-1">/</span><span class="text-gray-700">Edit</span>
    </nav>

    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Edit Cuti #{{ $leave->id }}</h2>
        </header>
        <form method="POST" action="{{ route('leaves.update', $leave) }}" class="p-5 space-y-4">
            @csrf @method('PUT')
            <x-alert-errors />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="date" name="date_start" value="{{ old('date_start', $leave->date_start) }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Selesai</label>
                    <input type="date" name="date_end" value="{{ old('date_end', $leave->date_end) }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                </div>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Alasan</label>
                <textarea name="reason" rows="4" required maxlength="1000"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">{{ old('reason', $leave->reason) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <a href="{{ route('leaves.show', $leave) }}"
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
