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
            <img src="https://admin.leaderfortrans.com/assets/media/logo.png" alt="Logo">
        </div>

        <!-- Email Content -->
        <div class="email-body">
            <h2>مرحباً!</h2>
            <p>تم إضافة حجز جديد بالمعلومات التالية:</p>
            <p><strong>رقم الحجز:</strong> {{ $booking->booking_number }}</p>
            <p><strong>عدد الحاويات:</strong> {{ $booking->bookingContainers->count() }}</p>

            <a class="btn" href="https://leaderfortrans.com/book/?track={{ $booking->booking_number }}">عرض تفاصيل الحجز</a>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            شكراً لاستخدامك تطبيقنا!
        </div>
    </div>
</body>
</html>
