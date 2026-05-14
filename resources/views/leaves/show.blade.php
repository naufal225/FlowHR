@extends('layouts.app')

@section('title', 'Detail Cuti #' . $leave->id)
@section('header', 'Detail Cuti')
@section('subtitle', 'Informasi pengajuan cuti')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Breadcrumb --}}
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('leaves.index') }}" class="hover:text-primary-600">Leave</a>
        <span class="mx-1">/</span>
        <span class="text-gray-700">Detail #{{ $leave->id }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Main details --}}
        <div class="space-y-6 lg:col-span-2">
            <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                <header class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="font-semibold text-gray-800">Cuti #{{ $leave->id }}</h2>
                    @if($leave->status_1 === 'approved')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">Approved</span>
                    @elseif($leave->status_1 === 'rejected')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600 border border-rose-100">Rejected</span>
                    @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 border border-amber-100">Pending</span>
                    @endif
                </header>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Karyawan</p>
                        <p class="font-medium text-gray-800">{{ $leave->employee?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $leave->employee?->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Divisi</p>
                        <p class="font-medium text-gray-800">{{ $leave->employee?->division?->name ?? '–' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tanggal Mulai</p>
                        <p class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($leave->date_start)->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tanggal Selesai</p>
                        <p class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($leave->date_end)->format('d F Y') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 mb-0.5">Alasan</p>
                        <p class="text-gray-800">{{ $leave->reason }}</p>
                    </div>
                    @if($leave->note_1)
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 mb-0.5">Catatan Approver</p>
                        <p class="text-gray-800 bg-gray-50 rounded-lg px-3 py-2">{{ $leave->note_1 }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Diajukan</p>
                        <p class="text-gray-700">{{ \Carbon\Carbon::parse($leave->created_at)->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Approver</p>
                        <p class="text-gray-700">{{ $leave->approver1?->name ?? '–' }}</p>
                    </div>
                </div>
            </article>

            {{-- Approval form (manager only, pending, not own) --}}
            @if($canApprove)
            <article class="overflow-hidden bg-white border border-orange-100 shadow-sm rounded-2xl">
                <header class="px-5 py-4 border-b border-orange-100 bg-orange-50">
                    <h3 class="text-sm font-semibold text-orange-800">
                        <i class="fas fa-check-circle mr-2"></i>Tindakan Approval
                    </h3>
                </header>
                <div class="p-5">
                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Catatan (opsional)</label>
                        <textarea id="note_input" rows="2" placeholder="Tulis catatan approval..."
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300"></textarea>
                    </div>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('leaves.approve', $leave) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="note_1" id="approve_note">
                            <button type="submit" onclick="document.getElementById('approve_note').value = document.getElementById('note_input').value"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                <i class="fas fa-check mr-1"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('leaves.reject', $leave) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="note_1" id="reject_note">
                            <button type="submit" onclick="document.getElementById('reject_note').value = document.getElementById('note_input').value"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-rose-600 hover:bg-rose-700">
                                <i class="fas fa-times mr-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                </div>
            </article>
            @endif
        </div>

        {{-- Sidebar actions --}}
        <div class="space-y-3">
            <a href="{{ route('leaves.index') }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-arrow-left text-gray-400"></i> Kembali
            </a>
            @if($leave->status_1 === 'pending' && (int)$leave->employee_id === (int)Auth::id())
            <a href="{{ route('leaves.edit', $leave) }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-edit text-gray-400"></i> Edit
            </a>
            <form method="POST" action="{{ route('leaves.destroy', $leave) }}"
                onsubmit="return confirm('Yakin ingin menghapus pengajuan cuti ini?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="w-full flex items-center gap-2 px-4 py-3 text-sm text-rose-600 bg-white border border-rose-200 rounded-xl hover:bg-rose-50 shadow-sm">
                    <i class="w-4 fas fa-trash text-rose-400"></i> Hapus
                </button>
            </form>
            @endif
            <a href="{{ route('leaves.export-pdf', $leave) }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-file-pdf text-red-400"></i> Cetak PDF
            </a>
        </div>
    </div>
</div>
@endsection
