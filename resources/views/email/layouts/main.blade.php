@php
    $font = "Tahoma, 'Segoe UI', Arial, sans-serif";
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $title ?? 'Leader for Trans' }}</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; }
        table { border-collapse: collapse; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        a { color: #1d4ed8; }
        @media only screen and (max-width: 620px) {
            .lft-shell { width: 100% !important; }
            .lft-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .lft-title { font-size: 20px !important; }
        }
    </style>
</head>
<body dir="rtl" style="margin:0; padding:0; background-color:#eef2f7;">
    @isset($preheader)
        <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#eef2f7; opacity:0;">{{ $preheader }}</div>
    @endisset

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" bgcolor="#eef2f7" style="background-color:#eef2f7;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" class="lft-shell" width="600" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:600px; max-width:600px; background-color:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td align="center" bgcolor="#0f2747" style="background-color:#0f2747; background-image:linear-gradient(135deg, #0f2747 0%, #1d4ed8 100%); padding:28px 24px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                <tr>
                                    <td align="center" bgcolor="#ffffff" style="background-color:#ffffff; border-radius:14px; padding:10px 18px;">
                                        <img src="{{ asset('assets/media/logo.png') }}" width="96" alt="Leader for Trans" style="display:block; width:96px; height:auto;">
                                    </td>
                                </tr>
                            </table>
                            <div style="font-family:{{ $font }}; font-size:18px; font-weight:700; color:#ffffff; margin-top:14px;">Leader for Trans</div>
                            <div style="font-family:{{ $font }}; font-size:13px; color:#dbeafe; margin-top:4px;">نظام إدارة الشحنات والنقل</div>
                        </td>
                    </tr>

                    <tr>
                        <td class="lft-pad" dir="rtl" align="right" style="padding:32px 36px 28px; font-family:{{ $font }}; font-size:15px; line-height:1.8; color:#334155; text-align:right; direction:rtl;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td class="lft-pad" align="center" bgcolor="#f8fafc" style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:20px 36px; font-family:{{ $font }}; font-size:12px; line-height:1.8; color:#64748b; text-align:center; direction:rtl;">
                            للاستفسار تواصل معنا على
                            <a href="mailto:booking@leaderfortrans.com" style="color:#1d4ed8; text-decoration:none;" dir="ltr">booking@leaderfortrans.com</a>
                            <br>
                            <span dir="ltr">© {{ date('Y') }} Leader for Trans</span> · جميع الحقوق محفوظة
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
