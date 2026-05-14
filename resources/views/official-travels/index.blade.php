@extends('layouts.app')

@section('title', 'Perjalanan Dinas')
@section('header', 'Perjalanan Dinas')
@section('subtitle', $permissions['canViewAllRequests'] ? 'Kelola semua pengajuan perjalanan dinas' : 'Pengajuan perjalanan dinas kamu')

@section('content')

{{-- Stats --}}
<section class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <article class="p-4 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Total</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
    </article>
    <article class="p-4 bg-amber-50 border border-amber-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-amber-600 uppercase">Pending</p>
        <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['pending'] }}</p>
    </article>
    <article class="p-4 bg-emerald-50 border border-emerald-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-emerald-600 uppercase">Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['approved'] }}</p>
    </article>
    <article class="p-4 bg-rose-50 border border-rose-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-rose-600 uppercase">Rejected</p>
        <p class="mt-1 text-2xl font-bold text-rose-700">{{ $stats['rejected'] }}</p>
    </article>
</section>

<div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
    {{-- Toolbar --}}
    <div class="flex flex-col gap-4 px-5 py-4 border-b border-gray-100 bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-sm font-semibold text-gray-800">Daftar Perjalanan Dinas</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('official-travels.create') }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                <i class="fas fa-plus"></i> Ajukan
            </a>
            @if($permissions['canExport'])
            <a href="{{ route('official-travels.export', request()->query()) }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                <i class="fas fa-file-excel"></i> Export
            </a>
            @endif
            @if($permissions['canBulkExport'] ?? false)
            <a href="{{ route('official-travels.bulk-export', request()->query()) }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-file-archive"></i> Bulk Export
            </a>
            @endif
            @if($permissions['canMarkPayment'])
            <button id="btn-mark-done"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700 hidden">
                <i class="fas fa-check-double"></i> Tandai Lunas
            </button>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('official-travels.index') }}" class="flex flex-wrap items-end gap-3 px-5 py-4 border-b border-gray-100">
        <div>
            <label class="block mb-1 text-xs text-gray-500">Status</label>
            <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-300">
                <option value="">Semua</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div>
            <label class="block mb-1 text-xs text-gray-500">Dari</label>
            <input type="date" name="from_date" value="{{ request('from_date') }}"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
        </div>
        <div>
            <label class="block mb-1 text-xs text-gray-500">Sampai</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5">
        </div>
        <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">Filter</button>
        <a href="{{ route('official-travels.index') }}" class="px-4 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Reset</a>
    </form>

    @if($permissions['canViewAllRequests'])
    <x-request-tabs />
    <div data-request-tab-panel="my">
        @include('official-travels._table', ['items' => $myOfficialTravels, 'showEmployee' => false])
    </div>
    <div data-request-tab-panel="all">
        @include('official-travels._table', ['items' => $allOfficialTravels, 'showEmployee' => true])
    </div>
    @else
    @include('official-travels._table', ['items' => $myOfficialTravels, 'showEmployee' => false])
    @endif
</div>

@if($permissions['canMarkPayment'])
<form id="form-mark-done" method="POST" action="{{ route('official-travels.marked') }}">
    @csrf @method('PATCH')
    <div id="mark-ids"></div>
</form>
@endif

@endsection

@push('scripts')
@if($permissions['canMarkPayment'])
<script>
const checkboxes = document.querySelectorAll('.mark-checkbox');
const btn = document.getElementById('btn-mark-done');
const form = document.getElementById('form-mark-done');
const idsDiv = document.getElementById('mark-ids');

function updateBtn() {
    const checked = [...checkboxes].filter(c => c.checked);
    btn?.classList.toggle('hidden', checked.length === 0);
}

checkboxes.forEach(c => c.addEventListener('change', updateBtn));

btn?.addEventListener('click', () => {
    idsDiv.innerHTML = '';
    checkboxes.forEach(c => {
        if (c.checked) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = c.value;
            idsDiv.appendChild(input);
        }
    });
    form.submit();
});
</script>
@endif
@endpush
