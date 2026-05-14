<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                @if($showEmployee ?? false)
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                @endif
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Check In</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Check Out</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($records ?? [] as $record)
            @php
                $statusLabel = $record['status'] ?? ($record->status ?? null);
                $checkIn     = $record['check_in'] ?? ($record->check_in ?? null);
                $checkOut    = $record['check_out'] ?? ($record->check_out ?? null);
                $date        = $record['date'] ?? ($record->date ?? null);
                $recordId    = $record['id'] ?? ($record->id ?? null);
            @endphp
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-3 text-xs font-medium text-gray-800">
                    {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '–' }}
                    <p class="text-gray-400 font-normal">{{ $date ? \Carbon\Carbon::parse($date)->format('l') : '' }}</p>
                </td>
                @if($showEmployee ?? false)
                <td class="px-5 py-3">
                    @php
                        $emp = $record['employee'] ?? ($record->employee ?? null);
                        $empName = $record['employee_name'] ?? ($emp?->name ?? '–');
                        $empEmail = $record['employee_email'] ?? ($emp?->email ?? '');
                    @endphp
                    <p class="text-sm font-medium text-gray-800">{{ $empName }}</p>
                    <p class="text-xs text-gray-400">{{ $empEmail }}</p>
                </td>
                @endif
                <td class="px-5 py-3 text-xs text-gray-700">
                    {{ $checkIn ? \Carbon\Carbon::parse($checkIn)->format('H:i') : '–' }}
                </td>
                <td class="px-5 py-3 text-xs text-gray-700">
                    {{ $checkOut ? \Carbon\Carbon::parse($checkOut)->format('H:i') : '–' }}
                </td>
                <td class="px-5 py-3">
                    @if($statusLabel === 'present')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Hadir
                    </span>
                    @elseif($statusLabel === 'late')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-amber-500 rounded-full"></span>Terlambat
                    </span>
                    @elseif($statusLabel === 'absent')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-rose-500 rounded-full"></span>Absen
                    </span>
                    @elseif($statusLabel === 'incomplete')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-blue-500 rounded-full"></span>Tidak Lengkap
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                        <span class="w-1.5 h-1.5 mr-1 bg-gray-400 rounded-full"></span>{{ ucfirst($statusLabel ?? '–') }}
                    </span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    @if($recordId)
                    <a href="{{ route('attendance.show', $recordId) }}"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100">
                        <i class="fas fa-eye text-[10px]"></i> Detail
                    </a>
                    @else
                    <span class="text-gray-300 text-xs">–</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-12 text-center">
                    <i class="mb-2 text-3xl text-gray-200 fas fa-calendar-times block"></i>
                    <p class="text-sm text-gray-400">Tidak ada data kehadiran.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(isset($records) && method_exists($records, 'hasPages') && $records->hasPages())
<div class="px-5 py-4 border-t border-gray-100">{{ $records->appends(request()->query())->links() }}</div>
@endif
