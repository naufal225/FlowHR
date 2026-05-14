@extends('layouts.app')

@section('title', 'Leave')
@section('header', 'Leave')
@section('subtitle', $permissions['canViewAllRequests'] ? 'Kelola semua pengajuan cuti' : 'Pengajuan cuti kamu')

@section('content')

{{-- Stats --}}
<section class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-5">
    <article class="p-4 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Total</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
    </article>
    <article class="p-4 bg-white border border-amber-100 bg-amber-50 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-amber-600 uppercase">Pending</p>
        <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['pending'] }}</p>
    </article>
    <article class="p-4 bg-white border border-emerald-100 bg-emerald-50 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-emerald-600 uppercase">Approved</p>
        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['approved'] }}</p>
    </article>
    <article class="p-4 bg-white border border-rose-100 bg-rose-50 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-rose-600 uppercase">Rejected</p>
        <p class="mt-1 text-2xl font-bold text-rose-700">{{ $stats['rejected'] }}</p>
    </article>
    <article class="p-4 bg-white border border-blue-100 bg-blue-50 shadow-sm rounded-2xl">
        <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">Sisa Cuti</p>
        <p class="mt-1 text-2xl font-bold text-blue-700">{{ $stats['sisa_cuti'] }}</p>
    </article>
</section>

{{-- Header & Filter --}}
<div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
    <div class="flex flex-col gap-4 px-5 py-4 border-b border-gray-100 bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-sm font-semibold text-gray-800">Daftar Cuti</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('leaves.create') }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                <i class="fas fa-plus"></i> Ajukan Cuti
            </a>
            @if($permissions['canExport'])
            <a href="{{ route('leaves.export', request()->query()) }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-emerald-600 hover:bg-emerald-700">
                <i class="fas fa-file-excel"></i> Export
            </a>
            @endif
            @if($permissions['canBulkExport'])
            <a href="{{ route('leaves.bulk-export') }}"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-file-archive"></i> Bulk PDF
            </a>
            @endif
        </div>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('leaves.index') }}" class="flex flex-wrap items-end gap-3 px-5 py-4 border-b border-gray-100">
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
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-300">
        </div>
        <div>
            <label class="block mb-1 text-xs text-gray-500">Sampai</label>
            <input type="date" name="to_date" value="{{ request('to_date') }}"
                class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-300">
        </div>
        <button type="submit"
            class="px-4 py-1.5 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
            Filter
        </button>
        <a href="{{ route('leaves.index') }}"
            class="px-4 py-1.5 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
            Reset
        </a>
    </form>

    @if($permissions['canViewAllRequests'])
    {{-- Tabs --}}
    <x-request-tabs />

    {{-- My Requests Tab --}}
    <div id="panel-my">
        @include('leaves._table', ['leaves' => $myLeaves, 'showEmployee' => false, 'tab' => 'my'])
    </div>

    {{-- All Requests Tab --}}
    <div id="panel-all" class="hidden">
        @include('leaves._table', ['leaves' => $allLeaves, 'showEmployee' => true, 'tab' => 'all'])
    </div>

    @else
    {{-- Employee: only own --}}
    @include('leaves._table', ['leaves' => $myLeaves, 'showEmployee' => false, 'tab' => 'my'])
    @endif
</div>

@endsection
