<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin Notification' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f7; font-family: 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6b21a8, #9333ea); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: 1px;">LesGo</h1>
                            <p style="margin: 6px 0 0; color: #e9d5ff; font-size: 13px;">Admin Panel Notification</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 16px; color: #1e1b4b; font-size: 20px; font-weight: 600;">
                                {{ $title ?? 'Notification' }}
                            </h2>
                            <div style="color: #4b5563; font-size: 15px; line-height: 1.7;">
                                {!! $body ?? '<p>You have a new notification from the LesGo Admin Panel.</p>' !!}
                            </div>

                            @if(isset($actionUrl) && isset($actionText))
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 30px 0 0;">
                                <tr>
                                    <td style="border-radius: 6px; background-color: #7c3aed;">
                                        <a href="{{ $actionUrl }}" style="display: inline-block; padding: 12px 28px; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none;">
                                            {{ $actionText }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 40px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 12px; text-align: center;">
                                &copy; {{ date('Y') }} LesGo. All rights reserved.
                            </p>
                            <p style="margin: 8px 0 0; color: #d1d5db; font-size: 11px; text-align: center;">
                                This is an automated message from the LesGo Admin Panel. Please do not reply.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>