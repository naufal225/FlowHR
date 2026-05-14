@extends('layouts.app')

@section('title', 'Detail Perjalanan Dinas #' . $officialTravel->id)
@section('header', 'Detail Perjalanan Dinas')

@section('content')
<div class="max-w-4xl mx-auto">
    <nav class="mb-4 text-xs text-gray-500">
        <a href="{{ route('official-travels.index') }}" class="hover:text-primary-600">Perjalanan Dinas</a>
        <span class="mx-1">/</span><span class="text-gray-700">Detail #{{ $officialTravel->id }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <article class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
                <header class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="font-semibold text-gray-800">Perjalanan Dinas #{{ $officialTravel->id }}</h2>
                    @php
                        $s1 = $officialTravel->status_1; $s2 = $officialTravel->status_2;
                        $final = ($s1 === 'approved' && $s2 === 'approved') ? 'approved'
                               : ($s1 === 'rejected' || $s2 === 'rejected' ? 'rejected' : 'pending');
                    @endphp
                    @if($final === 'approved')
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">Approved</span>
                    @elseif($final === 'rejected')
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600 border border-rose-100">Rejected</span>
                    @else
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 border border-amber-100">Pending</span>
                    @endif
                </header>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Karyawan</p>
                        <p class="font-medium">{{ $officialTravel->employee?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $officialTravel->employee?->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Customer / Tujuan</p>
                        <p class="font-medium">{{ $officialTravel->customer ?? '–' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tanggal Mulai</p>
                        <p class="font-medium">{{ \Carbon\Carbon::parse($officialTravel->date_start)->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Tanggal Selesai</p>
                        <p class="font-medium">{{ \Carbon\Carbon::parse($officialTravel->date_end)->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Jumlah Hari</p>
                        @php
                            $days = \Carbon\Carbon::parse($officialTravel->date_start)->diffInDays(\Carbon\Carbon::parse($officialTravel->date_end)) + 1;
                        @endphp
                        <p class="font-medium">{{ $days }} hari</p>
                    </div>
                    @if($officialTravel->total)
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Total Biaya</p>
                        <p class="font-medium text-lg text-gray-900">Rp {{ number_format($officialTravel->total, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($officialTravel->description)
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 mb-0.5">Keterangan</p>
                        <p class="text-sm text-gray-700">{{ $officialTravel->description }}</p>
                    </div>
                    @endif

                    {{-- Stage 1 --}}
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Status Tahap 1 (Team Leader)</p>
                        <span class="text-xs font-medium {{ $officialTravel->status_1 === 'approved' ? 'text-emerald-600' : ($officialTravel->status_1 === 'rejected' ? 'text-rose-500' : 'text-amber-600') }}">
                            {{ ucfirst($officialTravel->status_1) }}
                        </span>
                        @if($officialTravel->note_1)
                        <p class="mt-0.5 text-xs text-gray-500">Catatan: {{ $officialTravel->note_1 }}</p>
                        @endif
                    </div>

                    {{-- Stage 2 --}}
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Status Tahap 2 (Manager)</p>
                        <span class="text-xs font-medium {{ $officialTravel->status_2 === 'approved' ? 'text-emerald-600' : ($officialTravel->status_2 === 'rejected' ? 'text-rose-500' : 'text-amber-600') }}">
                            {{ ucfirst($officialTravel->status_2) }}
                        </span>
                        @if($officialTravel->note_2)
                        <p class="mt-0.5 text-xs text-gray-500">Catatan: {{ $officialTravel->note_2 }}</p>
                        @endif
                    </div>
                </div>
            </article>

            {{-- Approval Stage 1 --}}
            @if($canApproveStage1)
            <article class="overflow-hidden bg-white border border-orange-100 shadow-sm rounded-2xl">
                <header class="px-5 py-4 border-b border-orange-100 bg-orange-50">
                    <h3 class="text-sm font-semibold text-orange-800"><i class="fas fa-check-circle mr-2"></i>Approval Tahap 1</h3>
                    <p class="text-xs text-orange-600">Kamu sebagai Team Leader</p>
                </header>
                <div class="p-5">
                    <textarea id="note_input_s1" rows="2" placeholder="Catatan (opsional)"
                        class="w-full mb-3 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300"></textarea>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('official-travels.approve', $officialTravel) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <input type="hidden" name="stage" value="1">
                            <input type="hidden" name="note" id="note_approve_s1">
                            <button onclick="document.getElementById('note_approve_s1').value=document.getElementById('note_input_s1').value"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                <i class="fas fa-check mr-1"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('official-travels.reject', $officialTravel) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <input type="hidden" name="stage" value="1">
                            <input type="hidden" name="note" id="note_reject_s1">
                            <button onclick="document.getElementById('note_reject_s1').value=document.getElementById('note_input_s1').value"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-rose-600 hover:bg-rose-700">
                                <i class="fas fa-times mr-1"></i> Tolak
                            </button>
                        </form>
                    </div>
                </div>
            </article>
            @endif

            {{-- Approval Stage 2 --}}
            @if($canApproveStage2)
            <article class="overflow-hidden bg-white border border-orange-100 shadow-sm rounded-2xl">
                <header class="px-5 py-4 border-b border-orange-100 bg-orange-50">
                    <h3 class="text-sm font-semibold text-orange-800"><i class="fas fa-check-circle mr-2"></i>Approval Tahap 2</h3>
                    <p class="text-xs text-orange-600">Kamu sebagai Manager</p>
                </header>
                <div class="p-5">
                    <textarea id="note_input_s2" rows="2" placeholder="Catatan (opsional)"
                        class="w-full mb-3 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-300"></textarea>
                    <div class="flex gap-3">
                        <form method="POST" action="{{ route('official-travels.approve', $officialTravel) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="approved">
                            <input type="hidden" name="stage" value="2">
                            <input type="hidden" name="note" id="note_approve_s2">
                            <button onclick="document.getElementById('note_approve_s2').value=document.getElementById('note_input_s2').value"
                                class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                                <i class="fas fa-check mr-1"></i> Setujui
                            </button>
                        </form>
                        <form method="POST" action="{{ route('official-travels.reject', $officialTravel) }}" class="flex-1">
                            @csrf
                            <input type="hidden" name="status" value="rejected">
                            <input type="hidden" name="stage" value="2">
                            <input type="hidden" name="note" id="note_reject_s2">
                            <button onclick="document.getElementById('note_reject_s2').value=document.getElementById('note_input_s2').value"
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
            <a href="{{ route('official-travels.index') }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-arrow-left text-gray-400"></i> Kembali
            </a>
            @if($officialTravel->status_1 === 'pending' && (int)$officialTravel->employee_id === (int)Auth::id())
            <a href="{{ route('official-travels.edit', $officialTravel) }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-edit text-gray-400"></i> Edit
            </a>
            <form method="POST" action="{{ route('official-travels.destroy', $officialTravel) }}"
                onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                @csrf @method('DELETE')
                <button class="w-full flex items-center gap-2 px-4 py-3 text-sm text-rose-600 bg-white border border-rose-200 rounded-xl hover:bg-rose-50 shadow-sm">
                    <i class="w-4 fas fa-trash text-rose-400"></i> Hapus
                </button>
            </form>
            @endif
            <a href="{{ route('official-travels.export-pdf', $officialTravel) }}"
                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 shadow-sm">
                <i class="w-4 fas fa-file-pdf text-red-400"></i> Cetak PDF
            </a>
        </div>
    </div>
</div>
@endsection
