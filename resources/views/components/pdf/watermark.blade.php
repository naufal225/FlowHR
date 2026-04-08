@php
    $reportLogo = (string) config('branding.report_logo', 'FlowHR_logo Transapran.png');
    $logoCandidates = [
        public_path($reportLogo),
        public_path('FlowHR_logo Transapran.png'),
        public_path('FlowHR_logo.png'),
    ];
    $logoBase64 = '';

    foreach ($logoCandidates as $candidate) {
        if (!file_exists($candidate)) {
            continue;
        }

        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        $data = @file_get_contents($candidate);
        if ($data === false) {
            continue;
        }

        $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'png'));
        $logoBase64 = 'data:image/' . $mime . ';base64,' . base64_encode($data);
        break;
    }
@endphp

@if($logoBase64 !== '')
    <div style="position: fixed; right: 18px; bottom: 18px; width: 250px; opacity: 0.7; z-index: 5;">
        <img src="{{ $logoBase64 }}" alt="FlowHR Watermark" style="width:100%; height:auto; display:block;">
    </div>
@endif

