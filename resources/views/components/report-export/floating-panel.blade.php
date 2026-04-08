@props([
    'indexUrl',
    'storeUrl',
    'showUrlTemplate',
    'downloadUrlTemplate',
    'userId' => 0,
    'roleScope' => 'unknown',
])

<button
    id="report-export-launcher"
    class="fixed bottom-4 right-4 z-[90] hidden items-center gap-2 rounded-full bg-primary-700 px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-primary-800"
    type="button"
>
    <i class="fas fa-file-export"></i>
    <span>Report Export</span>
    <span
        id="report-export-launcher-count"
        class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-primary-700"
    >0</span>
</button>

<div
    id="report-export-panel"
    class="fixed bottom-20 right-4 z-[90] hidden w-[360px] max-w-[92vw] rounded-xl border border-slate-200 bg-white shadow-2xl"
    data-index-url="{{ $indexUrl }}"
    data-store-url="{{ $storeUrl }}"
    data-show-url-template="{{ $showUrlTemplate }}"
    data-download-url-template="{{ $downloadUrlTemplate }}"
    data-user-id="{{ (int) $userId }}"
    data-role-scope="{{ $roleScope }}"
>
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
        <div>
            <p class="text-sm font-semibold text-slate-900">Report Exports</p>
            <p class="text-xs text-slate-500">Background generation status</p>
        </div>
        <button
            id="report-export-panel-close"
            class="rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700"
            type="button"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="report-export-list" class="max-h-[420px] space-y-3 overflow-y-auto p-4">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
            No export activity yet.
        </div>
    </div>
</div>

