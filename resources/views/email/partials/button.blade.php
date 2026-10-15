<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:28px auto 8px;">
    <tr>
        <td align="center" bgcolor="#2563eb" style="background-color:#2563eb; border-radius:10px;">
            <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:14px 40px; font-family:Tahoma, 'Segoe UI', Arial, sans-serif; font-size:15px; font-weight:700; line-height:1.4; color:#ffffff !important; text-decoration:none; border-radius:10px;"><span style="color:#ffffff;">{{ $label }}</span></a>
        </td>
    </tr>
</table>

@if (!empty($showUrl))
    <p style="margin:18px 0 0; font-size:12px; line-height:1.7; color:#64748b; text-align:center;">
        إذا لم يعمل الزر، انسخ الرابط التالي والصقه في المتصفح:
        <br>
        <a href="{{ $url }}" target="_blank" dir="ltr" style="color:#1d4ed8; word-break:break-all;">{{ $url }}</a>
    </p>
@endif
