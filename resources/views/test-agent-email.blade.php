<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار AgentEmailService</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            direction: rtl;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .config-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .config-box h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .config-item:last-child {
            border-bottom: none;
        }

        .config-label {
            font-weight: 600;
            color: #6c757d;
        }

        .config-value {
            color: #212529;
            font-family: 'Courier New', monospace;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }

        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        button {
            flex: 1;
            padding: 15px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 15px;
            margin-top: 30px;
            color: #004085;
        }

        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }

        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 اختبار AgentEmailService</h1>
        <p class="subtitle">اختبر إرسال إيميلات المندوبين باستخدام Service خارجي</p>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="config-box">
            <h3>⚙️ إعدادات البريد الحالية</h3>
            @foreach($config as $key => $value)
                <div class="config-item">
                    <span class="config-label">{{ $key }}</span>
                    <span class="config-value">{{ $value ?: '(غير محدد)' }}</span>
                </div>
            @endforeach
        </div>

        <form action="{{ route('test.email.agent-service.send') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email">البريد الإلكتروني للاختبار:</label>
                <input type="email" id="email" name="email" value="{{ $email }}" placeholder="ahmedhassansadek8@gmail.com" required>
            </div>

            <div class="form-group">
                <label for="name">اسم المندوب:</label>
                <input type="text" id="name" name="name" value="{{ $name }}" placeholder="اسم المندوب" required>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-primary">
                    📧 إرسال إيميل اختبار
                </button>
            </div>
        </form>

        <div class="info-box">
            <strong>ℹ️ معلومات:</strong>
            <p>هذه الصفحة تختبر <code>AgentEmailService</code> - Service خارجي لإرسال إيميلات المندوبين.</p>

            <div class="code-block">
                <strong>الكود المستخدم:</strong><br>
                $agentEmailService = app(\App\Services\AgentEmailService::class);<br>
                $result = $agentEmailService->testEmail($email, $name);
            </div>

            <p style="margin-top: 15px;">
                <strong>✅ المميزات:</strong>
            </p>
            <ul style="margin-right: 20px; margin-top: 10px;">
                <li>Service منفصل يمكن إعادة استخدامه</li>
                <li>Logging مفصل لكل عملية</li>
                <li>معالجة أخطاء شاملة</li>
                <li>سهل الاختبار والصيانة</li>
            </ul>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="color: #6c757d; font-size: 14px;">
                📝 <strong>ملاحظة:</strong> بعد الإرسال، تحقق من:
            </p>
            <ul style="color: #6c757d; font-size: 14px; margin-right: 20px; margin-top: 10px;">
                <li>صندوق الوارد (Inbox)</li>
                <li>مجلد الرسائل غير المرغوب فيها (Spam/Junk)</li>
                <li>ملف السجل: <code>storage/logs/laravel.log</code></li>
                <li>ابحث عن: <code>AgentEmailService</code> في الـ logs</li>
            </ul>
        </div>
    </div>
</body>
</html>

