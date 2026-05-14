@php $user = Auth::user(); $permissions = $permissions ?? []; @endphp
<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                @if($permissions['canMarkPayment'] ?? false)
                <th class="px-4 py-3 w-8"></th>
                @endif
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                @if($showEmployee)
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Karyawan</th>
                @endif
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Customer</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Durasi</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Hari</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                @if($permissions['canMarkPayment'] ?? false)
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lunas</th>
                @endif
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($items ?? [] as $item)
            <tr class="hover:bg-gray-50 transition-colors">
                @if($permissions['canMarkPayment'] ?? false)
                <td class="px-4 py-3">
                    @if(!$item->marked_down)
                    <input type="checkbox" class="mark-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-400"
                        value="{{ $item->id }}">
                    @endif
                </td>
                @endif
                <td class="px-5 py-3 text-gray-600 font-mono text-xs">#{{ $item->id }}</td>
                @if($showEmployee)
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        @if($item->employee?->url_profile)
                        <img src="{{ $item->employee->url_profile }}" class="w-7 h-7 rounded-full object-cover border border-gray-200" alt="">
                        @else
                        <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-xs font-medium text-primary-700">
                            {{ strtoupper(substr($item->employee?->name ?? '?', 0, 1)) }}
                        </div>
                        @endif
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $item->employee?->name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->employee?->division?->name }}</p>
                        </div>
                    </div>
                </td>
                @endif
                <td class="px-5 py-3 text-xs text-gray-700 font-medium">{{ $item->customer ?? '–' }}</td>
                <td class="px-5 py-3">
                    <p class="text-xs text-gray-800 font-medium">
                        {{ \Carbon\Carbon::parse($item->date_start)->format('d M Y') }}
                    </p>
                    <p class="text-xs text-gray-500">
                        s/d {{ \Carbon\Carbon::parse($item->date_end)->format('d M Y') }}
                    </p>
                </td>
                <td class="px-5 py-3 text-xs text-gray-700">
                    @php
                        $days = \Carbon\Carbon::parse($item->date_start)->diffInDays(\Carbon\Carbon::parse($item->date_end)) + 1;
                    @endphp
                    {{ $days }} hari
                </td>
                <td class="px-5 py-3">
                    @php
                        $s1 = $item->status_1; $s2 = $item->status_2;
                        $final = ($s1 === 'approved' && $s2 === 'approved') ? 'approved'
                               : ($s1 === 'rejected' || $s2 === 'rejected' ? 'rejected' : 'pending');
                    @endphp
                    @if($final === 'approved')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="w-1.5 h-1.5 mr-1 bg-emerald-500 rounded-full"></span>Approved
                    </span>
                    @elseif($final === 'rejected')
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-rose-500 rounded-full"></span>Rejected
                    </span>
                    @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600">
                        <span class="w-1.5 h-1.5 mr-1 bg-amber-500 rounded-full"></span>Pending
                    </span>
                    @endif
                    <div class="mt-1 flex gap-1">
                        <span class="text-[10px] px-1.5 py-0.5 rounded {{ $s1 === 'approved' ? 'bg-emerald-50 text-emerald-600' : ($s1 === 'rejected' ? 'bg-rose-50 text-rose-500' : 'bg-gray-100 text-gray-500') }}">
                            TL: {{ ucfirst($s1) }}
                        </span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded {{ $s2 === 'approved' ? 'bg-emerald-50 text-emerald-600' : ($s2 === 'rejected' ? 'bg-rose-50 text-rose-500' : 'bg-gray-100 text-gray-500') }}">
                            Mgr: {{ ucfirst($s2) }}
                        </span>
                    </div>
                </td>
                @if($permissions['canMarkPayment'] ?? false)
                <td class="px-5 py-3 text-xs">
                    @if($item->marked_down)
                    <span class="text-emerald-600 font-medium"><i class="fas fa-check mr-1"></i>Lunas</span>
                    @else
                    <span class="text-gray-400">–</span>
                    @endif
                </td>
                @endif
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('official-travels.show', $item) }}"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100">
                        <i class="fas fa-eye text-[10px]"></i> Detail
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-5 py-12 text-center">
                    <i class="mb-2 text-3xl text-gray-200 fas fa-inbox block"></i>
                    <p class="text-sm text-gray-400">Tidak ada data perjalanan dinas.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if(isset($items) && method_exists($items, 'hasPages') && $items->hasPages())
<div class="px-5 py-4 border-t border-gray-100">{{ $items->appends(request()->query())->links() }}</div>
@endif
