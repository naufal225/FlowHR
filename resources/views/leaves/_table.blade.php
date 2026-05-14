@php
    $user = Auth::user();
    $permissions = $permissions ?? $user->permissions()->toArray();
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">ID</th>
                @if($showEmployee)
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Karyawan</th>
                @endif
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Durasi</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Approver</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($leaves as $leave)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-gray-600 font-mono text-xs">#{{ $leave->id }}</td>
                @if($showEmployee)
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        @if($leave->employee?->url_profile)
                        <img src="{{ $leave->employee->url_profile }}" alt=""
                            class="w-7 h-7 rounded-full object-cover border border-gray-200">
                        @else
                        <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-xs font-medium text-primary-700">
                            {{ strtoupper(substr($leave->employee?->name ?? '?', 0, 1)) }}
                        </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $leave->employee?->name }}</p>
                            <p class="text-xs text-gray-400">{{ $leave->employee?->division?->name }}</p>
                        </div>
                    </div>
                </td>
                @endif
                <td class="px-5 py-3 text-gray-700 text-xs">
                    {{ \Carbon\Carbon::parse($leave->date_start)->format('d M Y') }}
                    @if($leave->date_start !== $leave->date_end)
                    – {{ \Carbon\Carbon::parse($leave->date_end)->format('d M Y') }}
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-700 text-xs">
                    {{ \Carbon\Carbon::parse($leave->date_start)->diffInDays(\Carbon\Carbon::parse($leave->date_end)) + 1 }} hari
                </td>
                <td class="px-5 py-3">
                    @if($leave->status_1 === 'approved')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Approved
                    </span>
                    @elseif($leave->status_1 === 'rejected')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-rose-500 rounded-full"></span>Rejected
                    </span>
                    @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-amber-500 rounded-full"></span>Pending
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs text-gray-500">
                    {{ $leave->approver1?->name ?? '–' }}
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('leaves.show', $leave) }}"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100">
                        <i class="fas fa-eye text-[10px]"></i> Detail
                    </a>
                    @if($leave->status_1 === 'pending' && (int)$leave->employee_id === (int)Auth::id())
                    <a href="{{ route('leaves.edit', $leave) }}"
                        class="ml-1 inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-pen text-[10px]"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $showEmployee ? 7 : 6 }}" class="px-5 py-12 text-center">
                    <i class="mb-2 text-3xl text-gray-200 fas fa-inbox block"></i>
                    <p class="text-sm text-gray-400">Tidak ada data cuti.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($leaves && $leaves->hasPages())
<div class="px-5 py-4 border-t border-gray-100">
    {{ $leaves->appends(request()->query())->links() }}
</div>
@endif
