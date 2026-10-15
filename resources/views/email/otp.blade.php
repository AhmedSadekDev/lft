@extends('email.layouts.main', ['title' => 'كود إعادة تعيين كلمة المرور', 'preheader' => 'كود التحقق الخاص بك: ' . $otp])

@section('content')
    @include('email.partials.heading', ['title' => 'كود إعادة تعيين كلمة المرور'])

    <p style="margin:14px 0 0; text-align:right;">
        تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك. استخدم الكود التالي لإكمال العملية:
    </p>

    <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:26px auto; border-collapse:separate;">
        <tr>
            <td align="center" bgcolor="#eff6ff" dir="ltr" style="background-color:#eff6ff; border:2px dashed #2563eb; border-radius:12px; padding:18px 34px; font-family:'Courier New', Courier, monospace; font-size:34px; font-weight:700; letter-spacing:10px; color:#1e3a8a;">{{ $otp }}</td>
        </tr>
    </table>

    <p style="margin:0 0 20px; font-size:13px; color:#64748b; text-align:center;">
        هذا الكود صالح لمدة 10 دقائق فقط.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%;">
        <tr>
            <td bgcolor="#fffbeb" style="background-color:#fffbeb; border-right:4px solid #d97706; border-radius:8px; padding:14px 16px; font-size:13px; line-height:1.8; color:#92400e; text-align:right;">
                لا تشارك هذا الكود مع أحد. إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذه الرسالة.
            </td>
        </tr>
    </table>
@endsection
