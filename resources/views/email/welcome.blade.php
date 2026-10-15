@extends('email.layouts.main', ['title' => 'مرحباً بك معنا', 'preheader' => 'تم إنشاء حسابك على Leader for Trans.'])

@section('content')
    @include('email.partials.heading', ['title' => 'مرحباً بك معنا'])

    <p style="margin:14px 0 12px; font-size:16px; font-weight:700; color:#0f172a; text-align:right;">
        مرحباً{{ filled($name) ? ' ' . $name : '' }}،
    </p>

    <p style="margin:0 0 12px; text-align:right;">
        تم إنشاء حسابك على منظومة Leader for Trans، ويسعدنا انضمامك إلينا.
    </p>

    <p style="margin:0; text-align:right;">
        ستصلك رسالة منفصلة تحتوي على رابط تعيين كلمة المرور لإكمال تفعيل حسابك.
    </p>
@endsection
