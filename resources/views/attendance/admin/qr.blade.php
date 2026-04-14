@extends($layout)

@section('title', 'Attendance QR')
@section('header', 'Attendance QR')
@section('subtitle', 'Monitor active QR attendance token')

@section('content')
<div class="space-y-6">

    @include('components.attendance.page-header', [
        'title' => $headerTitle,
        'subtitle' => $headerSubtitle,
    ])

    @include('components.attendance.flash-messages')

    @if($generatedDisplayUrl !== '')
        <div class="p-6 border shadow-sm rounded-3xl border-emerald-200 bg-emerald-50">
            <h2 class="text-base font-semibold text-emerald-900">Office Display Link Created</h2>
            <p class="mt-1 text-sm text-emerald-800">Simpan link ini untuk dipasang di TV kantor. Link hanya ditampilkan sekali setelah dibuat.</p>
            <div class="grid grid-cols-1 gap-3 mt-4">
                <div class="px-4 py-3 bg-white border rounded-2xl border-emerald-200">
                    <p class="text-xs font-semibold tracking-wide uppercase text-emerald-700">Display URL</p>
                    <p class="mt-1 text-sm break-all text-slate-700">{{ $generatedDisplayUrl }}</p>
                    <div class="mt-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 px-3 py-2 text-xs font-semibold transition border rounded-xl border-emerald-200 bg-emerald-100 text-emerald-800 hover:bg-emerald-200"
                            data-copy-url="{{ $generatedDisplayUrl }}"
                        >
                            <i class="fa-regular fa-copy"></i>
                            <span>Copy Display URL</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="p-6 bg-white border shadow-sm rounded-3xl border-slate-200">
        <form method="GET" action="{{ route($routePrefix . '.attendance.qr') }}" class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <label class="block mb-2 text-sm font-medium text-slate-700" for="office_location_id">Office</label>
                <select id="office_location_id" name="office_location_id"
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:min-w-[18rem]">
                    @foreach($officeLocations as $office)
                        <option value="{{ $office->id }}" @selected($selectedOffice?->id === $office->id)>{{ $office->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                <i class="fa-solid fa-filter"></i>
                <span>Apply</span>
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.1fr_1fr] items-start">
        <div class="p-6 bg-white border shadow-sm rounded-3xl border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Current QR</h2>
                    <p class="mt-1 text-sm text-slate-600">Attendance mobile flow reads the raw token encoded inside this QR.</p>
                </div>
                <span id="attendance-qr-status"
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $qrCard['status_classes'] }}">
                    {{ $qrCard['status_label'] }}
                </span>
            </div>

            <div id="attendance-qr-active-state" class="mt-6 {{ $qrCard['has_token'] ? '' : 'hidden' }}">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <div class="flex items-center justify-center p-5 border h-72 w-72 rounded-3xl border-slate-200 bg-slate-50">
                        <div id="attendance-qr-canvas" data-token="{{ $qrCard['token'] }}"></div>
                    </div>
                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                        @include('components.attendance.info-item', ['label' => 'Office', 'value' => $qrCard['office_name'], 'helper' => $qrCard['office_address'], 'valueId' => 'attendance-qr-office-name', 'helperId' => 'attendance-qr-office-address'])
                        @include('components.attendance.info-item', ['label' => 'Generated At', 'value' => $qrCard['generated_at'], 'valueId' => 'attendance-qr-generated-at'])
                        @include('components.attendance.info-item', ['label' => 'Expires At', 'value' => $qrCard['expires_at'], 'valueId' => 'attendance-qr-expires-at'])
                        @include('components.attendance.info-item', ['label' => 'Countdown', 'value' => $qrCard['expires_in'], 'valueId' => 'attendance-qr-countdown'])
                        @include('components.attendance.info-item', ['label' => 'Rotation', 'value' => $qrCard['rotation_seconds'] ? $qrCard['rotation_seconds'] . ' sec' : '-', 'valueId' => 'attendance-qr-rotation'])
                        @include('components.attendance.info-item', ['label' => 'Token', 'value' => $qrCard['masked_token'], 'valueId' => 'attendance-qr-token'])
                    </div>
                </div>
            </div>

            <div id="attendance-qr-empty-state" class="mt-6 {{ $qrCard['has_token'] ? 'hidden' : '' }}">
                @include('components.attendance.empty-state', ['title' => 'No QR token available', 'description' => 'Generate the first active QR token after an attendance setting is configured for the selected office.', 'icon' => 'fa-solid fa-qrcode'])
            </div>

             @if($selectedOffice && $qrCard['rotation_seconds'])
                <div class="flex flex-wrap gap-3 mt-5">
                    <form method="POST" action="{{ route($routePrefix . '.attendance.qr.regenerate') }}">
                        @csrf
                        <input type="hidden" name="office_location_id" value="{{ $selectedOffice->id }}">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-sky-700">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Regenerate QR</span>
                        </button>
                    </form>
                    <form method="POST" action="{{ route($routePrefix . '.attendance.qr.invalidate') }}">
                        @csrf
                        <input type="hidden" name="office_location_id" value="{{ $selectedOffice->id }}">
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                            <i class="fa-solid fa-ban"></i>
                            <span>Invalidate QR</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="p-6 bg-white border shadow-sm rounded-3xl border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">QR Info</h2>
                <div class="grid grid-cols-1 gap-3 mt-5">
                    @include('components.attendance.info-item', ['label' => 'Office', 'value' => $qrCard['office_name'], 'helper' => $qrCard['office_address']])
                    @include('components.attendance.info-item', ['label' => 'Rotation Interval', 'value' => $qrCard['rotation_seconds'] ? $qrCard['rotation_seconds'] . ' sec' : '-'])
                    @include('components.attendance.info-item', ['label' => 'Minimum Accuracy', 'value' => $qrCard['min_accuracy'] ? $qrCard['min_accuracy'] . ' m' : '-'])
                    @include('components.attendance.info-item', ['label' => 'Security Note', 'value' => 'Do not expose the raw token outside the QR display area.'])
                </div>
            </div>

            @if($selectedOffice)
                <div class="p-6 bg-white border shadow-sm rounded-3xl border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Office Display Sessions</h2>
                    <p class="mt-1 text-sm text-slate-600">Create signed display-only links for office TVs without exposing admin page access.</p>

                    <form method="POST" action="{{ route($routePrefix . '.attendance.qr.display-sessions.store') }}" class="grid grid-cols-1 gap-3 mt-5 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="office_location_id" value="{{ $selectedOffice->id }}">
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Display Name</span>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', 'TV Display ' . $selectedOffice->code) }}"
                                maxlength="120"
                                required
                                class="w-full text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                                placeholder="Example: Lobby TV 1"
                            >
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium text-slate-700">Session TTL (days)</span>
                            <input
                                type="number"
                                name="ttl_days"
                                min="1"
                                max="365"
                                value="{{ old('ttl_days', $displaySessionDefaults['ttl_days']) }}"
                                class="w-full text-sm shadow-sm rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500"
                            >
                        </label>
                        <div class="sm:col-span-2">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                                <i class="fa-solid fa-display"></i>
                                <span>Create Display Session</span>
                            </button>
                        </div>
                    </form>

                    <div class="mt-5 overflow-hidden border rounded-2xl border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-slate-600">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold text-left">Name</th>
                                        <th class="px-4 py-3 font-semibold text-left">Status</th>
                                        <th class="px-4 py-3 font-semibold text-left">Last Seen</th>
                                        <th class="px-4 py-3 font-semibold text-left">Expires</th>
                                        <th class="px-4 py-3 font-semibold text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($displaySessions as $displaySession)
                                        @php
                                            $isRevoked = $displaySession->revoked_at !== null;
                                            $isExpired = !$isRevoked && optional($displaySession->expires_at)->isPast();
                                            $statusLabel = $isRevoked ? 'Revoked' : ($isExpired ? 'Expired' : 'Active');
                                            $statusClass = $isRevoked
                                                ? 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-200'
                                                : ($isExpired
                                                    ? 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'
                                                    : 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200');
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-slate-800">{{ $displaySession->name }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                                    {{ $statusLabel }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ optional($displaySession->last_seen_at)->translatedFormat('d M Y H:i') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ optional($displaySession->expires_at)->translatedFormat('d M Y H:i') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap items-center justify-end gap-2">
                                                    @if($displaySession->display_url)
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold transition border rounded-xl border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100"
                                                            data-open-display-link-modal="true"
                                                            data-display-name="{{ $displaySession->name }}"
                                                            data-display-url="{{ $displaySession->display_url }}"
                                                        >
                                                            <i class="fa-solid fa-link"></i>
                                                            <span>View Link</span>
                                                        </button>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold border rounded-xl border-slate-200 bg-slate-50 text-slate-500">
                                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                                            <span>Link unavailable</span>
                                                        </span>
                                                    @endif

                                                    @if(!$isRevoked && !$isExpired)
                                                        <form method="POST" action="{{ route($routePrefix . '.attendance.qr.display-sessions.revoke', $displaySession->id) }}">
                                                            @csrf
                                                            <input type="hidden" name="office_location_id" value="{{ $selectedOffice->id }}">
                                                            <button type="submit"
                                                                class="inline-flex items-center gap-1 px-3 py-2 text-xs font-semibold transition border rounded-xl border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300 hover:bg-rose-100">
                                                                <i class="fa-solid fa-ban"></i>
                                                                <span>Revoke</span>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-xs text-slate-400">No action</span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">
                                                Belum ada display session untuk office ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(!($selectedOffice && $qrCard['rotation_seconds']))
                @include('components.attendance.state-panel', [
                    'title' => 'QR actions unavailable',
                    'description' => 'Configure an active attendance setting for the selected office before generating or rotating QR tokens.',
                    'icon' => 'fa-solid fa-gear',
                    'classes' => 'border-amber-200 bg-amber-50',
                    'iconClasses' => 'bg-amber-100 text-amber-700',
                ])
            @endif
        </div>
    </div>
</div>

<div id="display-link-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4">
    <div class="absolute inset-0 bg-slate-900/55" data-close-display-link-modal="true"></div>
    <div class="relative z-10 w-full max-w-2xl p-6 bg-white border shadow-2xl rounded-3xl border-slate-200">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Display URL</h3>
                <p id="display-link-modal-name" class="mt-1 text-sm text-slate-600">-</p>
            </div>
            <button type="button"
                class="inline-flex items-center justify-center transition border h-9 w-9 rounded-xl border-slate-200 text-slate-500 hover:bg-slate-50"
                data-close-display-link-modal="true"
                aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-4 mt-4 border rounded-2xl border-slate-200 bg-slate-50">
            <p id="display-link-modal-url" class="text-sm break-all text-slate-700">-</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 mt-4">
            <button
                id="display-link-modal-copy"
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold transition border rounded-xl border-sky-200 bg-sky-50 text-sky-700 hover:border-sky-300 hover:bg-sky-100"
            >
                <i class="fa-regular fa-copy"></i>
                <span>Copy Link</span>
            </button>
            <button
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold transition bg-white border rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50"
                data-close-display-link-modal="true"
            >
                <span>Close</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusUrl = @json(route($routePrefix . '.attendance.qr.status', array_filter(['office_location_id' => $selectedOffice?->id])));
        const statusBadge = document.getElementById('attendance-qr-status');
        const activeState = document.getElementById('attendance-qr-active-state');
        const emptyState = document.getElementById('attendance-qr-empty-state');
        const canvas = document.getElementById('attendance-qr-canvas');
        const officeName = document.getElementById('attendance-qr-office-name');
        const officeAddress = document.getElementById('attendance-qr-office-address');
        const generatedAt = document.getElementById('attendance-qr-generated-at');
        const expiresAt = document.getElementById('attendance-qr-expires-at');
        const countdown = document.getElementById('attendance-qr-countdown');
        const rotation = document.getElementById('attendance-qr-rotation');
        const token = document.getElementById('attendance-qr-token');
        const linkModal = document.getElementById('display-link-modal');
        const linkModalName = document.getElementById('display-link-modal-name');
        const linkModalUrl = document.getElementById('display-link-modal-url');
        const linkModalCopy = document.getElementById('display-link-modal-copy');

        let qrCard = @json($qrCard);
        let isRefreshing = false;
        let selectedDisplayUrl = '';

        function formatRemaining(totalSeconds) {
            if (totalSeconds <= 0) {
                return 'Expired';
            }

            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m ${seconds}s`;
            }

            if (minutes > 0) {
                return `${minutes}m ${seconds}s`;
            }

            return `${seconds}s`;
        }

        function renderQr(tokenValue) {
            if (!canvas) {
                return;
            }

            canvas.innerHTML = '';
            canvas.dataset.token = tokenValue || '';

            if (!tokenValue || typeof QRCode === 'undefined') {
                return;
            }

            new QRCode(canvas, {
                text: tokenValue,
                width: 220,
                height: 220,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        }

        async function copyText(text, triggerButton) {
            if (!text) {
                return;
            }

            let copied = false;

            try {
                await navigator.clipboard.writeText(text);
                copied = true;
            } catch (error) {
                const tempInput = document.createElement('textarea');
                tempInput.value = text;
                tempInput.style.position = 'fixed';
                tempInput.style.opacity = '0';
                document.body.appendChild(tempInput);
                tempInput.select();
                copied = document.execCommand('copy');
                document.body.removeChild(tempInput);
            }

            if (!copied || !triggerButton) {
                return;
            }

            const originalHtml = triggerButton.innerHTML;
            triggerButton.innerHTML = '<i class="fa-solid fa-check"></i><span>Copied</span>';

            window.setTimeout(function () {
                triggerButton.innerHTML = originalHtml;
            }, 1500);
        }

        function openLinkModal(name, url) {
            if (!linkModal) {
                return;
            }

            selectedDisplayUrl = url || '';
            linkModalName.textContent = name || '-';
            linkModalUrl.textContent = selectedDisplayUrl || 'Display URL unavailable for this session.';
            linkModal.classList.remove('hidden');
            linkModal.classList.add('flex');
        }

        function closeLinkModal() {
            if (!linkModal) {
                return;
            }

            linkModal.classList.remove('flex');
            linkModal.classList.add('hidden');
        }

        function renderCard(card) {
            qrCard = card;

            if (statusBadge) {
                statusBadge.className = `inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${card.status_classes}`;
                statusBadge.textContent = card.status_label;
            }

            if (activeState && emptyState) {
                activeState.classList.toggle('hidden', !card.has_token);
                emptyState.classList.toggle('hidden', card.has_token);
            }

            if (officeName) {
                officeName.textContent = card.office_name || '-';
            }
            if (officeAddress) {
                officeAddress.textContent = card.office_address || '';
            }
            if (generatedAt) {
                generatedAt.textContent = card.generated_at || '-';
            }
            if (expiresAt) {
                expiresAt.textContent = card.expires_at || '-';
            }
            if (rotation) {
                rotation.textContent = card.rotation_seconds ? `${card.rotation_seconds} sec` : '-';
            }
            if (token) {
                token.textContent = card.masked_token || '-';
            }

            renderQr(card.has_token ? card.token : null);
            updateCountdown();
        }

        function updateCountdown() {
            if (!countdown) {
                return;
            }

            if (!qrCard.has_token || !qrCard.expires_at_iso) {
                countdown.textContent = qrCard.expires_in || '-';
                return;
            }

            const expiresAtMs = Date.parse(qrCard.expires_at_iso);
            if (Number.isNaN(expiresAtMs)) {
                countdown.textContent = qrCard.expires_in || '-';
                return;
            }

            const remainingSeconds = Math.floor((expiresAtMs - Date.now()) / 1000);
            if (remainingSeconds <= 0) {
                countdown.textContent = 'Updating...';
                refreshQrCard();
                return;
            }

            countdown.textContent = formatRemaining(remainingSeconds);
        }

        async function refreshQrCard() {
            if (isRefreshing || !statusUrl) {
                return;
            }

            isRefreshing = true;

            try {
                const response = await fetch(statusUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Failed to refresh QR state.');
                }

                const payload = await response.json();
                if (payload && payload.qrCard) {
                    renderCard(payload.qrCard);
                }
            } catch (error) {
                if (countdown) {
                    countdown.textContent = qrCard.expires_in || 'Sync failed';
                }
            } finally {
                isRefreshing = false;
            }
        }

        document.querySelectorAll('[data-copy-url]').forEach(function (button) {
            button.addEventListener('click', function () {
                copyText(button.dataset.copyUrl || '', button);
            });
        });

        document.querySelectorAll('[data-open-display-link-modal="true"]').forEach(function (button) {
            button.addEventListener('click', function () {
                openLinkModal(button.dataset.displayName || '-', button.dataset.displayUrl || '');
            });
        });

        document.querySelectorAll('[data-close-display-link-modal="true"]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeLinkModal();
            });
        });

        if (linkModalCopy) {
            linkModalCopy.addEventListener('click', function () {
                copyText(selectedDisplayUrl, linkModalCopy);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeLinkModal();
            }
        });

        renderCard(qrCard);
        window.setInterval(updateCountdown, 1000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshQrCard();
            }
        });
    });
</script>
@endpush
