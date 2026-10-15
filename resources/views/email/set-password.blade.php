@extends('email.layouts.main', ['title' => 'تعيين كلمة المرور', 'preheader' => 'اضغط على الزر لتعيين كلمة المرور وتفعيل حسابك على Leader for Trans.'])

@section('content')
    @include('email.partials.heading', ['title' => 'تعيين كلمة المرور', 'subtitle' => $accountLabel])

    <p style="margin:0 0 12px; font-size:16px; font-weight:700; color:#0f172a; text-align:right;">
        مرحباً{{ filled($name) ? ' ' . $name : '' }}،
    </p>

    <p style="margin:0; text-align:right;">{{ $intro }}</p>

    @include('email.partials.button', ['url' => $url, 'label' => 'تعيين كلمة المرور', 'showUrl' => true])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%; margin-top:24px;">
        <tr>
            <td bgcolor="#eff6ff" style="background-color:#eff6ff; border-right:4px solid #2563eb; border-radius:8px; padding:14px 16px; font-size:13px; line-height:1.8; color:#1e3a8a; text-align:right;">
                هذا الرابط خاص بحسابك، لا تشاركه مع أحد.
                <br>
                إذا لم تكن تتوقع هذه الرسالة، يمكنك تجاهلها بأمان.
            </td>
        </tr>
    </table>
@endsection
