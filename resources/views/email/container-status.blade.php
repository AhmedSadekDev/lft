@extends('email.layouts.main', ['title' => 'تحديث حالة الشحنة', 'preheader' => $statusMessage])

@section('content')
    @include('email.partials.heading', ['title' => 'تحديث حالة الشحنة'])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%; margin-top:14px;">
        <tr>
            <td bgcolor="#eff6ff" style="background-color:#eff6ff; border-right:4px solid #2563eb; border-radius:8px; padding:14px 16px; font-size:15px; font-weight:700; line-height:1.8; color:#1e3a8a; text-align:right;">{{ $statusMessage }}</td>
        </tr>
    </table>

    @include('email.partials.details', ['rows' => $details])

    @if ($url)
        @include('email.partials.button', ['url' => $url, 'label' => 'تفاصيل الطلب'])
    @endif
@endsection
