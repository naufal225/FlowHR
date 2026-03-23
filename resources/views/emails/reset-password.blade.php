<!DOCTYPE html>
<html>

<head>
    <title>Reset Your Password</title>
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
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
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

        .security-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
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
            padding: 12px 24px;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: white !important;
            text-decoration: none !important;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
        }

        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.4);
        }

        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 25px 0;
        }

        .text-center {
            text-align: center;
        }

        .icon {
            display: inline-block;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-bottom: 10px;
            line-height: 50px;
            font-size: 20px;
        }

        .highlight {
            color: #4f46e5;
            font-weight: 600;
        }

        .small-text {
            font-size: 13px;
            color: #64748b;
            margin-top: 10px;
        }

        .warning-text {
            color: #dc2626;
            font-weight: 500;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 10px;
            }

            .email-header,
            .email-body {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="email-header">
            <div class="icon">🔐</div>
            <h1 style="margin: 0; font-size: 22px;">YAZTECH ENGINEERING SOLUSINDO</h1>
            <p style="margin: 10px 0 0 0; font-size: 14px; opacity: 0.9;">Password Reset Request</p>
        </div>

        <div class="email-body">
            <p style="margin-top: 0; font-size: 16px;">Hello <strong class="highlight">{{ $userName }}</strong>,</p>

            <p>We received a request to reset the password for your account. If you made this request, please click the button below to create a new password.</p>

            <div class="info-box">
                <p style="margin: 0;"><strong style="color: #3b82f6;">📧 Account:</strong> This email was sent to your registered email address</p>
                <p style="margin: 5px 0 0 0;"><strong style="color: #3b82f6;">⏰ Request Time:</strong> {{ date('F d, Y \a\t H:i') }}</p>
                <p style="margin: 5px 0 0 0;"><strong style="color: #3b82f6;">⏳ Valid Until:</strong> {{ date('F d, Y \a\t H:i', strtotime('+60 minutes')) }}</p>
            </div>

            <div class="text-center">
                <a href="{{ $resetUrl }}" class="button">
                    🔑 Reset My Password
                </a>
            </div>

            <div class="text-center small-text">
                <p>This link will expire in <strong>60 minutes</strong> for security reasons.</p>
            </div>

            <div class="divider"></div>

            <div class="security-notice">
                <h3 style="color: #d97706; margin: 0 0 10px 0; font-size: 16px;">🛡️ Security Notice</h3>
                <p style="margin: 0; font-size: 14px;">
                    <strong>If you didn't request this password reset:</strong>
                </p>
                <ul style="margin: 10px 0 0 20px; font-size: 14px;">
                    <li>Please ignore this email - your password will remain unchanged</li>
                    <li>Consider changing your password if you suspect unauthorized access</li>
                    <li>Contact our support team if you have security concerns</li>
                </ul>
            </div>

            <div class="divider"></div>

            <h3 style="color: #4f46e5; margin-bottom: 10px; font-size: 16px;">📋 What happens next?</h3>
            <ol style="color: #64748b; font-size: 14px; padding-left: 20px;">
                <li>Click the "Reset My Password" button above</li>
                <li>You'll be redirected to a secure page</li>
                <li>Enter your new password (minimum 8 characters)</li>
                <li>Confirm your new password</li>
                <li>Sign in with your new credentials</li>
            </ol>

            <div class="divider"></div>

            <p style="font-size: 14px; color: #64748b; margin-bottom: 0;">
                If the button doesn't work, you can copy and paste this link into your browser:
            </p>
            <p style="font-size: 12px; color: #3b82f6; word-break: break-all; background-color: #f8fafc; padding: 10px; border-radius: 4px; margin-top: 10px;">
                {{ $resetUrl }}
            </p>
        </div>

        <div class="footer">
            <p style="margin: 5px 0; font-weight: 600;">© {{ date('Y') }} Yaztech Engineering Solusindo</p>
            <p style="margin: 5px 0; font-size: 11px;">This email was sent automatically, please do not reply directly.</p>
            <p style="margin: 15px 0 5px 0; font-size: 11px;">
                <strong class="warning-text">Security Reminder:</strong> Never share your password or reset links with anyone.
            </p>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <p style="margin: 5px 0; font-size: 11px;">
                    📞 Need help? Contact us at:
                    <a href="mailto:support@yaztech.com" style="color: #4f46e5; text-decoration: none;">support@yaztech.com</a>
                </p>
                <p style="margin: 5px 0; font-size: 11px;">
                    🌐 Visit our website:
                    <a href="https://yaztech.com" style="color: #4f46e5; text-decoration: none;">www.yaztech.com</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
