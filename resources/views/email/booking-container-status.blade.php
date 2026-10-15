@extends('email.layouts.main', ['title' => 'تنبيه حالة الحجز', 'preheader' => 'تحديث جديد على الحجز رقم ' . ($summary['رقم الحجز'] ?? '')])

@section('content')
    @include('email.partials.heading', ['title' => 'تنبيه حالة الحجز'])

    @include('email.partials.details', ['rows' => $summary, 'margin' => '14px 0 0'])

    @foreach ($containers as $container)
        <p style="margin:26px 0 0; font-size:15px; font-weight:700; color:#0f172a; text-align:right;">
            الحاوية {{ $container['details']['رقم الحاوية'] }}
        </p>

        @include('email.partials.details', ['rows' => $container['details'], 'margin' => '10px 0 0'])

        <p style="margin:14px 0 6px; font-size:13px; font-weight:700; color:#475569; text-align:right;">بيانات السائق والسيارة</p>

        @forelse ($container['drivers'] as $driver)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" dir="rtl" style="width:100%; margin-bottom:8px;">
                <tr>
                    <td bgcolor="#f8fafc" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-right:4px solid #2563eb; border-radius:8px; padding:12px 14px; font-size:13px; line-height:1.9; color:#334155; text-align:right;">
                        <strong style="color:#0f172a; font-size:14px;">{{ $driver['name'] }}</strong>
                        <br>
                        رقم التليفون: <span dir="ltr" style="color:#0f172a;">{{ $driver['phone'] }}</span>
                        <br>
                        رقم السيارة: <strong style="color:#1d4ed8;">{{ $driver['plate'] }}</strong>
                    </td>
                </tr>
            </table>
        @empty
            <p style="margin:0; font-size:13px; color:#64748b; text-align:right;">لا توجد بيانات متاحة</p>
        @endforelse
    @endforeach

    @if ($url)
        @include('email.partials.button', ['url' => $url, 'label' => 'تفاصيل الطلب'])
    @endif
@endsection
