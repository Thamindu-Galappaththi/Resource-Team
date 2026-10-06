<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up your password</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;font-family:Inter,Segoe UI,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0f172a;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:18px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0b3d91,#00aeef);padding:28px 32px;text-align:center;">
                            <img src="{{ url('images/logos/nebula.png') }}" alt="Nebula Institute of Technology" width="180" style="max-width:180px;height:auto;display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 8px;color:#0b3d91;font-size:13px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">Resource Reservation</p>
                            <h1 style="margin:0 0 16px;color:#111827;font-size:24px;line-height:1.3;">Welcome{{ ! empty($user->name) ? ', '.$user->name : '' }}</h1>
                            <p style="margin:0 0 16px;color:#4b5563;font-size:16px;line-height:1.6;">
                                An account was created for you. Set a password to start using the Resource Reservation System.
                            </p>
                            <p style="margin:0 0 24px;color:#4b5563;font-size:15px;line-height:1.6;">
                                This link expires in <strong>{{ $expiresMinutes }} minutes</strong>.
                            </p>
                            <p style="margin:0 0 28px;text-align:center;">
                                <a href="{{ $url }}" style="display:inline-block;background:#0b3d91;color:#ffffff;text-decoration:none;font-weight:700;font-size:16px;padding:14px 28px;border-radius:10px;">
                                    Set up password
                                </a>
                            </p>
                            <p style="margin:0 0 8px;color:#6b7280;font-size:13px;line-height:1.6;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0 0 24px;word-break:break-all;color:#0b3d91;font-size:13px;">
                                <a href="{{ $url }}" style="color:#0b3d91;">{{ $url }}</a>
                            </p>
                            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
                                If you were not expecting this invitation, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 24px;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:12px;text-align:center;">
                            Nebula Institute of Technology · SLT Mobitel
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
