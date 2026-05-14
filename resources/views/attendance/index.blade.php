@extends('layouts.app')

@section('title', 'Attendance')
@section('header', 'Attendance')
@section('subtitle', 'Rekap kehadiran kamu')

@section('content')
<div class="space-y-6">

    {{-- Today's Attendance Card --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <article class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl sm:col-span-3">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Hari Ini</p>
                    <p class="mt-1 text-base font-semibold text-gray-800">{{ now()->format('l, d F Y') }}</p>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-0.5">Check In</p>
                        @if(isset($todayAttendance) && $todayAttendance?->check_in)
                        <p class="text-lg font-bold text-emerald-600">{{ \Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') }}</p>
                        @else
                        <p class="text-lg font-bold text-gray-300">––:––</p>
                        @endif
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-0.5">Check Out</p>
                        @if(isset($todayAttendance) && $todayAttendance?->check_out)
                        <p class="text-lg font-bold text-blue-600">{{ \Carbon\Carbon::parse($todayAttendance->check_out)->format('H:i') }}</p>
                        @else
                        <p class="text-lg font-bold text-gray-300">––:––</p>
                        @endif
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-400 mb-0.5">Status</p>
                        @if(isset($todayAttendance) && $todayAttendance)
                            @if($todayAttendance->check_out)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Hadir
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                <span class="w-1.5 h-1.5 mr-1 bg-amber-500 rounded-full"></span>Sedang Hadir
                            </span>
                            @endif
                        @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                            <span class="w-1.5 h-1.5 mr-1 bg-gray-400 rounded-full"></span>Belum Absen
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </article>
    </section>

    {{-- Monthly Stats --}}
    @if(isset($stats))
    <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <article class="p-4 bg-white border border-gray-100 shadow-sm rounded-2xl">
            <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Hadir</p>
            <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $stats['present'] ?? 0 }}</p>
        </article>
        <article class="p-4 bg-amber-50 border border-amber-100 shadow-sm rounded-2xl">
            <p class="text-xs font-semibold tracking-wide text-amber-600 uppercase">Terlambat</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['late'] ?? 0 }}</p>
        </article>
        <article class="p-4 bg-rose-50 border border-rose-100 shadow-sm rounded-2xl">
            <p class="text-xs font-semibold tracking-wide text-rose-600 uppercase">Absen</p>
            <p class="mt-1 text-2xl font-bold text-rose-700">{{ $stats['absent'] ?? 0 }}</p>
        </article>
        <article class="p-4 bg-blue-50 border border-blue-100 shadow-sm rounded-2xl">
            <p class="text-xs font-semibold tracking-wide text-blue-600 uppercase">Tidak Lengkap</p>
            <p class="mt-1 text-2xl font-bold text-blue-700">{{ $stats['incomplete'] ?? 0 }}</p>
        </article>
    </section>
    @endif

    <div class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl">
        {{-- Toolbar --}}
        <div class="flex flex-col gap-4 px-5 py-4 border-b border-gray-100 bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Riwayat Kehadiran</h3>
            <div class="flex flex-wrap gap-2">
                @if(isset($permissions['canRequestCorrection']) && $permissions['canRequestCorrection'])
                <a href="{{ route('attendance.correction.create') }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">
                    <i class="fas fa-pen"></i> Ajukan Koreksi
                </a>
                @endif
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('attendance.index') }}" class="flex flex-wrap items-end gap-3 px-5 py-4 border-b border-gray-100">
            <div>
                <label class="block mb-1 text-xs text-gray-500">Bulan</label>
                <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-300">
            </div>
            @if(isset($permissions['canViewAllAttendance']) && $permissions['canViewAllAttendance'])
            <div>
                <label class="block mb-1 text-xs text-gray-500">Status</label>
                <select name="status" class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary-300">
                    <option value="">Semua</option>
                    <option value="present"    {{ request('status') === 'present'    ? 'selected' : '' }}>Hadir</option>
                    <option value="late"       {{ request('status') === 'late'       ? 'selected' : '' }}>Terlambat</option>
                    <option value="absent"     {{ request('status') === 'absent'     ? 'selected' : '' }}>Absen</option>
                    <option value="incomplete" {{ request('status') === 'incomplete' ? 'selected' : '' }}>Tidak Lengkap</option>
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg bg-primary-600 hover:bg-primary-700">Filter</button>
            <a href="{{ route('attendance.index') }}" class="px-4 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Reset</a>
        </form>

        @if(isset($permissions['canViewAllAttendance']) && $permissions['canViewAllAttendance'])
        <x-request-tabs my-label="Kehadiran Saya" all-label="Semua Karyawan" />
        <div data-request-tab-panel="my">
            @include('attendance._table', ['records' => $myAttendances ?? collect(), 'showEmployee' => false])
        </div>
        <div data-request-tab-panel="all">
            @include('attendance._table', ['records' => $allAttendances ?? collect(), 'showEmployee' => true])
        </div>
        @else
        @include('attendance._table', ['records' => $myAttendances ?? collect(), 'showEmployee' => false])
        @endif
    </div>
</div>
@endsection
