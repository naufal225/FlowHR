@php
    // Cari file logo FlowHR yang tersedia di public/ dan buat base64 agar aman di Dompdf
    $logoCandidates = [
        public_path('FlowHR_logo.png'),
    ];
    $logoBase64 = '';
    foreach ($logoCandidates as $candidate) {
        if (file_exists($candidate)) {
            $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            $data = @file_get_contents($candidate);
            if ($data !== false) {
                // Dompdf paling stabil untuk png/jpg. webp dicoba terakhir sebagai fallback
                $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'png'));
                $logoBase64 = 'data:image/' . $mime . ';base64,' . base64_encode($data);
                break;
            }
        }
    }
@endphp

<table width="100%" style="width:100%; border-collapse:collapse; margin-bottom: 10px;">
    <tr>
        <td style="width:25%; vertical-align:middle; padding:0; border: none;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="FlowHR Logo" style="height:60px; width:auto; display:block;">
            @endif
        </td>
        <td style="width:75%; text-align:center; vertical-align:middle; padding:0; border: none;">
            <div style="font-weight:700; font-size:14px;">FlowHR</div>
            <div style="font-size:11px; line-height:1.4; position: relative; left: -8px;">
                Bellezza BSA 1st Floor SA1-06 Jl. Letjen Soepeno,<br>
                Permata Hijau, Kebayoran Lama, Jakarta Selatan - 12210<br>
                Phone: (021) 7203052 / 0812-1953-7943<br>
                Email: support@flowhr.com &nbsp;&nbsp; Website: www.flowhr.com
            </div>
        </td>
    </tr>
</table>
<div style="border-bottom:2px solid #000; margin: 6px 0 16px 0;"></div>
