<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5fb;font-family:'Segoe UI',Arial,sans-serif;color:#26324a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5fb;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e6e8f2;">
                    <tr>
                        <td style="background:#2b2d5c;padding:22px 28px;">
                            <span style="display:inline-block;color:#ffffff;font-size:16px;font-weight:700;vertical-align:middle;">{{ config('app.name') }}</span>
                            <span style="display:block;margin-top:4px;color:#a9b2d6;font-size:12px;">Notifikasi workspace internal</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 6px;color:#7a849e;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Hai {{ $recipientName }}</p>
                            <h1 style="margin:0 0 14px;font-size:20px;line-height:1.35;color:#1e2740;">{{ $title }}</h1>
                            @if ($body)
                                <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#4b5670;">{{ $body }}</p>
                            @endif
                            @if ($link)
                                <a href="{{ $link }}" style="display:inline-block;background:#6c5ce7;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 22px;border-radius:10px;">Buka di {{ config('app.name') }}</a>
                            @endif
                            <p style="margin:24px 0 0;font-size:12px;line-height:1.6;color:#8a93a8;">
                                Email ini dikirim otomatis karena kamu terlibat dalam laporan atau agenda terkait.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#fafbff;border-top:1px solid #eceefa;color:#98a1b5;font-size:11px;">
                            {{ config('app.name') }} · Create If Impossible{{ $sentAt ? ' · '.$sentAt->translatedFormat('d M Y H:i').' WIB' : '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
