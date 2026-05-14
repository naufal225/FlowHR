@extends('layouts.app')
@section('title', 'Edit Reimbursement #' . $reimbursement->id)
@section('header', 'Edit Reimbursement')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('reimbursements.index') }}" class="hover:text-primary-600">Reimbursement</a>
        <span class="mx-1">/</span>
        <a href="{{ route('reimbursements.show', $reimbursement) }}" class="hover:text-primary-600">Detail #{{ $reimbursement->id }}</a>
        <span class="mx-1">/</span><span class="text-gray-700">Edit</span>
    </nav>
    <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        <header class="px-5 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-sm font-semibold text-gray-800">Edit Reimbursement #{{ $reimbursement->id }}</h2>
        </header>
        <form method="POST" action="{{ route('reimbursements.update', $reimbursement) }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf @method('PUT')
            <x-alert-errors />
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Nama Perusahaan/Customer</label>
                <input type="text" name="customer" value="{{ old('customer', $reimbursement->customer) }}" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Tipe Reimbursement</label>
                <select name="reimbursement_type_id" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    @foreach($types as $type)
                    <option value="{{ $type->id }}" {{ (old('reimbursement_type_id', $reimbursement->reimbursement_type_id)) == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Total (Rp)</label>
                    <input type="number" name="total" value="{{ old('total', $reimbursement->total) }}" min="0" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700">Tanggal Pengeluaran</label>
                    <input type="date" name="date" value="{{ old('date', $reimbursement->date) }}" required
                        class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300">
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Ganti Bukti / Invoice (opsional)</label>
                <input type="file" name="invoice_path" accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2">
                @if($reimbursement->invoice_path)
                <div class="flex items-center gap-2 mt-2">
                    <a href="{{ Storage::url($reimbursement->invoice_path) }}" target="_blank"
                        class="text-xs text-primary-600 hover:underline">Lihat lampiran saat ini</a>
                    <label class="flex items-center gap-1 text-xs text-gray-500">
                        <input type="checkbox" name="remove_invoice_path" value="1"> Hapus lampiran
                    </label>
                </div>
                @endif
            </div>
            <div class="flex gap-3 pt-2">
                <a href="{{ route('reimbursements.show', $reimbursement) }}"
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
