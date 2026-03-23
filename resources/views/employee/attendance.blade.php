@extends('Employee.layouts.app')

@section('title', 'Attendance')
@section('header', 'Attendance')
@section('subtitle', 'Attendance monitoring and correction')

@section('content')
@php
    $statusLabels = [
        'present' => 'Present',
        'late' => 'Late',
        'outside_radius' => 'Outside Radius',
        'invalid' => 'Invalid',
        'absent' => 'Absent',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    $statusClasses = [
        'present' => 'bg-green-100 text-green-700',
        'late' => 'bg-yellow-100 text-yellow-700',
        'outside_radius' => 'bg-orange-100 text-orange-700',
        'invalid' => 'bg-red-100 text-red-700',
        'absent' => 'bg-gray-100 text-gray-700',
        'pending' => 'bg-yellow-100 text-yellow-700',
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900">Attendance Monitoring</h1>
            <p class="text-neutral-600">Track attendance logs and submit attendance corrections.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('employee.attendance.index', ['month' => now()->format('Y-m')]) }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-300 rounded-xl bg-primary-600 hover:bg-primary-700 hover:shadow-md">
                <i class="mr-2 fas fa-calendar-alt"></i>
                Current Month
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-success-100 text-success-600">
                    <i class="text-lg fas fa-user-check"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Present</p>
                    <p class="text-lg font-semibold text-neutral-900">{{ $summary['present'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-warning-100 text-warning-600">
                    <i class="text-lg fas fa-clock"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Late</p>
                    <p class="text-lg font-semibold text-neutral-900">{{ $summary['late'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="text-lg fas fa-location-dot"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Outside Radius</p>
                    <p class="text-lg font-semibold text-neutral-900">{{ $summary['outside_radius'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-error-100 text-error-600">
                    <i class="text-lg fas fa-triangle-exclamation"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Invalid</p>
                    <p class="text-lg font-semibold text-neutral-900">{{ $summary['invalid'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-error-100 text-error-600">
                    <i class="text-lg fas fa-flag"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-neutral-500">Flagged</p>
                    <p class="text-lg font-semibold text-neutral-900">{{ $summary['flagged'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-neutral-900">Filter Attendance Log</h2>
                <p class="mt-1 text-sm text-neutral-600">Filter log by month and status.</p>
            </div>

            <form method="GET" action="{{ route('employee.attendance.index') }}"
                class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label for="month" class="block mb-2 text-sm font-medium text-neutral-700">Month</label>
                    <input type="month" id="month" name="month" value="{{ request('month', $month->format('Y-m')) }}"
                        class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label for="status" class="block mb-2 text-sm font-medium text-neutral-700">Status</label>
                    <select id="status" name="status"
                        class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">All Status</option>
                        @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}" {{ request('status') === $statusOption ? 'selected' : '' }}>
                            {{ $statusLabels[$statusOption] ?? ucfirst($statusOption) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-300 bg-blue-600 shadow-sm rounded-xl hover:bg-blue-700 hover:shadow-md">
                        <i class="mr-2 fas fa-search"></i>
                        Filter
                    </button>
                    <a href="{{ route('employee.attendance.index') }}"
                        class="flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-300 bg-gray-100 shadow-sm rounded-xl hover:bg-gray-200 hover:shadow-md">
                        <i class="mr-2 fas fa-refresh"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="pt-4 mt-4 border-t border-gray-200">
            <p class="text-xs text-neutral-500">
                <span class="font-semibold">Tips:</span> Pilih bulan yang benar sebelum submit koreksi agar log yang dipilih tidak keliru.
            </p>
        </div>
    </div>

    <div class="mt-6 mb-10 transform scale-y-50 border-t border-gray-300/80"></div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-soft">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-neutral-900">Attendance Log - {{ $month->translatedFormat('F Y') }}</h2>
            <p class="mt-1 text-sm text-neutral-600">Mode dan status mengikuti data yang dikirimkan dari aplikasi mobile.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Date
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Shift
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">
                            Accuracy
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Check
                            In</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Check
                            Out</th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Mode
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase">Status
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($attendanceRecords as $record)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800">{{ $record->attendance_date?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $record->shift?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $record->accuracy !== null ? number_format($record->accuracy, 1). ' m' : '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $record->check_in_time?->format('H:i') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $record->check_out_time?->format('H:i') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 uppercase">{{ $record->mode }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$record->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$record->status] ?? ucfirst(str_replace('_', ' ', $record->status))
                                    }}
                                </span>
                                @if($record->flagged)
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-semibold text-red-700 rounded-full bg-red-100">
                                    Flagged
                                </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="text-neutral-400">
                                <i class="mb-3 text-3xl fas fa-calendar-xmark"></i>
                                <p class="text-base font-medium text-neutral-500">No attendance logs found</p>
                                <p class="text-sm">Try selecting another month or reset the current filters.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendanceRecords->hasPages())
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $attendanceRecords->links() }}
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900">Submit Attendance Correction</h2>
            <p class="mt-1 text-sm text-neutral-600">Pilih log absensi, lalu isi koreksi check in/check out sesuai data sebenarnya.</p>

            <div class="p-3 mt-4 border border-blue-100 rounded-lg bg-blue-50">
                <p class="text-xs text-blue-700">
                    Satu log absensi hanya bisa memiliki satu koreksi dengan status <span class="font-semibold">pending</span>.
                </p>
            </div>

            <form method="POST" action="{{ route('employee.attendance.corrections.store') }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="attendance_record_id" class="block mb-2 text-sm font-medium text-neutral-700">Log
                        Absensi</label>
                    <select id="attendance_record_id" name="attendance_record_id"
                        class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Pilih log absensi</option>
                        @foreach($correctionCandidates as $candidate)
                        <option value="{{ $candidate->id }}" {{ (string) old('attendance_record_id') ===
                            (string) $candidate->id ? 'selected' : '' }}
                            data-checkin="{{ $candidate->check_in_time?->format('Y-m-d\TH:i') }}"
                            data-checkout="{{ $candidate->check_out_time?->format('Y-m-d\TH:i') }}">
                            {{ $candidate->attendance_date?->format('d M Y') ?? '-' }} | IN {{
                            $candidate->check_in_time?->format('H:i') ?? '-' }} | OUT {{
                            $candidate->check_out_time?->format('H:i') ?? '-' }}
                        </option>
                        @endforeach
                    </select>
                    @error('attendance_record_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="requested_check_in_time" class="block mb-2 text-sm font-medium text-neutral-700">Koreksi
                            Check In</label>
                        <input type="datetime-local" id="requested_check_in_time" name="requested_check_in_time"
                            value="{{ old('requested_check_in_time') }}"
                            class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('requested_check_in_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="requested_check_out_time"
                            class="block mb-2 text-sm font-medium text-neutral-700">Koreksi Check Out</label>
                        <input type="datetime-local" id="requested_check_out_time" name="requested_check_out_time"
                            value="{{ old('requested_check_out_time') }}"
                            class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @error('requested_check_out_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="reason" class="block mb-2 text-sm font-medium text-neutral-700">Alasan Koreksi</label>
                    <textarea id="reason" name="reason" rows="4"
                        class="w-full rounded-xl p-2.5 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        placeholder="Contoh: lupa check out karena meeting eksternal.">{{ old('reason') }}</textarea>
                    @error('reason')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white transition-all duration-300 bg-blue-600 shadow-sm rounded-xl hover:bg-blue-700 hover:shadow-md">
                    <i class="mr-2 fas fa-paper-plane"></i>
                    Submit Koreksi
                </button>
            </form>
        </div>

        <div class="p-6 bg-white border rounded-xl shadow-soft border-neutral-200">
            <h2 class="text-lg font-semibold text-neutral-900">Correction History</h2>
            <p class="mt-1 text-sm text-neutral-600">Pantau status approval koreksi absensi yang sudah diajukan.</p>

            <div class="mt-5 space-y-3">
                @forelse($corrections as $correction)
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $correction->attendance_date?->format('d M Y') ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-500">
                                IN: {{ $correction->requested_check_in_time?->format('d M Y H:i') ?? '-' }} | OUT: {{
                                $correction->requested_check_out_time?->format('d M Y H:i') ?? '-' }}
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$correction->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $statusLabels[$correction->status] ?? ucfirst($correction->status) }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">{{ $correction->reason }}</p>
                    @if($correction->reviewer_note)
                    <p class="mt-2 text-xs text-gray-500">
                        Catatan reviewer: {{ $correction->reviewer_note }}
                    </p>
                    @endif
                    <p class="mt-2 text-xs text-gray-400">
                        Diajukan: {{ $correction->created_at?->format('d M Y H:i') }}
                    </p>
                </div>
                @empty
                <div class="py-8 text-sm text-center text-gray-500 border border-dashed border-gray-300 rounded-lg">
                    Belum ada pengajuan koreksi absensi.
                </div>
                @endforelse
            </div>

            @if($corrections->hasPages())
            <div class="pt-4 mt-4 border-t border-gray-100">
                {{ $corrections->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectLog = document.getElementById('attendance_record_id');
        const checkInInput = document.getElementById('requested_check_in_time');
        const checkOutInput = document.getElementById('requested_check_out_time');

        if (!selectLog || !checkInInput || !checkOutInput) return;

        function syncCorrectionDefault() {
            const selected = selectLog.options[selectLog.selectedIndex];
            if (!selected) return;

            const checkIn = selected.getAttribute('data-checkin');
            const checkOut = selected.getAttribute('data-checkout');

            if (!checkInInput.value && checkIn) {
                checkInInput.value = checkIn;
            }

            if (!checkOutInput.value && checkOut) {
                checkOutInput.value = checkOut;
            }
        }

        selectLog.addEventListener('change', function() {
            checkInInput.value = '';
            checkOutInput.value = '';
            syncCorrectionDefault();
        });

        syncCorrectionDefault();
    });
</script>
@endpush
