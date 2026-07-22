<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inquiry Response</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f5f7; color: #333333; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e1e4e8;">
        <!-- Header -->
        <div style="background-color: #4f46e5; padding: 30px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px; font-weight: bold;">Mera's Store</h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">Inquiry Response Notification</p>
        </div>
        
        <!-- Content -->
        <div style="padding: 30px; line-height: 1.6;">
            <p style="margin-top: 0; font-size: 16px;">Hello <strong>{{ $inquiry->customer_name }}</strong>,</p>
            
            <p style="font-size: 15px;">An administrator has replied to your inquiry regarding <strong>"{{ $inquiry->subject }}"</strong>.</p>
            
            <!-- Customer Message Box -->
            <div style="background-color: #f8fafc; border-left: 4px solid #cbd5e1; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; font-style: italic; color: #64748b; font-size: 14px;">Your Inquiry:</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #334155;">{{ $inquiry->message }}</p>
            </div>
            
            <!-- Admin Response Box -->
            <div style="background-color: #e0e7ff; border-left: 4px solid #6366f1; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; font-weight: bold; color: #4338ca; font-size: 14px;">Administrator Response:</p>
                <p style="margin: 5px 0 0 0; font-size: 14px; color: #1e1b4b;">{!! nl2br(e($inquiry->response)) !!}</p>
            </div>
            
            <p style="font-size: 14px; color: #64748b; margin-top: 30px;">Thank you for reaching out to us. If you have any further questions, please submit another inquiry or contact us directly.</p>
        </div>
        
        <!-- Footer -->
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0;">&copy; {{ date('Y') }} Mera's Store. All rights reserved.</p>
            <p style="margin: 5px 0 0 0;">Bantayan Public Market, Cebu, Philippines</p>
        </div>
    </div>
</body>
</html>
