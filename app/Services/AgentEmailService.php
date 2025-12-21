<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Log;

class AgentEmailService
{
    /**
     * Send password assignment email to agent
     *
     * @param  \App\Models\Agent  $agent
     * @param  string  $token
     * @return bool
     */
    public function sendPasswordAssignmentEmail(Agent $agent, string $token): bool
    {
        try {
            $url = env('APP_URL') . '/set_password/agents/?token=' . $token;
            $name = $agent->name ?? '';
            $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $subject = 'تعيين كلمة المرور - Agent';
            $to = $agent->email;

            if (empty($to)) {
                Log::warning('AgentEmailService: Agent email is empty', [
                    'agent_id' => $agent->id,
                ]);
                return false;
            }

            $html = $this->getEmailTemplate($nameEsc, $urlEsc);

            // Headers للبريد
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . config('mail.from.name') . " <" . config('mail.from.address') . ">\r\n";
            $headers .= "Reply-To: " . config('mail.from.address') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: High\r\n";

            // Check if mail() function is available
            if (!function_exists('mail')) {
                throw new \Exception('PHP mail() function is not available on this server');
            }

            // Check sendmail path (for debugging)
            $sendmailPath = ini_get('sendmail_path');

            Log::info('AgentEmailService: Sending email using PHP mail()', [
                'agent_id' => $agent->id,
                'email' => $to,
                'subject' => $subject,
                'method' => 'PHP mail()',
                'sendmail_path' => $sendmailPath,
                'php_version' => phpversion(),
                'headers_length' => strlen($headers),
                'html_length' => strlen($html),
            ]);

            // إرسال البريد باستخدام PHP mail()
            $result = @mail($to, $subject, $html, $headers);

            // Get last error if any
            $lastError = error_get_last();

            if ($result) {
                Log::info('AgentEmailService: Email sent successfully using PHP mail()', [
                    'agent_id' => $agent->id,
                    'email' => $to,
                    'time' => now()->toDateTimeString(),
                    'method' => 'PHP mail()',
                    'note' => 'mail() returned TRUE, but actual delivery depends on server configuration',
                ]);

                return true;
            } else {
                $errorMsg = $lastError ? $lastError['message'] : 'PHP mail() returned FALSE';
                throw new \Exception('PHP mail() failed: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Log::error('AgentEmailService: Failed to send agent password email', [
                'agent_id' => $agent->id ?? null,
                'email' => $to ?? null,
                'error' => $e->getMessage(),
                'method' => 'PHP mail()',
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get email HTML template
     *
     * @param  string  $name
     * @param  string  $url
     * @return string
     */
    private function getEmailTemplate(string $name, string $url): string
    {
        return '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة المرور - Leader for Trans</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            color: #333;
            direction: rtl;
            min-height: 100vh;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }

        .logo-container {
            position: relative;
            z-index: 1;
        }

        .logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }

        .title {
            font-size: 28px;
            color: #ffffff;
            margin: 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px 30px;
            background: #ffffff;
        }

        .greeting {
            font-size: 20px;
            color: #2c3e50;
            margin: 0 0 20px 0;
            font-weight: 600;
        }

        .text {
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }

        .button-wrapper {
            text-align: center;
            margin: 35px 0;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.5);
        }

        .info-box {
            background: #f8f9fa;
            border-right: 4px solid #0d6efd;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }

        .info-box p {
            margin: 0;
            color: #495057;
            font-size: 14px;
            line-height: 1.6;
        }

        .footer {
            background: #f8f9fa;
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer-text {
            color: #6c757d;
            font-size: 13px;
            margin: 5px 0;
            line-height: 1.6;
        }

        .footer-links {
            margin-top: 15px;
        }

        .footer-links a {
            color: #0d6efd;
            text-decoration: none;
            margin: 0 10px;
            font-size: 13px;
        }

        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e9ecef, transparent);
            margin: 30px 0;
        }

        @media only screen and (max-width: 600px) {
            body {
                padding: 20px 10px;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .title {
                font-size: 24px;
            }

            .btn {
                padding: 14px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="https://cloudymenue.cloudy-digital.com/assets/media/logo.png" alt="Leader for Trans" class="logo" onerror="this.style.display=\'none\'">
                <h1 class="title">تعيين كلمة المرور للوكيل</h1>
            </div>
        </div>

        <div class="content">
            <p class="greeting">مرحباً ' . $name . ' 👋</p>

            <p class="text">
                نود إعلامك بأنه تم إنشاء حساب لك كوكيل على نظام <strong>Leader for Trans</strong>.
                <br><br>
                لإكمال تفعيل حسابك وتعيين كلمة المرور الخاصة بك، يرجى الضغط على الزر أدناه:
            </p>

            <div class="button-wrapper">
                <a href="' . $url . '" class="btn">🔐 تعيين كلمة المرور</a>
            </div>

            <div class="divider"></div>

            <div class="info-box">
                <p>
                    <strong>📌 ملاحظة هامة:</strong><br>
                    هذا الرابط صالح لاستخدام واحد فقط. إذا لم تكن قد طلبت إنشاء هذا الحساب، يمكنك تجاهل هذا البريد الإلكتروني بأمان.
                </p>
            </div>
        </div>

        <div class="footer">
            <p class="footer-text">
                <strong>Leader for Trans</strong><br>
                نظام إدارة الشحنات والنقل
            </p>
            <p class="footer-text" style="margin-top: 10px; color: #adb5bd;">
                © ' . date('Y') . ' Leader for Trans. جميع الحقوق محفوظة.
            </p>
            <div class="footer-links">
                <a href="' . env('APP_URL') . '">الموقع الإلكتروني</a>
                <span style="color: #dee2e6;">|</span>
                <a href="mailto:booking@leaderfortrans.com">اتصل بنا</a>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Test email sending with custom data
     *
     * @param  string  $email
     * @param  string  $name
     * @param  string  $token
     * @return bool
     */
    public function testEmail(string $email, string $name = 'Test Agent', string $token = 'test_token_123'): bool
    {
        try {
            $url = env('APP_URL') . '/set_password/agents/?token=' . $token;
            $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $subject = 'اختبار - تعيين كلمة المرور - Agent';
            $html = $this->getEmailTemplate($nameEsc, $urlEsc);

            // Headers للبريد
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . config('mail.from.name') . " <" . config('mail.from.address') . ">\r\n";
            $headers .= "Reply-To: " . config('mail.from.address') . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 1 (Highest)\r\n";
            $headers .= "Importance: High\r\n";

            Log::info('AgentEmailService: Testing email sending using PHP mail()', [
                'email' => $email,
                'name' => $name,
                'method' => 'PHP mail()',
            ]);

            // إرسال البريد باستخدام PHP mail()
            $result = mail($email, $subject, $html, $headers);

            if ($result) {
                Log::info('AgentEmailService: Test email sent successfully using PHP mail()', [
                    'email' => $email,
                    'time' => now()->toDateTimeString(),
                    'method' => 'PHP mail()',
                ]);

                return true;
            } else {
                throw new \Exception('PHP mail() returned FALSE');
            }
        } catch (\Exception $e) {
            Log::error('AgentEmailService: Failed to send test email', [
                'email' => $email,
                'error' => $e->getMessage(),
                'method' => 'PHP mail()',
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}

