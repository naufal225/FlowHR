<!DOCTYPE html>
<html>

<head>
    <title>Pesan Baru</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        /* Base styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f7f9fc;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .email-header {
            background-color: #4f46e5;
            color: white;
            padding: 25px 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .email-body {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .message-box {
            background-color: #f8fafc;
            border-left: 4px solid #4f46e5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        .footer {
            text-align: center;
            padding: 20px 0;
            color: #64748b;
            font-size: 12px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 15px;
        }

        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
        }

        .text-white {
            color: white;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <h1 style="margin: 0; font-size: 22px;">YAZTECH ENGINEERING SOLUSINDO</h1>
        </div>

        <div class="email-body">
            <p style="margin-top: 0;">Halo {{ $namaApprover }},</p>
            <p>Anda menerima pesan baru dari {{ $namaPengaju }}:</p>

            <div style="margin: 20px 0;">
                <p style="margin: 5px 0;"><strong style="color: #4f46e5;">Dari:</strong> {{ $namaPengaju }}</p>
                <p style="margin: 5px 0;"><strong style="color: #4f46e5;">Email:</strong> {{ $emailPengaju ?? 'Tidak
                    disertakan' }}</p>
                <p style="margin: 5px 0;"><strong style="color: #4f46e5;">Tanggal:</strong> {{ date('d F Y H:i') }}</p>
            </div>

            <div class="divider"></div>

            <p style="margin-bottom: 5px;">Segera tanggapi pengajuan ini melalui:</p>
            <a href="{{ $linkTanggapan ?? '#' }}" class="button" style="display:inline-block;
                padding:10px 20px;
                background-color:#4f46e5;
                color:#ffffff !important;
                text-decoration:none !important;
                border-radius:4px;
                font-weight:500;
                margin-top:15px;">
                Approve/Reject
            </a>
        </div>

        <div class="footer">
            <p style="margin: 5px 0;">© {{ date('Y') }} Yaztech Engineering Solusindo</p>
            <p style="margin: 5px 0; font-size: 11px;">Email ini dikirim secara otomatis, harap tidak membalas langsung.
            </p>
        </div>
    </div>
</body>

</html>