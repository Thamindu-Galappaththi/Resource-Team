<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set up your password</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;font-family:Inter,Segoe UI,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:#4b5563;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0f172a;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0b3d91,#00aeef);padding:20px 24px;text-align:center;">
                            <img src="{{ url('images/logos/nebula.png') }}" alt="Nebula Institute of Technology" width="140" style="max-width:140px;height:auto;display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 6px;color:#0b3d91;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;">Resource Reservation</p>
                            <h1 style="margin:0 0 10px;color:#111827;font-size:18px;line-height:1.35;font-weight:700;">Welcome{{ ! empty($user->name) ? ', '.$user->name : '' }}</h1>
                            <p style="margin:0 0 10px;color:#4b5563;font-size:14px;line-height:1.5;">
                                An account was created for you. Set a password to start using the Resource Reservation System.
                            </p>
                            <p style="margin:0 0 18px;color:#6b7280;font-size:13px;line-height:1.5;">
                                This link expires in <strong style="color:#374151;">{{ $expiresMinutes }} minutes</strong>.
                            </p>
                            <p style="margin:0 0 18px;text-align:center;">
                                <a href="{{ $url }}" style="display:inline-block;background:#0b3d91;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:10px 20px;border-radius:8px;">
                                    Set up password
                                </a>
                            </p>
                            <p style="margin:0 0 6px;color:#6b7280;font-size:12px;line-height:1.5;">
                                If the button does not work, copy and paste this link into your browser:
                            </p>
                            <p style="margin:0 0 16px;word-break:break-all;font-size:12px;line-height:1.5;">
                                <a href="{{ $url }}" style="color:#0b3d91;">{{ $url }}</a>
                            </p>
                            <p style="margin:0;color:#9ca3af;font-size:11px;line-height:1.5;">
                                If you were not expecting this invitation, you can ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 24px 18px;border-top:1px solid #e5e7eb;color:#9ca3af;font-size:11px;text-align:center;">
                            Nebula Institute of Technology · SLT Mobitel
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
