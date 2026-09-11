<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار إرسال البريد الإلكتروني</title>
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

        input[type="email"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 30px;
            color: #856404;
        }

        .warning-box strong {
            display: block;
            margin-bottom: 8px;
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
        <h1>🔧 اختبار إرسال البريد الإلكتروني</h1>
        <p class="subtitle">اختبر إعدادات SMTP وإرسال الإيميلات من نظام Leader</p>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 15px; margin-bottom: 20px; color: #856404;">
                <strong>⚠️ تنبيه هام:</strong> "تم الإرسال بنجاح" يعني أن Laravel أرسل الأمر إلى SMTP server.
                لكن وصول الإيميل الفعلي يعتمد على إعدادات السيرفر (SPF, DKIM, IP reputation).
                <br><br>
                <strong>إذا لم يصل الإيميل:</strong> المشكلة في <code>mail.leaderfortrans.com</code> وليس في الكود.
                <a href="/SMTP_TROUBLESHOOTING.md" style="color: #0d6efd; font-weight: bold;">اقرأ دليل الحلول</a>
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

        <div class="form-group">
            <label for="email">البريد الإلكتروني للاختبار:</label>
            <input type="email" id="email" name="email" value="{{ $email }}" placeholder="ahmedhassansadek8@gmail.com">
        </div>

        <div class="button-group">
            <form action="{{ route('test.email.send') }}" method="POST" style="flex: 1;">
                @csrf
                <input type="hidden" name="email" id="simple-email">
                <button type="submit" class="btn-primary">
                    📧 إرسال إيميل بسيط
                </button>
            </form>

            <form action="{{ route('test.email.send-agent') }}" method="POST" style="flex: 1;">
                @csrf
                <input type="hidden" name="email" id="agent-email">
                <button type="submit" class="btn-secondary">
                    👤 إرسال إشعار مندوب
                </button>
            </form>
        </div>

        <div style="margin-top: 15px; text-align: center; display: flex; gap: 15px; justify-content: center;">
            <a href="{{ route('test.email.check-smtp') }}" target="_blank" style="display: inline-block; padding: 12px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 10px; font-weight: 600;">
                🔍 فحص اتصال SMTP
            </a>
            <a href="{{ route('test.email.agent-service') }}" style="display: inline-block; padding: 12px 25px; background: #17a2b8; color: white; text-decoration: none; border-radius: 10px; font-weight: 600;">
                🧪 اختبار AgentEmailService
            </a>
        </div>

        <div class="warning-box">
            <strong>⚠️ تحذير أمني:</strong>
            هذه الصفحة للاختبار فقط. يجب حذفها أو حمايتها بـ middleware قبل رفع الموقع للـ production.
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p style="color: #6c757d; font-size: 14px;">
                📝 <strong>ملاحظة:</strong> بعد الإرسال، تحقق من:
            </p>
            <ul style="color: #6c757d; font-size: 14px; margin-right: 20px; margin-top: 10px;">
                <li>صندوق الوارد (Inbox)</li>
                <li>مجلد الرسائل غير المرغوب فيها (Spam/Junk)</li>
                <li>ملف السجل: <code>storage/logs/laravel.log</code></li>
            </ul>
        </div>
    </div>

    <script>
        // Sync email input with hidden fields
        const emailInput = document.getElementById('email');
        const simpleEmail = document.getElementById('simple-email');
        const agentEmail = document.getElementById('agent-email');

        emailInput.addEventListener('input', function() {
            simpleEmail.value = this.value;
            agentEmail.value = this.value;
        });

        // Set initial values
        simpleEmail.value = emailInput.value;
        agentEmail.value = emailInput.value;
    </script>
</body>
</html>

