<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Office Attendance QR Display</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('FlowHR_logo.png') }}">
    <style>
        :root {
            color-scheme: only light;
            --bg: #0f172a;
            --surface: #ffffff;
            --ink: #0f172a;
            --active: #065f46;
            --active-bg: #d1fae5;
            --inactive: #9f1239;
            --inactive-bg: #ffe4e6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at 20% 20%, #1e293b 0%, rgba(30, 41, 59, 0.1) 55%),
                radial-gradient(circle at 80% 80%, #0b4a6f 0%, rgba(11, 74, 111, 0.1) 48%),
                var(--bg);
            color: #f8fafc;
            display: grid;
            place-items: center;
            padding: 2rem;
        }
        .panel {
            width: min(1200px, 100%);
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 24px;
            padding: 2rem;
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 2rem;
        }
        .qr-card {
            background: var(--surface);
            border-radius: 20px;
            color: var(--ink);
            padding: 1.25rem;
            box-shadow: 0 20px 40px rgba(2, 6, 23, 0.35);
        }
        #qr-canvas {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 10px;
            min-height: 320px;
            display: grid;
            place-items: center;
        }
        .meta h1 {
            margin: 0;
            font-size: clamp(1.5rem, 2.2vw, 2.3rem);
            line-height: 1.2;
        }
        .meta p {
            margin: 0.5rem 0 1rem;
            color: #cbd5e1;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.4rem 0.9rem;
            font-size: 0.88rem;
            font-weight: 700;
        }
        .chip.active {
            color: var(--active);
            background: var(--active-bg);
        }
        .chip.inactive {
            color: var(--inactive);
            background: var(--inactive-bg);
        }
        .metrics {
            margin-top: 1.2rem;
            display: grid;
            gap: 0.7rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .metric {
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.35);
            border-radius: 14px;
            padding: 0.8rem 1rem;
        }
        .metric .label {
            display: block;
            font-size: 0.78rem;
            color: #cbd5e1;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .metric .value {
            display: block;
            font-weight: 700;
            font-size: 1rem;
            color: #f8fafc;
            word-break: break-word;
        }
        .hint {
            margin-top: 1rem;
            color: #94a3b8;
            font-size: 0.88rem;
        }
        @media (max-width: 900px) {
            body { padding: 1rem; }
            .panel {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            #qr-canvas { min-height: 280px; }
        }
    </style>
</head>
<body>
<main class="panel">
    <section class="qr-card">
        <div id="qr-canvas"></div>
    </section>
    <section class="meta">
        <h1>Attendance QR Display</h1>
        <p>Scan QR ini melalui aplikasi mobile employee untuk check-in/check-out di kantor.</p>
        <span id="status-chip" class="chip inactive">Unavailable</span>
        <div class="metrics">
            <div class="metric">
                <span class="label">Office</span>
                <span id="office-name" class="value">{{ $qrPayload['office_name'] ?? '-' }}</span>
            </div>
            <div class="metric">
                <span class="label">Countdown</span>
                <span id="countdown" class="value">-</span>
            </div>
            <div class="metric">
                <span class="label">Expired Time</span>
                <span id="expired-time" class="value">-</span>
            </div>
            <div class="metric">
                <span class="label">Session</span>
                <span class="value">Display #{{ $session->id }}</span>
            </div>
        </div>
    </section>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    (function () {
        const statusUrl = @json($statusUrl);
        const pollingIntervalMs = @json($pollingIntervalMs);
        const qrCanvas = document.getElementById('qr-canvas');
        const officeName = document.getElementById('office-name');
        const statusChip = document.getElementById('status-chip');
        const countdown = document.getElementById('countdown');
        const expiredTime = document.getElementById('expired-time');
        let state = @json($qrPayload);
        let refreshing = false;

        function formatRemaining(expiresIso) {
            if (!expiresIso) return '-';
            const expiresAt = Date.parse(expiresIso);
            if (Number.isNaN(expiresAt)) return '-';
            const seconds = Math.floor((expiresAt - Date.now()) / 1000);
            if (seconds <= 0) return 'Updating...';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
        }

        function renderQr(token) {
            qrCanvas.innerHTML = '';
            if (!token || typeof QRCode === 'undefined') {
                const fallback = document.createElement('p');
                fallback.textContent = 'QR belum tersedia';
                fallback.style.color = '#64748b';
                fallback.style.fontWeight = '700';
                qrCanvas.appendChild(fallback);
                return;
            }
            new QRCode(qrCanvas, {
                text: token,
                width: 300,
                height: 300,
                colorDark: '#020617',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M,
            });
        }

        function renderStatusChip(label) {
            const normalized = (label || '').toLowerCase();
            const isActive = normalized === 'active';
            statusChip.textContent = label || 'Unavailable';
            statusChip.classList.toggle('active', isActive);
            statusChip.classList.toggle('inactive', !isActive);
        }

        function render(payload) {
            state = payload || {};
            officeName.textContent = state.office_name || '-';
            renderStatusChip(state.status_label);
            renderQr(state.token || null);
            countdown.textContent = formatRemaining(state.expires_at_iso);

            if (state.expires_at_formatted) {
                expiredTime.textContent = state.expires_at_formatted
            } else {
                expiredTime.textContent = '-';
            }

            // if (state.expires_at_iso) {
            //     const expiresAt = new Date(state.expires_at_iso);
            //     const expiresAtFormatted = new Date(state.expires_at_formatted)
            //     expiredTime.textContent = Number.isNaN(expiresAtFormatted.getTime())
            //         ? '-'
            //         : expiresAt.toLocaleString('id-ID', { hour12: false });
            // } else {
            //     expiredTime.textContent = '-';
            // }
        }

        async function refreshState() {
            if (!statusUrl || refreshing) return;
            refreshing = true;

            try {
                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });

                if (!response.ok) {
                    throw new Error('Unable to refresh QR state.');
                }

                const payload = await response.json();
                render(payload);
            } catch (error) {
                countdown.textContent = 'Sync failed';
                renderStatusChip('Inactive');
            } finally {
                refreshing = false;
            }
        }

        render(state);

        window.setInterval(function () {
            countdown.textContent = formatRemaining(state.expires_at_iso);
            if (countdown.textContent === 'Updating...') {
                refreshState();
            }
        }, 1000);

        window.setInterval(refreshState, Math.max(1000, Number(pollingIntervalMs) || 2500));

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                refreshState();
            }
        });
    })();
</script>
</body>
</html>
