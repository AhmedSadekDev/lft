<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إشعار حجز جديد</title>
    <style>
        body {
            font-family: 'Tajawal', Arial, sans-serif;
            direction: rtl;
            background-color: #f4f6f8;
            padding: 0;
            margin: 0;
            color: #333;
        }

        .email-wrapper {
            max-width: 650px;
            margin: 30px auto;
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
        }

        .email-header {
            background-color: #007bff;
            padding: 20px;
            text-align: center;
        }

        .email-header img {
            max-height: 60px;
        }

        .email-body {
            padding: 30px;
        }

        .email-body h2 {
            margin-top: 0;
            color: #007bff;
        }

        .email-body p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .details-table th,
        .details-table td {
            text-align: right;
            padding: 8px 10px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .details-table th {
            width: 30%;
            background-color: #f9fafb;
            font-weight: bold;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }

        .email-footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            padding: 20px;
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Logo / Header -->
        <div class="email-header">
            <img src="{{ asset('assets/media/logo.png') }}" alt="Logo">
        </div>

        <!-- Email Content -->
        <div class="email-body">
            <h2>مرحباً!</h2>
            <p>تم إضافة حجز جديد بالمعلومات التالية:</p>

            <table class="details-table">
                <tr>
                    <th>رقم الحجز</th>
                    <td>{{ $booking->booking_number }}</td>
                </tr>
                <tr>
                    <th>اسم الشركة</th>
                    <td>{{ $booking->company?->name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>اسم الموظف المسؤول</th>
                    <td>{{ $booking->employee_name ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>وكيل الشحن</th>
                    <td>{{ $booking->shippingAgent?->title ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>رقم الشهادة</th>
                    <td>{{ $booking->certificate_number ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>نوع العملية</th>
                    <td>{{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? 'غير محدد' }}</td>
                </tr>
                <tr>
                    <th>عدد الحاويات</th>
                    <td>{{ $booking->bookingContainers->count() }}</td>
                </tr>
            </table>

            @if($booking->bookingContainers->count())
                <p style="margin-top:20px;"><strong>تفاصيل الحاويات:</strong></p>
                <table class="details-table">
                    <thead>
                        <tr>
                            <th>رقم الحاوية</th>
                            <th>الميناء</th>
                            <th>الوصول</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booking->bookingContainers as $container)
                            <tr>
                                <td>{{ $container->container_number ?? '-' }}</td>
                                <td>{{ $container->departure?->title ?? '-' }}</td>
                                <td>{{ $container->arrival_date ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <a class="btn" href="https://leaderfortrans.com/book/?track={{ $booking->booking_number }}">عرض تفاصيل الحجز</a>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            شكراً لاستخدامك تطبيقنا!
        </div>
    </div>
</body>
</html>
