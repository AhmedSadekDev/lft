<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%; margin:{{ $margin ?? '20px 0 0' }}; border:1px solid #e2e8f0; border-radius:12px; border-collapse:separate; overflow:hidden;">
    @foreach ($rows as $label => $value)
        <tr>
            <td width="40%" style="padding:11px 16px; background-color:#f8fafc; font-size:13px; font-weight:700; color:#475569; text-align:right; vertical-align:top; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $label }}</td>
            <td style="padding:11px 16px; font-size:14px; color:#0f172a; text-align:right; vertical-align:top; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $value }}</td>
        </tr>
    @endforeach
</table>
