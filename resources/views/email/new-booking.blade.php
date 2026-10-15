@extends('email.layouts.main', ['title' => 'إشعار حجز جديد', 'preheader' => 'تم إضافة حجز جديد برقم ' . $bookingNumber])

@section('content')
    @include('email.partials.heading', ['title' => 'إشعار حجز جديد', 'subtitle' => 'رقم الحجز: ' . $bookingNumber])

    <p style="margin:0; text-align:right;">تم إضافة حجز جديد بالمعلومات التالية:</p>

    @include('email.partials.details', ['rows' => $details])

    @if (count($containers))
        <p style="margin:26px 0 0; font-size:15px; font-weight:700; color:#0f172a; text-align:right;">تفاصيل الحاويات</p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%; margin-top:10px; border:1px solid #e2e8f0; border-radius:12px; border-collapse:separate; overflow:hidden;">
            <tr>
                <th bgcolor="#f1f5f9" style="background-color:#f1f5f9; padding:10px 12px; font-size:13px; font-weight:700; color:#334155; text-align:right; border-bottom:1px solid #e2e8f0;">رقم الحاوية</th>
                <th bgcolor="#f1f5f9" style="background-color:#f1f5f9; padding:10px 12px; font-size:13px; font-weight:700; color:#334155; text-align:right; border-bottom:1px solid #e2e8f0;">مقاس الحاوية</th>
                <th bgcolor="#f1f5f9" style="background-color:#f1f5f9; padding:10px 12px; font-size:13px; font-weight:700; color:#334155; text-align:right; border-bottom:1px solid #e2e8f0;">الوصول</th>
            </tr>
            @foreach ($containers as $container)
                <tr>
                    <td style="padding:10px 12px; font-size:13px; color:#0f172a; text-align:right; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $container['number'] }}</td>
                    <td style="padding:10px 12px; font-size:13px; color:#0f172a; text-align:right; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $container['size'] }}</td>
                    <td style="padding:10px 12px; font-size:13px; color:#0f172a; text-align:right; {{ $loop->last ? '' : 'border-bottom:1px solid #e2e8f0;' }}">{{ $container['arrival'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @include('email.partials.button', ['url' => $url, 'label' => 'عرض تفاصيل الحجز'])
@endsection
