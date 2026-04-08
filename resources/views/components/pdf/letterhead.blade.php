@php
    $reportLogo = (string) config('branding.report_logo', 'FlowHR_logo Transapran.png');
    $logoCandidates = [
        public_path($reportLogo),
        public_path('FlowHR_logo Transapran.png'),
        public_path('FlowHR_logo.png'),
    ];
    $logoBase64 = '';

    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            $data = @file_get_contents($candidate);
            if ($data !== false) {
                $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'png'));
                $logoBase64 = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                break;
            }
        }
    }
@endphp

<table width="100%" style="width:100%; border-collapse:collapse; margin:0 0 8px 0;">
    <tr>
        <td style="width:32%; vertical-align:middle; padding:0; border:none;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="FlowHR Logo" style="height:78px; max-width:220px; width:auto; display:block;">
            @endif
        </td>
        <td style="width:68%; text-align:center; vertical-align:middle; padding:0; border:none;">
            <div style="font-weight:700; font-size:15px; letter-spacing:0.2px;">FlowHR</div>
            <div style="font-size:11px; line-height:1.45;">
                Bellezza BSA 1st Floor SA1-06 Jl. Letjen Soepeno,<br>
                Permata Hijau, Kebayoran Lama, Jakarta Selatan - 12210<br>
                Phone: (021) 7203052 / 0812-1953-7943<br>
                Email: support@flowhr.com &nbsp;&nbsp; Website: www.flowhr.com
            </div>
        </td>
    </tr>
</table>
<div style="border-bottom:2px solid #000; margin: 4px 0 14px 0;"></div>
