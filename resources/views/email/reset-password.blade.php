<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .email-container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 40px 30px;
            text-align: right;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 200px;
            height: auto;
        }

        .email-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .email-text {
            font-size: 16px;
            margin-bottom: 15px;
            line-height: 1.8;
        }

        .code-box {
            background-color: #28a745;
            color: #fff;
            font-weight: bold;
            font-size: 20px;
            padding: 10px 25px;
            border-radius: 50px;
            display: inline-block;
            margin: 20px 0;
        }

        .footer {
            margin-top: 40px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="email-container">
        <div class="logo">
            <img src="https://admin.leaderfortrans.com/assets/media/logo.png" alt="El Zimity Logo" loading="lazy">
        </div>

        <div class="email-title">مرحبًا،</div>

        <div class="email-text">
            لقد تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك. إذا كنت أنت من طلب ذلك، الرجاء استخدام الرمز التالي:
        </div>

        <div class="code-box">{{$token}}</div>

        <div class="email-text">
            استخدم هذا الرمز لإعادة تعيين كلمة المرور الخاصة بك. صلاحية الرمز ستنتهي خلال 60 دقيقة.
        </div>

        <div class="email-text">
            إذا لم تكن قد طلبت إعادة تعيين كلمة المرور، فلا داعي لاتخاذ أي إجراء.
        </div>

        <div class="email-text">مع أطيب التحيات،</div>

        <div class="footer">El Zimity</div>
    </div>

</body>

</html>