<script>
    (() => {
        if (window.FlowHRReportExports) {
            return;
        }

        const panel = document.getElementById('report-export-panel');
        const launcher = document.getElementById('report-export-launcher');
        const launcherCount = document.getElementById('report-export-launcher-count');
        const closeButton = document.getElementById('report-export-panel-close');
        const listContainer = document.getElementById('report-export-list');

        if (!panel || !launcher || !launcherCount || !closeButton || !listContainer) {
            return;
        }

        const roleScope = panel.dataset.roleScope || 'unknown';
        const userId = panel.dataset.userId || '0';
        const hiddenStorageKey = `flowhr_report_exports_hidden_${roleScope}_${userId}`;
        const defaultCompletedVisibleCount = 3;
        const state = {
            items: [],
            isPanelOpen: false,
            pollingTimer: null,
            isPollingFast: true,
            completedExpanded: false,
            showHiddenCompleted: false,
            hiddenCompletedIds: new Set(),
        };
        let downloadFrame = null;

        const endpoint = {
            index: panel.dataset.indexUrl,
            store: panel.dataset.storeUrl,
        };
        const stalledQueuedSecondsThreshold = 12;
        const workerCommand = 'php artisan queue:work --queue=reports,default --tries=1 --timeout=1800 --sleep=1';

        const loadHiddenCompletedIds = () => {
            try {
                const raw = window.localStorage.getItem(hiddenStorageKey);
                if (!raw) {
                    return new Set();
                }

                const parsed = JSON.parse(raw);
                if (!Array.isArray(parsed)) {
                    return new Set();
                }

                return new Set(parsed.map((value) => String(value)));
            } catch (_error) {
                return new Set();
            }
        };

        const persistHiddenCompletedIds = () => {
            try {
                window.localStorage.setItem(
                    hiddenStorageKey,
                    JSON.stringify(Array.from(state.hiddenCompletedIds.values()))
                );
            } catch (_error) {
                // No-op on storage errors.
            }
        };

        state.hiddenCompletedIds = loadHiddenCompletedIds();

        const notify = (message, type = 'success') => {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
                return;
            }

            if (type === 'error') {
                console.error(message);
            } else {
                console.log(message);
            }
        };

        const moduleLabel = (module) => {
            if (module === 'official_travel') {
                return 'Official Travel';
            }
            if (module === 'reimbursement') {
                return 'Reimbursement';
            }
            if (module === 'overtime') {
                return 'Overtime';
            }
            return module || 'Report';
        };

        const emptyCard = (message) => `
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                ${message}
            </div>
        `;

        const statusChipClass = (status) => {
            if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
            if (status === 'failed') return 'bg-red-100 text-red-700';
            if (status === 'processing') return 'bg-blue-100 text-blue-700';
            return 'bg-amber-100 text-amber-700';
        };

        const ensureDownloadFrame = () => {
            if (downloadFrame && document.body.contains(downloadFrame)) {
                return downloadFrame;
            }

            const existingFrame = document.getElementById('report-export-download-frame');
            if (existingFrame) {
                downloadFrame = existingFrame;
                return downloadFrame;
            }

            const frame = document.createElement('iframe');
            frame.id = 'report-export-download-frame';
            frame.tabIndex = -1;
            frame.setAttribute('aria-hidden', 'true');
            frame.style.display = 'none';
            document.body.appendChild(frame);
            downloadFrame = frame;
            return downloadFrame;
        };

        const appendDownloadNonce = (url) => {
            const separator = url.includes('?') ? '&' : '?';
            return `${url}${separator}_dlts=${Date.now()}`;
        };

        const triggerBackgroundDownload = (url) => {
            const frame = ensureDownloadFrame();
            frame.src = 'about:blank';

            window.setTimeout(() => {
                frame.src = appendDownloadNonce(url);
            }, 15);
        };

        const buildExportCard = (item, options = {}) => {
            const allowDownload = options.allowDownload === true;
            const progressValue = Math.max(0, Math.min(100, Number(item.progress_percent || 0)));
            const exportLabel = item.export_type === 'evidence' ? 'Evidence ZIP' : 'Summary PDF';
            const processedLabel = Number(item.total_items || 0) > 0
                ? `${item.processed_items || 0}/${item.total_items || 0}`
                : '-';
            const progressLabel = item.status === 'processing' || item.status === 'queued'
                ? `Generating report... ${progressValue}%`
                : 'Progress';
            const createdAtMs = item.created_at ? new Date(item.created_at).getTime() : NaN;
            const queuedSeconds = Number.isFinite(createdAtMs)
                ? Math.floor((Date.now() - createdAtMs) / 1000)
                : 0;
            const isQueuedStalled = item.status === 'queued' && queuedSeconds >= stalledQueuedSecondsThreshold;
            const workerWarning = isQueuedStalled
                ? `<div class="mt-2 rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-[11px] text-amber-800">
                    Worker reports belum aktif. Jalankan: <code class="font-mono">${workerCommand}</code>
                </div>`
                : '';
            const errorMessage = item.status === 'failed' && item.error_message
                ? `<p class="mt-2 text-xs text-red-600">${item.error_message}</p>`
                : '';
            const downloadButton = allowDownload && item.download_url
                ? `<button
                        type="button"
                        data-report-export-download
                        data-export-id="${item.id}"
                        data-download-url="${item.download_url}"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                    >Download</button>`
                : '';

            return `
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">${moduleLabel(item.module)} - ${exportLabel}</p>
                            <p class="text-xs text-slate-500">${item.created_at ? new Date(item.created_at).toLocaleString() : '-'}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold ${statusChipClass(item.status)}">${item.status}</span>
                    </div>
                    <div class="mt-3">
                        <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                            <span>${progressLabel}</span>
                            <span>${progressValue}% (${processedLabel})</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                            <div class="h-full bg-primary-600 transition-all duration-300" style="width: ${progressValue}%"></div>
                        </div>
                    </div>
                    ${workerWarning}
                    ${errorMessage}
                    <div class="mt-3 flex items-center justify-end gap-2">
                        ${downloadButton}
                    </div>
                </div>
            `;
        };

        const hasInProgress = () => state.items.some(
            (item) => item.status === 'queued' || item.status === 'processing'
        );

        const render = () => {
            const inProgressItems = state.items.filter(
                (item) => item.status === 'queued' || item.status === 'processing'
            );
            const activeItems = state.items.filter(
                (item) => item.status === 'queued' || item.status === 'processing' || item.status === 'failed'
            );
            const completedItems = state.items.filter((item) => item.status === 'completed');

            launcherCount.textContent = String(inProgressItems.length);

            if (state.items.length < 1) {
                launcher.classList.add('hidden');
                launcher.classList.remove('inline-flex');
                closePanel();
                listContainer.innerHTML = emptyCard('No export activity yet.');
                return;
            }

            launcher.classList.remove('hidden');
            launcher.classList.add('inline-flex');

            const completedWithoutHidden = completedItems.filter(
                (item) => !state.hiddenCompletedIds.has(String(item.id))
            );
            const completedSource = state.showHiddenCompleted ? completedItems : completedWithoutHidden;
            const completedVisible = state.completedExpanded
                ? completedSource
                : completedSource.slice(0, defaultCompletedVisibleCount);

            const hiddenByUserCount = Math.max(0, completedItems.length - completedWithoutHidden.length);
            const collapsedCount = Math.max(0, completedSource.length - completedVisible.length);
            const hiddenBadgeCount = hiddenByUserCount + collapsedCount;
            const hasMoreCompleted = completedSource.length > defaultCompletedVisibleCount;
            const showMoreLabel = state.completedExpanded
                ? 'Show less'
                : `Show more (${collapsedCount})`;
            const showHiddenLabel = state.showHiddenCompleted
                ? `Hide hidden (${hiddenByUserCount})`
                : `Show hidden (${hiddenByUserCount})`;

            const completedControls = [];
            if (hasMoreCompleted && collapsedCount > 0 || (hasMoreCompleted && state.completedExpanded)) {
                completedControls.push(
                    `<button type="button" data-report-export-toggle-more class="text-[11px] font-semibold text-primary-700 hover:text-primary-800">${showMoreLabel}</button>`
                );
            }
            if (hiddenByUserCount > 0) {
                completedControls.push(
                    `<button type="button" data-report-export-toggle-hidden class="text-[11px] font-semibold text-slate-600 hover:text-slate-800">${showHiddenLabel}</button>`
                );
                completedControls.push(
                    `<button type="button" data-report-export-reset-hidden class="text-[11px] font-semibold text-slate-600 hover:text-slate-800">Reset hidden</button>`
                );
            }

            const activeSection = `
                <section class="space-y-2">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Active Exports</p>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">${activeItems.length}</span>
                    </div>
                    ${activeItems.length > 0
                        ? activeItems.map((item) => buildExportCard(item)).join('')
                        : emptyCard('No active exports.')}
                </section>
            `;

            const completedSection = `
                <section class="space-y-2 pt-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Completed History</p>
                            ${hiddenBadgeCount > 0
                                ? `<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">+${hiddenBadgeCount} hidden</span>`
                                : ''}
                        </div>
                        <div class="flex items-center gap-2">
                            ${completedControls.join('')}
                        </div>
                    </div>
                    ${completedVisible.length > 0
                        ? completedVisible.map((item) => buildExportCard(item, { allowDownload: true })).join('')
                        : emptyCard('No completed exports to show.')}
                </section>
            `;

            listContainer.innerHTML = `${activeSection}${completedSection}`;
        };

        const setPollingMode = (fast) => {
            state.isPollingFast = fast;
            if (state.pollingTimer) {
                clearInterval(state.pollingTimer);
                state.pollingTimer = null;
            }

            if (!hasInProgress()) {
                return;
            }

            const intervalMs = fast ? 2000 : 7000;
            state.pollingTimer = setInterval(fetchList, intervalMs);
        };

        const fetchList = async () => {
            if (!endpoint.index) {
                return;
            }

            try {
                const response = await fetch(endpoint.index, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                state.items = Array.isArray(payload.data) ? payload.data : [];
                render();
                setPollingMode(!document.hidden);
            } catch (error) {
                console.error('Failed to fetch export list', error);
            }
        };

        const openPanel = () => {
            state.isPanelOpen = true;
            panel.classList.remove('hidden');
            fetchList();
        };

        const closePanel = () => {
            state.isPanelOpen = false;
            panel.classList.add('hidden');
        };

        const readValue = (selector) => {
            if (!selector) {
                return '';
            }

            const element = document.querySelector(selector);
            return element && 'value' in element ? String(element.value || '').trim() : '';
        };

        const requestExport = async (payload, triggerButton = null) => {
            if (!endpoint.store) {
                notify('Export endpoint is not configured.', 'error');
                return null;
            }

            if (!payload?.filters?.from_date || !payload?.filters?.to_date) {
                notify('Please select both From Date and To Date before exporting.', 'error');
                return null;
            }

            if (triggerButton) {
                triggerButton.disabled = true;
                triggerButton.classList.add('opacity-70', 'cursor-not-allowed');
            }

            try {
                const response = await fetch(endpoint.store, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const message = result.message || 'Failed to queue report export.';
                    notify(message, 'error');
                    return null;
                }

                notify('Report export has been queued.', 'success');
                if (result.data && result.data.id) {
                    state.items = [
                        result.data,
                        ...state.items.filter((item) => item.id !== result.data.id),
                    ];
                    render();
                    setPollingMode(!document.hidden);
                }
                openPanel();
                await fetchList();
                return result.data || null;
            } catch (error) {
                console.error('Failed to queue export', error);
                notify('Failed to queue report export.', 'error');
                return null;
            } finally {
                if (triggerButton) {
                    triggerButton.disabled = false;
                    triggerButton.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            }
        };

        launcher.addEventListener('click', () => {
            if (state.isPanelOpen) {
                closePanel();
            } else {
                openPanel();
            }
        });

        closeButton.addEventListener('click', closePanel);

        document.addEventListener('click', async (event) => {
            const trigger = event.target.closest('[data-report-export-trigger]');
            if (trigger) {
                event.preventDefault();

                const payload = {
                    module: trigger.dataset.module || '',
                    export_type: trigger.dataset.exportType || 'summary',
                    filters: {
                        status: readValue(trigger.dataset.statusSelector),
                        from_date: readValue(trigger.dataset.fromSelector),
                        to_date: readValue(trigger.dataset.toSelector),
                    },
                };

                await requestExport(payload, trigger);
                return;
            }

            const downloadTrigger = event.target.closest('[data-report-export-download]');
            if (downloadTrigger) {
                event.preventDefault();
                const exportId = String(downloadTrigger.dataset.exportId || '');
                const downloadUrl = String(downloadTrigger.dataset.downloadUrl || '');
                if (!downloadUrl) {
                    return;
                }

                triggerBackgroundDownload(downloadUrl);

                if (exportId !== '') {
                    state.hiddenCompletedIds.add(exportId);
                    persistHiddenCompletedIds();
                }

                render();
                notify('Download started. Item disembunyikan dari history.', 'success');
                return;
            }

            const toggleMoreTrigger = event.target.closest('[data-report-export-toggle-more]');
            if (toggleMoreTrigger) {
                event.preventDefault();
                state.completedExpanded = !state.completedExpanded;
                render();
                return;
            }

            const toggleHiddenTrigger = event.target.closest('[data-report-export-toggle-hidden]');
            if (toggleHiddenTrigger) {
                event.preventDefault();
                state.showHiddenCompleted = !state.showHiddenCompleted;
                state.completedExpanded = false;
                render();
                return;
            }

            const resetHiddenTrigger = event.target.closest('[data-report-export-reset-hidden]');
            if (resetHiddenTrigger) {
                event.preventDefault();
                state.hiddenCompletedIds.clear();
                state.showHiddenCompleted = false;
                state.completedExpanded = false;
                persistHiddenCompletedIds();
                render();
                notify('Hidden history has been reset.', 'success');
                return;
            }
        });

        document.addEventListener('visibilitychange', () => {
            setPollingMode(!document.hidden);
        });

        window.FlowHRReportExports = {
            requestExport,
            refreshNow: fetchList,
            openPanel,
            closePanel,
        };

        fetchList();
    })();
</script>
