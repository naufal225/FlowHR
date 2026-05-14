@extends('layouts.app')
@section('title', 'Ajukan Reimbursement')
@section('header', 'Ajukan Reimbursement')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('reimbursements.index') }}" class="hover:text-primary-600">Reimbursement</a>
        <span class="mx-1">/</span><span class="text-gray-700">Ajukan</span>
    </nav>
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Form Pengajuan Reimbursement</h2>
        </header>
        <form method="POST" action="{{ route('reimbursements.store') }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf
            <x-alert-errors />
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Nama Perusahaan/Customer <span class="text-rose-500">*</span></label>
                <input type="text" name="customer" value="{{ old('customer') }}" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                @error('customer')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Tipe Reimbursement <span class="text-rose-500">*</span></label>
                <select name="reimbursement_type_id" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ old('reimbursement_type_id') == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('reimbursement_type_id')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Total (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" name="total" value="{{ old('total') }}" min="0" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('total')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date') }}" max="{{ now()->format('Y-m-d') }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @error('date')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Bukti / Invoice <span class="text-rose-500">*</span></label>
                <input type="file" name="invoice_path" accept=".jpg,.jpeg,.png,.pdf" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                <p class="mt-1 text-xs text-gray-400">Format: JPG, PNG, PDF. Maks 2MB.</p>
                @error('invoice_path')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('reimbursements.index') }}"
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
