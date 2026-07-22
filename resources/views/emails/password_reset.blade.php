<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f5f7; color: #333333; padding: 20px; margin: 0;">
    <div style="max-width: 540px; background-color: #ffffff; margin: 0 auto; padding: 32px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="font-size: 22px; font-weight: bold; color: #4f46e5; letter-spacing: -0.5px;">
                {{ config('app.name') }}
            </div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Account Security Services</div>
        </div>

        <!-- Body -->
        <div style="font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 24px;">
            <p style="margin: 0 0 12px 0;">Hello, <strong>{{ $name }}</strong>!</p>
            <p style="margin: 0 0 16px 0;">We received a request to reset the password for your customer account. If you did not make this request, you can safely ignore this email.</p>
            <p style="margin: 0 0 24px 0;">To reset your password, click the button below. This password reset link is only valid for <strong>60 minutes</strong>:</p>
            
            <div style="text-align: center; margin-bottom: 28px;">
                <a href="{{ $resetUrl }}" style="background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14.5px; font-weight: bold; display: inline-block; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2); transition: background-color 0.2s;">
                    Reset My Password
                </a>
            </div>

            <p style="margin: 0 0 12px 0; font-size: 13.5px; color: #64748b;">If the button above does not work, copy and paste this URL into your browser:</p>
            <p style="margin: 0 0 24px 0; font-size: 12.5px; word-break: break-all; color: #4f46e5; background-color: #f8fafc; padding: 10px; border-radius: 6px; border: 1px dashed #cbd5e1;">
                {{ $resetUrl }}
            </p>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">

        <!-- Footer -->
        <div style="text-align: center; font-size: 11px; color: #94a3b8;">
            <p style="margin: 0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p style="margin: 5px 0 0 0;">Bantayan Public Market, Cebu, Philippines</p>
        </div>
    </div>
</body>
</html>
